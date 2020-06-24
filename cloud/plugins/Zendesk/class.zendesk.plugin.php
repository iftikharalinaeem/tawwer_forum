<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Class ZendeskPlugin.
 */
class ZendeskPlugin extends Gdn_Plugin {

    /**
     * Used for OAuth.
     *
     * @var string
     */
    const PROVIDER_KEY = 'Zendesk';

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * If status is set to this we will stop getting updates from Salesforce.
     *
     * @var string
     */
    protected $closedCaseStatusString = 'solved';

    /**
     * If time since last update from Zendesk is less then this; we wont check for update - saving api calls.
     *
     * @var int
     */
    protected $minimumTimeForUpdate = 600;

    /* @var Zendesk Zendesk Zendesk Object. */
    protected $zendesk;

    /** @var SsoUtils */
    private $ssoUtils;

    /**
     * Set AccessToken to be used.
     *
     * @param SsoUtils $ssoUtils
     */
    public function __construct(SsoUtils $ssoUtils) {
        parent::__construct();

        $this->accessToken = getValueR('Attributes.'.self::PROVIDER_KEY.'.AccessToken', Gdn::session()->User);
        if (!$this->accessToken) {
            $this->accessToken = c('Plugins.Zendesk.GlobalLogin.AccessToken');
        }

        $this->ssoUtils = $ssoUtils;
    }

    /**
     * Writes and updates discussion attachments.
     *
     * @param DiscussionController $sender Sending controller.
     * @param array $args Event Arguments.
     */
    public function discussioncontroller_afterDiscussionBody_handler($sender, $args) {
        $this->updateAttachments($sender, $args, 'Discussion');
    }

    /**
     * Writes and updates discussion attachments.
     *
     * @param DiscussionController $sender Sending controller.
     * @param array $args Event Arguments.
     */
    public function discussionController_afterCommentBody_handler($sender, $args) {
        $this->updateAttachments($sender, $args, 'Comment');
    }

    /**
     * Writes and updates attachments for comments and discussions.
     *
     * @param DiscussionController|Commentocntroller $sender Sending controller.
     * @param array $args Event arguments.
     * @param string $type Record type.
     *
     * @throws Gdn_UserException If Errors.
     */
    protected function updateAttachments($sender, $args, $type) {
        if ($type == 'Discussion') {
            $content = 'Discussion';
        } elseif ($type == 'Comment') {
            $content = 'Comment';
        } else {
            throw new Gdn_UserException('Invalid Content');
        }
        // Signed in users only.
        if (!Gdn::session()->isValid()) {
            return;
        }

        if (!Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            return;
        }
        $attachments = val('Attachments', $args[$content]);
        if ($attachments) {
            foreach ($args[$content]->Attachments as $attachment) {
                if ($attachment['Type'] == 'zendesk-ticket') {
                    $this->updateAttachment($attachment);
                }
            }
        }

    }

    /**
     * Check to see if attachment needs to be updated.
     *
     * @param array $attachment Attachment Data - see AttachmentModel.
     *
     * @see    AttachmentModel
     *
     * @return bool
     */
    protected function isToBeUpdated($attachment) {
        if (val('Status', $attachment) == $this->closedCaseStatusString) {
            trace("Ticket {$this->closedCaseStatusString}.  Not checking for update.");
            return false;
        }
        $timeDiff = time() - strtotime($attachment['DateUpdated']);
        if ($timeDiff < $this->minimumTimeForUpdate) {
            trace("Not Checking For Update: $timeDiff seconds since last update");
            return false;
        }
        if (isset($attachment['LastModifiedDate'])) {
            $timeDiff = time() - strtotime($attachment['LastModifiedDate']);
            if ($timeDiff < $this->minimumTimeForUpdate) {
                trace("Not Checking For Update: $timeDiff seconds since last update");
                return false;
            }
        }
        return true;
    }

