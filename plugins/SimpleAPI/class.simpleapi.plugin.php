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

                if (stringEndsWith($fieldPrefix, 'User'))
                    $tableName = 'User';

                if (stringEndsWith($fieldPrefix, 'Users'))
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

                if (stringEndsWith($fieldPrefix, 'User'))
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
                            $matchRecords = Gdn::sql()->getWhere($tableName, [
                                $columnLookup => $multiValue
                            ]);
                            if (!$matchRecords->numRows()) {
                                $code = (Gdn::request()->get('callback', false) && c('Garden.AllowJSONP')) ? 200 : 404;
                                throw new Exception(self::notFoundString($fieldPrefix, $multiValue), $code);
                            }

                            if ($matchRecords->numRows() > 1)
                                throw new Exception(sprintf('Multiple %ss found by %s for "%s".', t('User'), $columnLookup, $multiValue), 409);

                            $record = $matchRecords->firstRow(DATASET_TYPE_ARRAY);
                            $lookupFieldValue = getValue($lookupField, $record);
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
                                $provider = $providerModel->getProviderByKey($providerKey);
                                if (!$provider)
                                    throw new Exception(self::notFoundString('Provider', $providerKey), 404);

                                // Check if we have an associated user for that ForeignID
                                $userAssociation = Gdn::authenticator()->getAssociation($foreignID, $providerKey, Gdn_Authenticator::KEY_TYPE_PROVIDER);
                                if (!$userAssociation)
                                    throw new Exception(self::notFoundString('User', $multiValue), 404);

                                $lookupFieldValue = getValue($lookupField, $userAssociation);
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

            } elseif (stringEndsWith($field, 'Category')) {
                // Translate a category column.
                $px = stringEndsWith($field, 'Category', true, true);
                $column = $px.'CategoryID';
                if (isset($data[$column]))
                    return;

                $category = CategoryModel::categories($multiValue);
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
        return sprintf('%1$s "%2$s" not found.', t($code), $item);
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
        $path = Gdn::request()->path();

        // Detect a versioned API call.
        if (!preg_match('`^/?api/(v[1-2])/(.+)`i', $path, $pathMatches)) {
            return;
        }

        try {
            $apiVersion = $pathMatches[1];
            $apiPath = $pathMatches[2];

            // Check the global access token for all API calls.
            $this->checkAccessToken();

            if ($apiVersion !== 'v1') {
                return;
            }

            $this->API = true;
            $Sender->API = true;

            // Check the version slug
            $ClassFile = "class.api.{$apiVersion}.php";
            $pluginInfo = Gdn::pluginManager()->getPluginInfo('SimpleAPI');
            $PluginPath = $pluginInfo['PluginRoot'];
            $MapperFile = combinePaths([$PluginPath, 'library', $ClassFile]);

            if (!file_exists($MapperFile)) throw new Exception('No such API Mapper');

            require_once($MapperFile);
            if (!class_exists('ApiMapper')) throw new Exception('API Mapper is not available after inclusion');

            $this->Mapper = new ApiMapper();

            $this->EventArguments['Mapper'] = &$this->Mapper;
            $this->fireEvent('Mapper');

            // Lookup the mapped replacement for this request
            $MappedURI = $this->Mapper->map($apiPath);
            if (!$MappedURI) throw new Exception('Unable to map request');

            // Apply the mapped replacement
            Gdn::request()->setURI($MappedURI);

            // Authenticate & prepare data
            $this->prepareAPI($Sender);

        } catch (Exception $Ex) {

            $Code = $HTTPCode = $Ex->getCode();
            $Message = Gdn_Controller::getStatusMessage($HTTPCode);

            // Send a
            if ($Message == 'Unknown') {
                $HTTPCode = 500;
                $Message = Gdn_Controller::getStatusMessage($HTTPCode);
            }

            header("Status: {$HTTPCode} {$Message}", true, $HTTPCode);

            // Set up data rray
            $Data = ['Code' => $Code, 'Exception' => $Ex->getMessage(), 'Class' => get_class($Ex)];

            if (debug()) {
                if ($Trace = trace()) {
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

            switch (Gdn::request()->outputFormat()) {
                case 'json':
                    header('Content-Type: application/json', true);
                    if ($Callback = Gdn::request()->getValueFrom(Gdn_Request::INPUT_GET, 'callback', false)) {
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
        if (strcasecmp(getValue('contenttype', $_GET, ''), 'json') == 0 || strpos(getValue('CONTENT_TYPE', $_SERVER, NULL), 'json') !== false) {
            $post = file_get_contents('php://input');

            if ($post)
                $post = json_decode($post, true);
            else
                $post = [];
        } else {
            $post = Gdn::request()->post();
        }

        // Translate POST data
        self::translatePost($post);
        Gdn::request()->setRequestArguments(Gdn_Request::INPUT_POST, $post);
        $_POST = $post;

        // Translate GET data
        self::translateGet($_GET);
        Gdn::request()->setRequestArguments(Gdn_Request::INPUT_GET, $_GET);
        trace(Gdn::request()->post(), 'post');
        trace(Gdn::request()->get(), 'get');
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
        $reflectionArguments = array_merge($reflectionArguments, $request->post());
    }

    /**
     * Apply output filter
     *
     * @param Gdn_Controller $sender
     */
    public function gdn_Controller_Finalize_Handler($sender) {
        if ($this->Mapper instanceof SimpleApiMapper)
            $this->Mapper->filter($sender->EventArguments['Data']);
    }

    /**
     * API Settings
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_api_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');

        if ($sender->Form->authenticatedPostBack()) {
            $save = [
                'Plugins.SimpleAPI.AccessToken' => $sender->Form->getFormValue('AccessToken'),
                'Plugins.SimpleAPI.UserID' => NULL,
                'Plugins.SimpleAPI.OnlyHttps' => (bool)$sender->Form->getFormValue('OnlyHttps')
            ];


            // Validate the settings.
            if (!validateRequired($sender->Form->getFormValue('AccessToken'))) {
                $sender->Form->addError('ValidateRequired', 'Access Token');
            }

            // Make sure the user exists.
            $username = $sender->Form->getFormValue('Username');
            if (!validateRequired($username))
                $sender->Form->addError('ValidateRequired', 'User');
            else {
                $user = Gdn::userModel()->getByUsername($username);
                if (!$user)
                    $sender->Form->addError('@'.self::notFoundString('User', htmlspecialchars($username)));
                else
                    $save['Plugins.SimpleAPI.UserID'] = getValue('UserID', $user);
            }

            if ($sender->Form->errorCount() == 0) {
                // Save the data.
                saveToConfig($save);

                $sender->informMessage('Your changes have been saved.');
            }
        } else {
            // Get the data.
            $data = [
                'AccessToken' => c('Plugins.SimpleAPI.AccessToken'),
                'UserID' => c('Plugins.SimpleAPI.UserID', Gdn::userModel()->getSystemUserID()),
                'OnlyHttps' => c('Plugins.SimpleAPI.OnlyHttps')];

            $user = Gdn::userModel()->getID($data['UserID'], DATASET_TYPE_ARRAY);
            if ($user) {
                $data['Username'] = $user['Name'];
            } else {
                $user = Gdn::userModel()->getID(Gdn::userModel()->getSystemUserID(), DATASET_TYPE_ARRAY);
                $data['Username'] = $user['Name'];
                $data['UserID'] = $user['UserID'];
            }

            $sender->Form->setData($data);
        }

        $sender->setData('Title', 'API v1 Settings');
        $sender->addSideMenu();
        $sender->render('Settings', '', 'plugins/SimpleAPI');
    }

    /**
     * Add the APIv1 menu item.
     *
     * @param DashboardNavModule $nav The menu to add the module to.
     */
    public function dashboardNavModule_init_handler(DashboardNavModule $nav) {
        $nav->addLinkToSectionIf(
            Gdn::session()->checkPermission('Garden.Settings.Manage'),
            'settings',
            t('API'),
            '/settings/api',
            'site-settings.apiv1',
            'nav-api',
            ['after' => 'security'],
            ['badge' => 'v1']
        );
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
        $userID = c('Plugins.SimpleAPI.UserID');
        if (!$userID)
            $userID = Gdn::userModel()->getSystemUserID();
        $user = Gdn::userModel()->getID($userID, DATASET_TYPE_ARRAY);
        if (!$user)
            $userID = Gdn::userModel()->getSystemUserID();

        // Make sure the access token is set.
        $accessToken = c('Plugins.SimpleAPI.AccessToken');
        if (!$accessToken)
            $accessToken = md5(microtime());

        saveToConfig([
            'Plugins.SimpleAPI.UserID' => $userID,
            'Plugins.SimpleAPI.AccessToken' => $accessToken
        ]);
    }

    /**
     * Check the user's access token.
     *
     * @throws \Exception Throws an exception when trying to to validate an access token through HTTP instead of HTTPS when that's not allowed.
     */
    private function checkAccessToken() {
        $accessToken = Gdn::request()->get('access_token', null);
        if ($accessToken === null && preg_match('`^Bearer\s+(.+)$`i', Gdn::request()->getHeader('Authorization'), $m)) {
            $accessToken = $m[1] ?:null;
        }

        if ($accessToken !== null) {
            if (hash_equals($accessToken, c('Plugins.SimpleAPI.AccessToken'))) {
                // Check for only-https here because we don't want to check for https on json calls from javascript.
                $onlyHttps = c('Plugins.SimpleAPI.OnlyHttps');
                if ($onlyHttps && strcasecmp(Gdn::request()->scheme(), 'https') !== 0) {
                    throw new Exception(t('You must access the API through https.'), 401);
                }

                $userID = c('Plugins.SimpleAPI.UserID');
                $user = false;
                if ($userID) {
                    $user = Gdn::userModel()->getID($userID);
                }
                if (!$user) {
                    $userID = Gdn::userModel()->getSystemUserID();
                }

                Gdn::session()->start($userID, false, false);
                Gdn::session()->validateTransientKey(true);
            } else {
                if (!Gdn::session()->isValid()) {
                    // Add a header to aid debugging.
                    safeHeader('X-WWW-Authenticate: error="invalid_token_v1"');
                }
            }
        }
    }

}
