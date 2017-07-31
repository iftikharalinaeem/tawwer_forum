<?php if (!defined('APPLICATION')) exit();

/**
 * Simple Vanilla API
 *
 * Changes:
 *  1.0        Initial Release
 *  1.1        Versioning overhaul
 *  1.2        Authentication overhaul
 *  1.2.1      Fix User lookup bug
 *  1.2.5      Fix early error format bug
 *  1.2.6      Slightly tweak API request detection
 *  1.2.7      Add ->API to Gdn_Dispatcher
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class SimpleAPIPlugin extends Gdn_Plugin {

    /**
     * Mapper tool
     * @var SimpleApiMapper
     */
    public $Mapper = NULL;

    /**
     * Detect API calls
     * @var boolean
     */
    public $API = false;

    /**
     * Generate an exception from an array of errors.
     *
     * @param array $errors An array of arrays in the form [code, message].
     * @return \Exception
     */
    protected static function createException($errors) {
        $max_code = 0;
        $messages = [];

        foreach ($errors as $row) {
            list($code, $message) = $row;
            $max_code = max($max_code, $code);
            $messages[] = $message;
        }

        return new Exception(implode(' ', $messages), $max_code);
    }

    /**
     * Intercept POST data
     *
     * This method inspects and potentially modifies incoming POST data to
     * facilitate simpler API development.
     *
     * For example, passing a KVP of:
     *    User.Email = tim@vanillaforums.com
     * would result in the corresponding UserID KVP being added to the POST data:
     *    UserID = 2387
     *
     * @param array $post
     * @param boolean $throwError
     * @return boolean
     * @throws Exception
     */
    public static function translatePost(&$post, $throwError = true) {

        $errors = [];
        $postData = $post;
        $post = [];

        // Loop over every KVP in the POST data
        foreach ($postData as $key => $value) {
            if ($key == 'access_token') continue;

            // Unscrew PHP encoding of periods in POST data
            $key = str_replace('_', '.', $key);
            $post[$key] = $value;

        }
        unset($postData);

        // Loop over every KVP in the POST data.
        foreach ($post as $key => $value) {
            $translateErrors = self::translateField($post, $key, $value);
            if (is_array($translateErrors))
                $errors = array_merge($errors, $translateErrors);

        }

        if (count($errors) > 0) {
            if ($throwError) {
                throw self::createException($errors);
            } else {
                return $errors;
            }
        }

        return true;
    }

    /**
     * Intercept GET data
     *
     * This method inspects and potentially modifies incoming GET data to
     * facilitate simpler API development.
     *
     * For example, passing a KVP of:
     *    User.Email = tim@vanillaforums.com
     * would result in the corresponding UserID KVP being added to the GET data:
     *    UserID = 2387
     *
     * @param array $get
     * @param boolean $throwError
     * @return boolean
     * @throws Exception
     */
    public static function translateGet(&$get, $throwError = true) {
        $errors = [];
        $getData = $get;
        $get = [];

        // Loop over every KVP in the POST data
        foreach ($getData as $key => $value) {
            if ($key == 'access_token') continue;

            // Unscrew PHP encoding of periods in POST data
            $key = str_replace('_', '.', $key);
            $get[$key] = $value;

        }
        unset($getData);

        // Loop over every KVP in the GET data
        foreach ($get as $key => $value) {
            $translateErrors = self::translateField($get, $key, $value);
            if (is_array($translateErrors)) {
                $errors = array_merge($errors, $translateErrors);
            }
        }

        if (count($errors) > 0) {
            if ($throwError) {
                throw self::createException($errors);
            } else {
                return $errors;
            }
        }

        return true;
    }

    /**
     * Translate a single field in an array
     *
     * @param array $data
     * @param string $field
     * @param string $value
     */
    protected static function translateField(&$data, $field, $value) {
        $errors = [];
        $supportedTables = ['Badge', 'Category', 'Rank', 'Role', 'User', 'Discussion'];

        try {

            // If the Key is dot-delimited, inspect it for potential munging
            if (strpos($field, '.') !== false) {

                list($fieldPrefix, $columnLookup) = explode('.', $field, 2);

                $fieldPrefix = ucfirst($fieldPrefix); // support some form of case-insensitivity.
                $tableName = $fieldPrefix;

                if (StringEndsWith($fieldPrefix, 'User'))
                    $tableName = 'User';

                if (StringEndsWith($fieldPrefix, 'Users'))
                    $tableName = 'Users';

                // Limit to supported tables
                $tableAllowed = true;
                $multi = false;
                if (!in_array($tableName, $supportedTables)) {
                    $tableAllowed = false;

                    // First check if this is a Multi request
                    if (stringEndsWith($tableName, 'ies')) {
                        // Swap out that IES with Y in $TableName (e.g. Categories becomes Category).
                        $singularTableName = stringEndsWith($tableName, 'ies', false, true).'y';
                    } else {
                        // Fallback to assuming its a plural if it ends in an S.
                        $singularTableName = stringEndsWith($tableName, 's', false, true);
                    }

                    if ($singularTableName) {
                        if (in_array($singularTableName, $supportedTables)) {
                            // De-pluralize $FieldPrefix, now that we've confirmed the table is supported.
                            if (stringEndsWith($fieldPrefix, 'ies')) {
                                $fieldPrefix = stringEndsWith($fieldPrefix, 'ies', false, true).'y';
                            } else {
                                $fieldPrefix = stringEndsWith($fieldPrefix, 's', false, true);
                            }

                            $tableName = $singularTableName;
                            $tableAllowed = true;
                            $multi = true;
                        }
                    }

                    // Only allow the ForeignID of the discussion table.
                    if ($singularTableName === 'Discussion' && !in_array($columnLookup, ['DiscussionID', 'ForeignID']))
                        $tableAllowed = false;
                }

                if (!$tableAllowed)
                    return;

                // We desire the 'ID' root field
                $lookupField = "{$fieldPrefix}ID";
                $outputField = $lookupField;

                if (StringEndsWith($fieldPrefix, 'User'))
                    $lookupField = "UserID";

                // Don't override an existing desired field
                if (isset($data[$outputField]) && !$multi)
                    return;

                // Allow a lookup to set a field to null.
                if ($value === NULL || $value === '') {
                    $data[$outputField] = NULL;
                    return;
                }

                $lookupFieldValue = NULL;
                $lookupKey = "{$tableName}.{$columnLookup}";
                $lookupMethod = 'simple';

                if ($columnLookup == 'ID')
                    $lookupMethod = 'noop';

                // Sucks, but jsConnect always sends lowercase.
                if (strtolower($columnLookup) == $columnLookup) {
                    $columnLookup = ucfirst($columnLookup);
                }

                if ($lookupKey == 'User.ForeignID')
                    $lookupMethod = 'custom';

                // Only bother with exploding or casting if $Value isn't already an array.
                if (is_array($value)) {
                    // Since our $Value is an array, it should be handled as if it were multiple values.
                    $multi = true;
                } else {
                    if ($multi) {
                        $value = explode(',', $value);
                    } else {
                        $value = (array)$value;
                    }
                }

                foreach ($value as $multiValue) {
                    switch ($lookupMethod) {

                        // Noop lookup
                        case 'noop':
                            $lookupFieldValue = $multiValue;
                            break;

                        // Simple table.field lookup types
                        case 'simple':
                            $matchRecords = Gdn::SQL()->GetWhere($tableName, [
                                $columnLookup => $multiValue
                            ]);
                            if (!$matchRecords->NumRows()) {
                                $code = (Gdn::Request()->Get('callback', false) && C('Garden.AllowJSONP')) ? 200 : 404;
                                throw new Exception(self::notFoundString($fieldPrefix, $multiValue), $code);
                            }

                            if ($matchRecords->NumRows() > 1)
                                throw new Exception(sprintf('Multiple %ss found by %s for "%s".', T('User'), $columnLookup, $multiValue), 409);

                            $record = $matchRecords->FirstRow(DATASET_TYPE_ARRAY);
                            $lookupFieldValue = GetValue($lookupField, $record);
                            break;

                        // Custom lookup types
                        case 'custom':

                            // Special lookup for SSO users
                            if ($lookupKey == 'User.ForeignID') {
                                if (strpos($multiValue, ':') === false)
                                    throw new Exception("Malformed ForeignID object '{$multiValue}'. Should be '[provider key]:[foreign id]'.", 400);

                                $providerParts = explode(':', $multiValue, 2);
                                $providerKey = $providerParts[0];
                                $foreignID = $providerParts[1];

                                // Check if we have a provider by that key
                                $providerModel = new Gdn_AuthenticationProviderModel();
                                $provider = $providerModel->GetProviderByKey($providerKey);
                                if (!$provider)
                                    throw new Exception(self::notFoundString('Provider', $providerKey), 404);

                                // Check if we have an associated user for that ForeignID
                                $userAssociation = Gdn::Authenticator()->GetAssociation($foreignID, $providerKey, Gdn_Authenticator::KEY_TYPE_PROVIDER);
                                if (!$userAssociation)
                                    throw new Exception(self::notFoundString('User', $multiValue), 404);

                                $lookupFieldValue = GetValue($lookupField, $userAssociation);
                            }

                            break;
                    }

                    if (!is_null($lookupFieldValue)) {
                        if ($multi) {
                            if (!isset($data[$outputField])) $data[$outputField] = [];
                            if (!is_array($data[$outputField])) $data[$outputField] = [$data[$outputField]];
                            $data[$outputField][] = $lookupFieldValue;
                        } else {
                            $data[$outputField] = $lookupFieldValue;
                        }
                    }
                }

            } elseif (StringEndsWith($field, 'Category')) {
                // Translate a category column.
                $px = StringEndsWith($field, 'Category', true, true);
                $column = $px.'CategoryID';
                if (isset($data[$column]))
                    return;

                $category = CategoryModel::Categories($multiValue);
                if (!$category)
                    throw new Exception(self::notFoundString('Category', $multiValue), 404);

                $data[$column] = (string)$category['CategoryID'];
            }

        } catch (Exception $ex) {
            $errors[] = [$ex->getCode(), $ex->getMessage()];
        }

        return $errors;
    }

    protected static function notFoundString($code, $item) {
        return sprintf('%1$s "%2$s" not found.', T($code), $item);
    }

    /**
     * API Translation hook
     *
     * This method fires before the dispatcher inspects the request. It allows us
     * to translate incoming API requests according their version specifier.
     *
     * If no version is specified, or if the specified version cannot be loaded,
     * strip the version and directly pass the resulting URI without modification.
     *
     * @param Gdn_Dispatcher $Sender
     */
    public function gdn_dispatcher_appStartup_handler($Sender) {
        $IncomingRequest = Gdn::Request()->RequestURI();

        // Detect a versioned API call

        $MatchedAPI = preg_match('`^/?api/(v1)/(.+)`i', $IncomingRequest, $URI);

        if (!$MatchedAPI)
            return;

        $this->API = true;
        $Sender->API = true;
        $APIVersion = $URI[1];
        $APIRequest = $URI[2];

        // Check the version slug

        try {

            $ClassFile = "class.api.{$APIVersion}.php";
            $PluginInfo = Gdn::PluginManager()->GetPluginInfo('SimpleAPI');
            $PluginPath = $PluginInfo['PluginRoot'];
            $MapperFile = CombinePaths([$PluginPath, 'library', $ClassFile]);

            if (!file_exists($MapperFile)) throw new Exception('No such API Mapper');

            require_once($MapperFile);
            if (!class_exists('ApiMapper')) throw new Exception('API Mapper is not available after inclusion');

            $this->Mapper = new ApiMapper();

            $this->EventArguments['Mapper'] = &$this->Mapper;
            $this->FireEvent('Mapper');

            // Lookup the mapped replacement for this request
            $MappedURI = $this->Mapper->Map($APIRequest);
            if (!$MappedURI) throw new Exception('Unable to map request');

            // Apply the mapped replacement
            Gdn::Request()->WithURI($MappedURI);

            // Authenticate & prepare data
            $this->prepareAPI($Sender);

        } catch (Exception $Ex) {

            $Code = $HTTPCode = $Ex->getCode();
            $Message = Gdn_Controller::GetStatusMessage($HTTPCode);

            // Send a
            if ($Message == 'Unknown') {
                $HTTPCode = 500;
                $Message = Gdn_Controller::GetStatusMessage($HTTPCode);
            }

            header("Status: {$HTTPCode} {$Message}", true, $HTTPCode);

            // Set up data rray
            $Data = ['Code' => $Code, 'Exception' => $Ex->getMessage(), 'Class' => get_class($Ex)];

            if (Debug()) {
                if ($Trace = Trace()) {
                    // Clear passwords from the trace.
                    array_walk_recursive($Trace, function (&$Value, $Key) {
                        if (in_array(strtolower($Key), ['password'])) {
                            $Value = '***';
                        }
                    });

                    $Data['Trace'] = $Trace;
                }

                if (!is_a($Ex, 'Gdn_UserException'))
                    $Data['StackTrace'] = $Ex->getTraceAsString();
            }

            switch (Gdn::Request()->OutputFormat()) {
                case 'json':
                    header('Content-Type: application/json', true);
                    if ($Callback = Gdn::Request()->GetValueFrom(Gdn_Request::INPUT_GET, 'callback', false)) {
                        // This is a jsonp request.
                        exit($Callback.'('.json_encode($Data).');');
                    } else {
                        // This is a regular json request.
                        exit(json_encode($Data));
                    }
                    break;

                case 'xml':
                    header('Content-Type: text/xml', true);
                    array_map('htmlspecialchars', $Data);
                    exit("<Exception><Code>{$Data['Code']}</Code><Class>{$Data['Class']}</Class><Message>{$Data['Exception']}</Message></Exception>");
                    break;

                default:
                    header('Content-Type: text/plain', true);
                    exit($Ex->getMessage());
            }

        }

    }

    /**
     *
     * @param type $sender
     * @throws Exception
     */
    protected function prepareAPI($sender) {
        $accessToken = GetValue('access_token', $_GET, NULL);

        if ($accessToken !== NULL) {
            if ($accessToken === C('Plugins.SimpleAPI.AccessToken')) {
                // Check for only-https here because we don't want to check for https on json calls from javascript.
                $onlyHttps = C('Plugins.SimpleAPI.OnlyHttps');
                if ($onlyHttps && strcasecmp(Gdn::Request()->Scheme(), 'https') != 0) {
                    throw new Exception(T('You must access the API through https.'), 401);
                }

                $userID = C('Plugins.SimpleAPI.UserID');
                $user = false;
                if ($userID)
                    $user = Gdn::UserModel()->GetID($userID);
                if (!$user)
                    $userID = Gdn::UserModel()->GetSystemUserID();

                Gdn::Session()->Start($userID, false, false);
                Gdn::Session()->ValidateTransientKey(true);
            } else {
                if (!Gdn::Session()->IsValid())
                    throw new Exception(T('Invald Access Token'), 401);
            }
        }

        if (strcasecmp(GetValue('contenttype', $_GET, ''), 'json') == 0 || strpos(GetValue('CONTENT_TYPE', $_SERVER, NULL), 'json') !== false) {
            $post = file_get_contents('php://input');

            if ($post)
                $post = json_decode($post, true);
            else
                $post = [];
        } else {
            $post = Gdn::Request()->Post();
        }

        // Translate POST data
        self::translatePost($post);
        Gdn::Request()->SetRequestArguments(Gdn_Request::INPUT_POST, $post);
        $_POST = $post;

        // Translate GET data
        self::translateGet($_GET);
        Gdn::Request()->SetRequestArguments(Gdn_Request::INPUT_GET, $_GET);
        Trace(Gdn::Request()->Post(), 'post');
        Trace(Gdn::Request()->Get(), 'get');
    }

    /**
     * Add POST data to existing GET for reflection purposes
     *
     * @param Gdn_Dispatcher $sender
     */
    public function gdn_dispatcher_beforeReflect_handler($sender) {
        if (!$this->API) return;

        $request = $sender->EventArguments['Request'];
        $reflectionArguments = &$sender->EventArguments['Arguments'];
        $reflectionArguments = array_merge($reflectionArguments, $request->Post());
    }

    /**
     * Apply output filter
     *
     * @param Gdn_Controller $sender
     */
    public function Gdn_Controller_Finalize_Handler($sender) {
        if ($this->Mapper instanceof SimpleApiMapper)
            $this->Mapper->Filter($sender->EventArguments['Data']);
    }

    /**
     * API Settings
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_api_create($sender, $args) {
        $sender->Permission('Garden.Settings.Manage');

        if ($sender->Form->AuthenticatedPostBack()) {
            $save = [
                'Plugins.SimpleAPI.AccessToken' => $sender->Form->GetFormValue('AccessToken'),
                'Plugins.SimpleAPI.UserID' => NULL,
                'Plugins.SimpleAPI.OnlyHttps' => (bool)$sender->Form->GetFormValue('OnlyHttps')
            ];


            // Validate the settings.
            if (!ValidateRequired($sender->Form->GetFormValue('AccessToken'))) {
                $sender->Form->AddError('ValidateRequired', 'Access Token');
            }

            // Make sure the user exists.
            $username = $sender->Form->GetFormValue('Username');
            if (!ValidateRequired($username))
                $sender->Form->AddError('ValidateRequired', 'User');
            else {
                $user = Gdn::UserModel()->GetByUsername($username);
                if (!$user)
                    $sender->Form->AddError('@'.self::notFoundString('User', htmlspecialchars($username)));
                else
                    $save['Plugins.SimpleAPI.UserID'] = GetValue('UserID', $user);
            }

            if ($sender->Form->ErrorCount() == 0) {
                // Save the data.
                SaveToConfig($save);

                $sender->InformMessage('Your changes have been saved.');
            }
        } else {
            // Get the data.
            $data = [
                'AccessToken' => C('Plugins.SimpleAPI.AccessToken'),
                'UserID' => C('Plugins.SimpleAPI.UserID', Gdn::UserModel()->GetSystemUserID()),
                'OnlyHttps' => C('Plugins.SimpleAPI.OnlyHttps')];

            $user = Gdn::UserModel()->GetID($data['UserID'], DATASET_TYPE_ARRAY);
            if ($user) {
                $data['Username'] = $user['Name'];
            } else {
                $user = Gdn::UserModel()->GetID(Gdn::UserModel()->GetSystemUserID(), DATASET_TYPE_ARRAY);
                $data['Username'] = $user['Name'];
                $data['UserID'] = $user['UserID'];
            }

            $sender->Form->SetData($data);
        }

        $sender->SetData('Title', 'API Settings');
        $sender->AddSideMenu();
        $sender->Render('Settings', '', 'plugins/SimpleAPI');
    }

    /**
     * Adds "API" menu option to the Forum menu on the dashboard.
     *
     * @param Gdn_Controller $sender
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->AddLink('Site Settings', T('API'), 'settings/api', 'Garden.Settings.Manage', ['class' => 'nav-api']);
    }

    /**
     * Plugin setup
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Database structure
     */
    public function structure() {
        // Make sure the API user is set.
        $userID = C('Plugins.SimpleAPI.UserID');
        if (!$userID)
            $userID = Gdn::UserModel()->GetSystemUserID();
        $user = Gdn::UserModel()->GetID($userID, DATASET_TYPE_ARRAY);
        if (!$user)
            $userID = Gdn::UserModel()->GetSystemUserID();

        // Make sure the access token is set.
        $accessToken = C('Plugins.SimpleAPI.AccessToken');
        if (!$accessToken)
            $accessToken = md5(microtime());

        SaveToConfig([
            'Plugins.SimpleAPI.UserID' => $userID,
            'Plugins.SimpleAPI.AccessToken' => $accessToken
        ]);
    }

}