    /**
     * Update the Attachment.
     *
     * @param array $attachment Attachment.
     *
     * @see    AttachmentModel
     *
     * @return bool
     */
    protected function updateAttachment($attachment) {
        if (!$this->isConfigured() || !$this->isConnected()) {
            return;
        }
        if ($this->isToBeUpdated($attachment)) {
            try {
                $this->setZendesk();
                $ticket = $this->zendesk->getTicket($attachment['SourceID']);
            } catch (Gdn_UserException $e) {
                if ($e->getCode() == 404) {
                    $attachment['Error'] = 'This Ticket has been deleted from Zendesk';
                    $attachmentModel = AttachmentModel::instance();
                    $attachment['DateUpdated'] = Gdn_Format::toDateTime();
                    $attachmentModel->save($attachment);
                    return false;
                }
            }

            $attachment['Status'] = $ticket['status'];
            $attachment['LastModifiedDate'] = $ticket['updated_at'];
            $attachment['DateUpdated'] = Gdn_Format::toDateTime();

            $attachmentModel = AttachmentModel::instance();
            $attachmentModel->save($attachment);
            return true;
        }
        return false;
    }

    /**
     * Creates the Virtual Zendesk Controller and adds Link to SideMenu in the dashboard.
     *
     * @param PluginController $sender Sending controller.
     */
    public function pluginController_zendesk_create($sender) {

        $sender->permission('Garden.Settings.Manage');
        $sender->title('Zendesk');
        $sender->addSideMenu('plugin/zendesk');
        $sender->Form = new Gdn_Form();
        $this->dispatch($sender, $sender->RequestArgs);
    }

    /**
     * Dashboard Settings.
     *
     * Default method of virtual Zendesk controller.
     *
     * @param Gdn_Controller $sender Sending controller.
     */
    public function controller_index($sender) {

        $sender->addCssFile('admin.css');

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(['Url', 'ApplicationID', 'Secret']);

        // Set the model on the form.
        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $sender->Form->setData($configurationModel->Data);
        } else {
            $formValues = $sender->Form->formValues();
            if ($sender->Form->isPostBack()) {
                $sender->Form->validateRule(
                    'Url',
                    'function:ValidateRequired',
                    'Url is required'
                );
                $sender->Form->validateRule(
                    'ApplicationID',
                    'function:ValidateRequired',
                    'Unique Identifier is required'
                );
                $sender->Form->validateRule('Secret', 'function:ValidateRequired', 'Secret is required');


                if ($sender->Form->errorCount() == 0) {
                    saveToConfig('Plugins.Zendesk.ApplicationID', trim($formValues['ApplicationID']));
                    saveToConfig('Plugins.Zendesk.Secret', trim($formValues['Secret']));
                    saveToConfig('Plugins.Zendesk.Url', trim($formValues['Url']));
                    $sender->informMessage(t("Your changes have been saved."));
                } else {
                    $sender->informMessage(t("Error saving settings to config."));
                }
            }

        }

        $sender->Form->setValue('Url', c('Plugins.Zendesk.Url'));
        $sender->Form->setValue('ApplicationID', c('Plugins.Zendesk.ApplicationID'));
        $sender->Form->setValue('Secret', c('Plugins.Zendesk.Secret'));
        $sender->setData([
            'GlobalLoginEnabled' => c('Plugins.Zendesk.GlobalLogin.Enabled'),
            'GlobalLoginConnected' => c('Plugins.Zendesk.GlobalLogin.AccessToken'),
            'ToggleUrl' => url('/plugin/zendesk/toggle/'.Gdn::session()->transientKey())
        ]);
        if (c('Plugins.Zendesk.GlobalLogin.Enabled') && c('Plugins.Zendesk.GlobalLogin.AccessToken')) {
            $this->setZendesk(c('Plugins.Zendesk.GlobalLogin.AccessToken'));
            $globalLoginProfile = $this->zendesk->getProfile();
            if ($globalLoginProfile['error']) {
                $sender->setData('GlobalLoginError',  $globalLoginProfile);
                $sender->setData('GlobalLoginConnected', false);
            } else {
                $sender->setData('GlobalLoginProfile', $globalLoginProfile);
            }

        }

        $sender->render($this->getView('dashboard.php'));
    }


    /**
     * Adds Option to Create Ticket to Discussion Gear.
     *
     * Options will be removed if Discussion has already been submitted as a Ticket
     *
     * @param DiscussionController $sender Sending controller.
     * @param array $args Event arguments.
     */
    public function discussionController_discussionOptions_handler($sender, $args) {
        $this->addOptions($sender, $args, 'Discussion');
    }

    /**
     * Adds Option to Create Ticket to Comment Gear.
     *
     * Option Will be removed if comment has already been submitted as a Ticket
     *
     * @param CommentController $sender Sending controller.
     * @param array $args Event arguments.
     */
    public function discussionController_commentOptions_handler($sender, $args) {
        $this->addOptions($sender, $args, 'Comment');
    }

    /**
     * Adds options to comments and discussions.
     *
     * @param DiscussionConoller|CommentContrller $sender Sending controller.
     * @param array $args Event arguments.
     * @param string $type Record type.
     *
     * @throws Gdn_UserException If Error.
     */
    protected function addOptions($sender, $args, $type) {

        if (!$this->isConfigured()) {
            return;
        }

        // Signed in users only. No guest reporting!
        if (!Gdn::session()->UserID) {
            return;
        }

        if (!Gdn::session()->checkPermission('Garden.Staff.Allow')) {
            return;
        }

        if ($type == 'Discussion') {
            $content = 'Discussion';
            $contentID = $args[$content]->DiscussionID;
        } elseif ($type == 'Comment') {
            $content = 'Comment';
            $contentID = $args[$content]->CommentID;
        } else {
            throw new Gdn_UserException('Invalid Content Type');
        }
        $elementAuthorID = $args[$content]->InsertUserID;


        if (!c('Plugins.Zendesk.AllowTicketForSelf', false) && $elementAuthorID == Gdn::session()->UserID) {
            //no need to create support tickets for your self
            return;
        }

        $linkText = 'Create Zendesk Ticket';
        if (isset($args[$content.'Options'])) {
            $args[$content.'Options']['Zendesk'] = [
                'Label' => t($linkText),
                'Url' => "/discussion/zendesk/".strtolower($content)."/$contentID",
                'Class' => 'Popup'
            ];
        }
        //remove create Create already created
        $attachments = val('Attachments', $args[$content], []);
        foreach ($attachments as $attachment) {
            if ($attachment['Type'] == 'zendesk-ticket') {
                unset($args[$content.'Options']['Zendesk']);
            }
        }
    }

    /**
     * Handle Zendesk popup to create ticket in discussions.
     *
     * @param DiscussionController $sender Sending controller.
     *
     * @throws Exception If Errors.
     */
    public function discussionController_zendesk_create($sender) {
        // Signed in users only.
        if (!($userID = Gdn::session()->UserID)) {
            return;
        }

        if (!$this->isConnected()) {
            $loginUrl = url('/profile/connections');
            if (c('Plugins.Zendesk.GlobalLogin.Enabled')) {
                $loginUrl = url('/plugin/zendesk#global-login');
            }
            $sender->setData('LoginUrl', $loginUrl);
            $sender->render('userlogin', '', 'plugins/Zendesk');
            return;
        }

        $userName = Gdn::session()->User->Name;

        $arguments = $sender->RequestArgs;
        if (sizeof($arguments) != 2) {
            throw new Exception('Invalid Request Url');
        }
        $context = $arguments[0];
        $contentID = $arguments[1];
        $sender->Form = new Gdn_Form();

        if ($context == 'comment') {
            $commentModel = new CommentModel();
            $content = $commentModel->getID($contentID);
            $discussionID = $content->DiscussionID;
            $url = commentUrl($content);

            $discussion = $sender->DiscussionModel->getID($discussionID);
            $ticketTitle = $discussion->Name;

        } else {
            $discussionID = $contentID;
            $content = $sender->DiscussionModel->getID($contentID);
            $ticketTitle = $content->Name;
            $url = discussionUrl($content, 1);

        }

        // Join in attachments
        $attachmentModel = AttachmentModel::instance();
        $attachmentModel->joinAttachments($content);

        if ($sender->Form->isPostBack() && $sender->Form->authenticatedPostBack() === true) {
            $sender->Form->validateRule('Title', 'function:ValidateRequired', 'Title is required');
            $sender->Form->validateRule('Body', 'function:ValidateRequired', 'Body is required');

            if ($sender->Form->errorCount() == 0) {
                $formValues = $sender->Form->formValues();
                $body = $formValues['Body'];
                $ticketUrl = anchor($url, $url);
                $body .= "<br><br>"."This ticket was generated from: ".$ticketUrl;
                $this->setZendesk();
                $ticketID = $this->zendesk->createTicket(
                    $formValues['Title'],
                    $body,
                    $this->zendesk->createRequester(
                        $formValues['InsertName'],
                        $formValues['InsertEmail']
                    ),
                    [
                        'custom_fields' => [
                            'DiscussionID' => $discussionID,
                            'ForumUrl' => $url
                        ]]
                );

                if ($ticketID > 0) {
                    //Save to Attachments
                    $attachmentModel->save([
                        'Type' => 'zendesk-ticket',
                        'ForeignID' => $attachmentModel->rowID($content),
                        'ForeignUserID' => $content->InsertUserID,
                        'Source' => 'zendesk',
                        'SourceID' => $ticketID,
                        'SourceURL' => c('Plugins.Zendesk.Url').'/agent/#/tickets/'.$ticketID,
                        'Status' => 'open',
                        'LastModifiedDate' => Gdn_Format::toDateTime()
                    ]);
                    $sender->informMessage('Zendesk Ticket Created');
                    $sender->jsonTarget('', $url, 'Redirect');

                } else {
                    $sender->informMessage(t("Error creating ticket with Zendesk"));
                }
            }
        }

        $sender->Form->addHidden('Url', $url);
        $sender->Form->addHidden('UserId', $userID);
        $sender->Form->addHidden('UserName', $userName);
        $sender->Form->addHidden('InsertName', $content->InsertName);
        $sender->Form->addHidden('InsertEmail', $content->InsertEmail);

        $sender->Form->setValue('Title', $ticketTitle);
        $content = Gdn_Format::to($content->Body, $content->Format);
        $sender->Form->setValue('Body', $content);

        $sender->setData('Data', [
            'DiscussionID' => $discussionID,
            'UserID' => $userID,
            'UserName' => $userName,
            'Body' => $content->Body,
            'InsertName' => $content->InsertName,
            'InsertEmail' => $content->InsertEmail,
            'Title' => $ticketTitle,
        ]);

        $sender->render('createticket', '', 'plugins/Zendesk');
    }

    /**
     * Enable/Disable Global Login.
     *
     * @param Controller $sender Sending controller.
     */
    public function controller_toggle($sender) {
        // Enable/Disable
        if (Gdn::session()->validateTransientKey(val(1, $sender->RequestArgs))) {
            if (c('Plugins.Zendesk.GlobalLogin.Enabled')) {
                $this->disable();
                redirectTo('/plugin/zendesk');
            }
            redirectTo('/plugin/zendesk/authorize');

        }
    }

    /**
     * Disable Global Login.
     */
    protected function disable() {
        removeFromConfig('Plugins.Zendesk.GlobalLogin.Enabled');
        removeFromConfig('Plugins.Zendesk.GlobalLogin.AccessToken');
    }

    /**
     * Setup to plugin.
     */
    public function setup() {

        saveToConfig('Garden.AttachmentsEnabled', true);

        $error = '';
        if (!function_exists('curl_init')) {
            $error = concatSep("\n", $error, 'This plugin requires curl.');
        }
        if ($error) {
            throw new Gdn_UserException($error, 400);
        }
        // Save the provider type.
        Gdn::sql()->replace(
            'UserAuthenticationProvider',
            [
                'AuthenticationSchemeAlias' => 'zendesk',
                'URL' => '...',
                'AssociationSecret' => '...',
                'AssociationHashMethod' => '...'
            ],
            ['AuthenticationKey' => self::PROVIDER_KEY],
            true
        );
        Gdn::permissionModel()->define(['Garden.Staff.Allow' => 'Garden.Moderation.Manage']);
        $this->setupConfig();

    }

    /**
     * Setup Config Settings.
     */
    protected function setupConfig() {
        saveToConfig('Garden.AttachmentsEnabled', true);
        $configSettings = [
            'Url',
            'ApplicationID',
            'Secret'
        ];
        //prevents resetting any previous values
        foreach ($configSettings as $configSetting) {
            if (!c('Plugins.Zendesk.'.$configSetting)) {
                saveToConfig('Plugins.Zendesk.'.$configSetting, '');
            }
        }
    }

    //OAuth Methods

    /**
     * OAuth Method.  Gets the authorize Uri.
     *
     * @param bool|string $redirectUri Redirect Url.
     *
     * @throws Gdn_UserException If Errors.
     * @return string Authorize URL Authorize Url.
     */
    public function authorizeUri($redirectUri = false) {
        if (!self::isConfigured()) {
            throw new Gdn_UserException('Zendesk is not configured yet');
        }
        $appID = c('Plugins.Zendesk.ApplicationID');
        if (!$redirectUri) {
            $redirectUri = self::redirectUri();
        }

        // Get a state token.
        $stateToken = $this->ssoUtils->getStateToken();

        $query = [
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'client_id' => $appID,
            'scope' => 'read write',
            'state' => json_encode([
                'token' => $stateToken,
            ]),

        ];
        return c('Plugins.Zendesk.Url').'/oauth/authorizations/new?'.http_build_query($query);
    }

    /**
     * Used in the OAuth Process.
     *
     * @param null|string $newValue A different redirect url.
     *
     * @return string Redirect Url.
     */
    public static function redirectUri($newValue = null) {
        if ($newValue !== null) {
            $redirectUri = $newValue;
        } else {
            $redirectUri = Gdn::request()->url('/profile/zendeskconnect', true, true);
        }
        return $redirectUri;
    }

    /**
     * OAuth Method.  Sends request to zendesk to validate tokens.
     *
     * @param string $code OAuth Code.
     * @param string $redirectUri Redirect Uri.
     *
     * @return string Response
     * @throws Gdn_UserException If error.
     */
    public static function getTokens($code, $redirectUri) {
        if (!self::isConfigured()) {
            throw new Gdn_UserException('Zendesk is not configured yet');
        }
        $post = [
            'grant_type' => 'authorization_code',
            'client_id' => c('Plugins.Zendesk.ApplicationID'),
            'client_secret' => c('Plugins.Zendesk.Secret'),
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'scope' => 'read'
        ];
        $proxy = new ProxyRequest();
        $requestOptions = [
            'URL' => c('Plugins.Zendesk.Url').'/oauth/tokens.json',
            'Method' => 'POST',
        ];
        $response = $proxy->request($requestOptions, $post);

        if (strpos($proxy->ContentType, 'application/json') !== false) {
            $response = json_decode($response);
        }
        if (!$response) {
            Logger::log(Logger::DEBUG, 'zendesk_token_error', [
                'error' => 'Could not parse Zendesk API response',
                'request_options' => $requestOptions,
                'request_data' => $post,
            ]);
            throw new Gdn_UserException('Could not parse Zendesk API response');
        }
        if (isset($response->error)) {
            Logger::log(Logger::DEBUG, 'zendesk_token_error', [
                'error' => 'Error Communicating with Zendesk API',
                'request_options' => $requestOptions,
                'request_data' => $post,
                'response' => $response,
            ]);
            throw new Gdn_UserException('Error Communicating with Zendesk API: '.$response->error_description);
        }

        return $response;

    }

    /**
     * OAuth Method.
     *
     * @return string $Url
     */
    public static function profileConnectUrl() {
        return Gdn::request()->url('/profile/zendeskconnect', true, true);
    }

    /**
     * OAuth Method.
     *
     * @return bool
     */
    public static function isConfigured() {
        $url = c('Plugins.Zendesk.Url');
        $appID = c('Plugins.Zendesk.ApplicationID');
        $secret = c('Plugins.Zendesk.Secret');
        if (!$appID || !$secret || !$url) {
            return false;
        }
        return true;
    }

    /**
     * OAuth Method.
     *
     * @return bool
     */
    public function isConnected() {
        if ($this->accessToken) {
            return true;
        }
        return false;
    }


    /**
     * Profile Social Connections.
     *
     * @param Controller $sender Sending controller.
     * @param array $args Event arguments.
     */
    public function base_getConnections_handler($sender, $args) {
        if (!$this->isConfigured()) {
            return;
        }
        //Staff Only
        if (!Gdn::session()->checkPermission('Garden.Staff.Allow')) {
            return;
        }
        $sf = getValueR('User.Attributes.'.self::PROVIDER_KEY, $args);
        trace($sf);
        $profile = getValueR('User.Attributes.'.self::PROVIDER_KEY.'.Profile', $args);
        $sender->Data["Connections"][self::PROVIDER_KEY] = [
            'Icon' => $this->getWebResource('zendesk.png', '/'),
            'Name' => self::PROVIDER_KEY,
            'ProviderKey' => self::PROVIDER_KEY,
            'ConnectUrl' => $this->authorizeUri(self::profileConnectUrl()),
            'Profile' => [
                'Name' => val('fullname', $profile),
                'Photo' => val('photo', $profile)
            ]
        ];
    }

    /**
     * OAUth Method.  Code is Exchanged for Token.
     *
     * Token is stored for later use.  Token does not expire.  It can be revoked from Zendesk
     *
     * @param ProfileController $sender Sending controller.
     * @param string $userReference User Reference.
     * @param string $username Username.
     * @param bool|string $code Authorize Code.
     */
    public function profileController_zendeskConnect_create(
        $sender,
        $userReference = '',
        $username = '',
        $code = false
    ) {
        $sender->permission('Garden.SignIn.Allow');

        $state = json_decode(Gdn::request()->get('state', ''), true);
        $suppliedStateToken = val('token', $state);
        $this->ssoUtils->verifyStateToken('zendeskConnect', $suppliedStateToken);

        $sender->getUserInfo($userReference, $username, '', true);
        $sender->_SetBreadcrumbs(t('Connections'), userUrl($sender->User, '', 'connections'));

        try {
            $tokens = $this->getTokens($code, self::profileConnectUrl());
        } catch (Gdn_UserException $e) {
            $attributes = [
                'AccessToken' => null,
                'Profile' => null,
            ];
            Gdn::userModel()->saveAttribute($sender->User->UserID, self::PROVIDER_KEY, $attributes);
            $message = $e->getMessage();
            Gdn::dispatcher()->passData('Exception', htmlspecialchars($message))
                ->dispatch('home/error');
            return;
        }
        $this->accessToken = val('access_token', $tokens);
        $this->setZendesk($this->accessToken);
        $profile = $this->zendesk->getProfile();

        if ($profile['error']) {
            throw new Gdn_UserException($profile['message'], $profile['code']);
        }

        Gdn::userModel()->saveAuthentication(
            [
                'UserID' => $sender->User->UserID,
                'Provider' => self::PROVIDER_KEY,
                'UniqueID' => $profile['id']
            ]
        );
        $attributes = [
            'AccessToken' => $this->accessToken,
            'Profile' => $profile,
        ];
        Gdn::userModel()->saveAttribute($sender->User->UserID, self::PROVIDER_KEY, $attributes);
        $this->EventArguments['Provider'] = self::PROVIDER_KEY;
        $this->EventArguments['User'] = $sender->User;
        $this->fireEvent('AfterConnection');

        redirectTo(userUrl($sender->User, '', 'connections'));
    }

    /**
     * OAuth Method. Redirects user to request access.
     */
    public function controller_authorize() {
        redirectTo($this->authorizeUri(self::globalConnectUrl()), 302, false);
    }

    /**
     * OAuth Method. Handles the redirect from Zendesk and stores AccessToken.
     *
     * @throws Gdn_UserException If Error.
     */
    public function controller_connect() {
        $code = Gdn::request()->get('code');
        $tokens = $this->getTokens($code, self::globalConnectUrl());
        $accessToken = val('access_token', $tokens);

        if ($accessToken) {
            saveToConfig([
                'Plugins.Zendesk.GlobalLogin.Enabled' => true,
                'Plugins.Zendesk.GlobalLogin.AccessToken' => $accessToken
            ]);
        } else {
            removeFromConfig([
                'Plugins.Zendesk.GlobalLogin.Enabled' => true,
                'Plugins.Zendesk.GlobalLogin.AccessToken' => $accessToken
            ]);
            throw new Gdn_UserException('Error Connecting to Zendesk');
        }
        redirectTo('/plugin/zendesk');

    }

    /**
     * OAuth Method.
     *
     * @return string
     */
    public static function globalConnectUrl() {
        return Gdn::request()->url('/plugin/zendesk/connect', true, true);
    }

    //end of OAUTH


    /**
     * Lazy Load Zendesk object.
     */
    protected function setZendesk($accessToken = null) {
        if ($accessToken == null) {
            $accessToken = $this->accessToken;
        }
        if (!$this->zendesk) {
            $this->zendesk = new Zendesk(
                new ZendeskCurlRequest(),
                c('Plugins.Zendesk.Url'),
                new ZendeskOAuthTokenStrategy($accessToken)
            );
        }
    }

    /**
     * Add attachment views.
     *
     * @param DiscussionController $Sender Sending Controller.
     */
    public function discussionController_fetchAttachmentViews_handler($Sender) {
        require_once $Sender->fetchViewLocation('attachment', '', 'plugins/Zendesk');
    }
}
