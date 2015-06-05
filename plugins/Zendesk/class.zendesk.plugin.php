<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

// Define the plugin:
$PluginInfo['Zendesk'] = array(
    'Name' => 'Zendesk',
    'Description' => "Allow staff users to create tickets and cases from discussions and comments.",
    'Version' => '0.0.4-beta',
    'RequiredApplications' => array('Vanilla' => '2.1.18'),
    'SettingsUrl' => '/dashboard/plugin/zendesk',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => true,
    'Author' => 'John Ashton',
    'AuthorEmail' => 'john@vanillaforums.com',
    'AuthorUrl' => 'http://www.github.com/John0x00',
    'SocialConnect' => false
);

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
     * @var int
     */
    protected $minimumTimeForUpdate = 600;

    /* @var Zendesk Zendesk Zendesk Object. */
    protected $zendesk;

    /**
     * Set AccessToken to be used.
     */
    public function __construct() {
        parent::__construct();

        $this->accessToken = GetValueR('Attributes.' . self::PROVIDER_KEY . '.AccessToken', Gdn::Session()->User);
        if (!$this->accessToken) {
            $this->accessToken = C('Plugins.Zendesk.GlobalLogin.AccessToken');
        }

    }

    /**
     * Writes and updates discussion attachments.
     *
     * @param DiscussionController $Sender Sending controller.
     * @param array $Args Event Arguments.
     */
    public function discussioncontroller_afterDiscussionBody_handler($Sender, $Args) {
        $this->updateAttachments($Sender, $Args, 'Discussion');
    }

    /**
     * Writes and updates discussion attachments.
     *
     * @param DiscussionController $Sender Sending controller.
     * @param array $Args Event Arguments.
     */
    public function discussionController_afterCommentBody_handler($Sender, $Args) {
        $this->updateAttachments($Sender, $Args, 'Comment');
    }

    /**
     * Writes and updates attachments for comments and discussions.
     *
     * @param DiscussionController|Commentocntroller $Sender Sending controller.
     * @param array $Args Event arguments.
     * @param string $Type Record type.
     *
     * @throws Gdn_UserException If Errors.
     */
    protected function updateAttachments($Sender, $Args, $Type) {
        if ($Type == 'Discussion') {
            $Content = 'Discussion';
        } elseif ($Type == 'Comment') {
            $Content = 'Comment';
        } else {
            throw new Gdn_UserException('Invalid Content');
        }
        // Signed in users only.
        if (!Gdn::Session()->IsValid()) {
            return;
        }

        if (!Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
            return;
        }
        $Attachments = GetValue('Attachments', $Args[$Content]);
        if ($Attachments) {
            foreach ($Args[$Content]->Attachments as $Attachment) {
                if ($Attachment['Type'] == 'zendesk-ticket') {
                    $this->UpdateAttachment($Attachment);
                }
            }
        }

    }

    /**
     * Check to see if attachment needs to be updated.
     *
     * @param array $Attachment Attachment Data - see AttachmentModel.
     *
     * @see    AttachmentModel
     *
     * @return bool
     */
    protected function isToBeUpdated($Attachment) {
        if (GetValue('Status', $Attachment) == $this->closedCaseStatusString) {
            Trace("Ticket {$this->closedCaseStatusString}.  Not checking for update.");
            return false;
        }
        $TimeDiff = time() - strtotime($Attachment['DateUpdated']);
        if ($TimeDiff < $this->minimumTimeForUpdate) {
            Trace("Not Checking For Update: $TimeDiff seconds since last update");
            return false;
        }
        if (isset($Attachment['LastModifiedDate'])) {
            $TimeDiff = time() - strtotime($Attachment['LastModifiedDate']);
            if ($TimeDiff < $this->minimumTimeForUpdate) {
                Trace("Not Checking For Update: $TimeDiff seconds since last update");
                return false;
            }
        }
        return true;
    }

    /**
     * Update the Attachment.
     *
     * @param array $Attachment Attachment.
     *
     * @see    AttachmentModel
     *
     * @return bool
     */
    protected function updateAttachment($Attachment) {
        if (!$this->isConfigured() || !$this->isConnected()) {
            return;
        }
        if ($this->IsToBeUpdated($Attachment)) {
            try {
                $this->setZendesk();
                $Ticket = $this->zendesk->getTicket($Attachment['SourceID']);
            } catch (Gdn_UserException $e) {
                if ($e->getCode() == 404) {
                    $Attachment['Error'] = 'This Ticket has been deleted from Zendesk';
                    $AttachmentModel = AttachmentModel::Instance();
                    $Attachment['DateUpdated'] = Gdn_Format::ToDateTime();
                    $AttachmentModel->Save($Attachment);
                    return false;
                }
            }

            $Attachment['Status'] = $Ticket['status'];
            $Attachment['LastModifiedDate'] = $Ticket['updated_at'];
            $Attachment['DateUpdated'] = Gdn_Format::ToDateTime();

            $AttachmentModel = AttachmentModel::Instance();
            $AttachmentModel->Save($Attachment);
            return true;
        }
        return false;
    }

    /**
     * Creates the Virtual Zendesk Controller and adds Link to SideMenu in the dashboard.
     *
     * @param PluginController $Sender Sending controller.
     */
    public function pluginController_zendesk_create($Sender) {

        $Sender->Permission('Garden.Settings.Manage');
        $Sender->Title('Zendesk');
        $Sender->AddSideMenu('plugin/zendesk');
        $Sender->Form = new Gdn_Form();
        $this->Dispatch($Sender, $Sender->RequestArgs);
    }

    /**
     * Dashboard Settings.
     *
     * Default method of virtual Zendesk controller.
     *
     * @param Gdn_Controller $Sender Sending controller.
     */
    public function controller_index($Sender) {

        $Sender->AddCssFile('admin.css');

        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->SetField(array('Url', 'ApplicationID', 'Secret'));

        // Set the model on the form.
        $Sender->Form->SetModel($ConfigurationModel);

        // If seeing the form for the first time...
        if ($Sender->Form->AuthenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $Sender->Form->SetData($ConfigurationModel->Data);
        } else {
            $FormValues = $Sender->Form->FormValues();
            if ($Sender->Form->IsPostBack()) {
                $Sender->Form->ValidateRule(
                    'Url',
                    'function:ValidateRequired',
                    'Url is required'
                );
                $Sender->Form->ValidateRule(
                    'ApplicationID',
                    'function:ValidateRequired',
                    'Unique Identifier is required'
                );
                $Sender->Form->ValidateRule('Secret', 'function:ValidateRequired', 'Secret is required');


                if ($Sender->Form->ErrorCount() == 0) {
                    SaveToConfig('Plugins.Zendesk.ApplicationID', trim($FormValues['ApplicationID']));
                    SaveToConfig('Plugins.Zendesk.Secret', trim($FormValues['Secret']));
                    SaveToConfig('Plugins.Zendesk.Url', trim($FormValues['Url']));
                    $Sender->InformMessage(T("Your changes have been saved."));
                } else {
                    $Sender->InformMessage(T("Error saving settings to config."));
                }
            }

        }

        $Sender->Form->SetValue('Url', C('Plugins.Zendesk.Url'));
        $Sender->Form->SetValue('ApplicationID', C('Plugins.Zendesk.ApplicationID'));
        $Sender->Form->SetValue('Secret', C('Plugins.Zendesk.Secret'));
        $Sender->SetData(array(
            'GlobalLoginEnabled' => C('Plugins.Zendesk.GlobalLogin.Enabled'),
            'GlobalLoginConnected' => C('Plugins.Zendesk.GlobalLogin.AccessToken'),
            'ToggleUrl' => Url('/plugin/zendesk/toggle/' . Gdn::Session()->TransientKey())
        ));
        if (C('Plugins.Zendesk.GlobalLogin.Enabled') && C('Plugins.Zendesk.GlobalLogin.AccessToken')) {
            $this->setZendesk(C('Plugins.Zendesk.GlobalLogin.AccessToken'));
            $globalLoginProfile = $this->zendesk->getProfile();
            $Sender->SetData('GlobalLoginProfile', $globalLoginProfile);
        }

        $Sender->Render($this->GetView('dashboard.php'));
    }


    /**
     * Adds Option to Create Ticket to Discussion Gear.
     *
     * Options will be removed if Discussion has already been submitted as a Ticket
     *
     * @param DiscussionController $Sender Sending controller.
     * @param array $Args Event arguments.
     */
    public function discussionController_discussionOptions_handler($Sender, $Args) {
        $this->addOptions($Sender, $Args, 'Discussion');
    }

    /**
     * Adds Option to Create Ticket to Comment Gear.
     *
     * Option Will be removed if comment has already been submitted as a Ticket
     *
     * @param CommentController $Sender Sending controller.
     * @param array $Args Event arguments.
     */
    public function discussionController_commentOptions_handler($Sender, $Args) {
        $this->addOptions($Sender, $Args, 'Comment');
    }

    /**
     * Adds options to comments and discussions.
     *
     * @param DiscussionConoller|CommentContrller $Sender Sending controller.
     * @param array $Args Event arguments.
     * @param string $Type Record type.
     *
     * @throws Gdn_UserException If Error.
     */
    protected function addOptions($Sender, $Args, $Type) {

        if (!$this->isConfigured()) {
            return;
        }

        // Signed in users only. No guest reporting!
        if (!Gdn::Session()->UserID) {
            return;
        }

        if (!Gdn::Session()->CheckPermission('Garden.Staff.Allow')) {
            return;
        }

        if ($Type == 'Discussion') {
            $Content = 'Discussion';
            $ContentID = $Args[$Content]->DiscussionID;
        } elseif ($Type == 'Comment') {
            $Content = 'Comment';
            $ContentID = $Args[$Content]->CommentID;
        } else {
            throw new Gdn_UserException('Invalid Content Type');
        }
        $ElementAuthorID = $Args[$Content]->InsertUserID;


        if (!C('Plugins.Zendesk.AllowTicketForSelf', false) && $ElementAuthorID == Gdn::Session()->UserID) {
            //no need to create support tickets for your self
            return;
        }

        $LinkText = 'Create Zendesk Ticket';
        if (isset($Args[$Content . 'Options'])) {
            $Args[$Content . 'Options']['Zendesk'] = array(
                'Label' => T($LinkText),
                'Url' => "/discussion/zendesk/" . strtolower($Content) . "/$ContentID",
                'Class' => 'Popup'
            );
        }
        //remove create Create already created
        $Attachments = GetValue('Attachments', $Args[$Content], array());
        foreach ($Attachments as $Attachment) {
            if ($Attachment['Type'] == 'zendesk-ticket') {
                unset($Args[$Content . 'Options']['Zendesk']);
            }
        }
    }

    /**
     * Handle Zendesk popup to create ticket in discussions.
     *
     * @param DiscussionController $Sender Sending controller.
     *
     * @throws Exception If Errors.
     */
    public function discussionController_zendesk_create($Sender) {
        // Signed in users only.
        if (!($UserID = Gdn::Session()->UserID)) {
            return;
        }

        if (!$this->isConnected()) {
            $LoginUrl = Url('/profile/connections');
            if (C('Plugins.Zendesk.GlobalLogin.Enabled')) {
                $LoginUrl = Url('/plugin/zendesk#global-login');
            }
            $Sender->SetData('LoginUrl', $LoginUrl);
            $Sender->Render('login', '', 'plugins/Zendesk');
            return;
        }

        $UserName = Gdn::Session()->User->Name;

        $Arguments = $Sender->RequestArgs;
        if (sizeof($Arguments) != 2) {
            throw new Exception('Invalid Request Url');
        }
        $Context = $Arguments[0];
        $ContentID = $Arguments[1];
        $Sender->Form = new Gdn_Form();

        if ($Context == 'comment') {
            $CommentModel = new CommentModel();
            $Content = $CommentModel->GetID($ContentID);
            $DiscussionID = $Content->DiscussionID;
            $Url = CommentUrl($Content);

            $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);
            $TicketTitle = $Discussion->Name;

        } else {
            $DiscussionID = $ContentID;
            $Content = $Sender->DiscussionModel->GetID($ContentID);
            $TicketTitle = $Content->Name;
            $Url = DiscussionUrl($Content, 1);

        }

        // Join in attachments
        $AttachmentModel = AttachmentModel::Instance();
        $AttachmentModel->JoinAttachments($Content);

        if ($Sender->Form->IsPostBack() && $Sender->Form->AuthenticatedPostBack() === true) {
            $Sender->Form->ValidateRule('Title', 'function:ValidateRequired', 'Title is required');
            $Sender->Form->ValidateRule('Body', 'function:ValidateRequired', 'Body is required');

            if ($Sender->Form->ErrorCount() == 0) {
                $FormValues = $Sender->Form->FormValues();
                $Body = $FormValues['Body'];

                $Body .= "\n--\n\nThis ticket was generated from: " . $Url . "\n\n";
                $this->setZendesk();
                $TicketID = $this->zendesk->createTicket(
                    $FormValues['Title'],
                    $Body,
                    $this->zendesk->createRequester(
                        $FormValues['InsertName'],
                        $FormValues['InsertEmail']
                    ),
                    array(
                        'custom_fields' => array(
                            'DiscussionID' => $DiscussionID,
                            'ForumUrl' => $Url
                        ))
                );

                if ($TicketID > 0) {
                    //Save to Attachments
                    $AttachmentModel->Save(array(
                        'Type' => 'zendesk-ticket',
                        'ForeignID' => $AttachmentModel->RowID($Content),
                        'ForeignUserID' => $Content->InsertUserID,
                        'Source' => 'zendesk',
                        'SourceID' => $TicketID,
                        'SourceURL' => C('Plugins.Zendesk.Url') . '/agent/#/tickets/' . $TicketID,
                        'Status' => 'open',
                        'LastModifiedDate' => Gdn_Format::ToDateTime()
                    ));
                    $Sender->InformMessage('Zendesk Ticket Created');
                    $Sender->JsonTarget('', $Url, 'Redirect');

                } else {
                    $Sender->InformMessage(T("Error creating ticket with Zendesk"));
                }
            }
        }

        $Sender->Form->AddHidden('Url', $Url);
        $Sender->Form->AddHidden('UserId', $UserID);
        $Sender->Form->AddHidden('UserName', $UserName);
        $Sender->Form->AddHidden('InsertName', $Content->InsertName);
        $Sender->Form->AddHidden('InsertEmail', $Content->InsertEmail);

        $Sender->Form->SetValue('Title', $TicketTitle);
        $Sender->Form->SetValue('Body', Gdn_Format::TextEx($Content->Body));

        $Sender->SetData('Data', array(
            'DiscussionID' => $DiscussionID,
            'UserID' => $UserID,
            'UserName' => $UserName,
            'Body' => $Content->Body,
            'InsertName' => $Content->InsertName,
            'InsertEmail' => $Content->InsertEmail,
            'Title' => $TicketTitle,
        ));

        $Sender->Render('createticket', '', 'plugins/Zendesk');
    }

    /**
     * Enable/Disable Global Login.
     *
     * @param Controller $Sender Sending controller.
     */
    public function controller_toggle($Sender) {
        // Enable/Disable
        if (Gdn::Session()->ValidateTransientKey(GetValue(1, $Sender->RequestArgs))) {
            if (C('Plugins.Zendesk.GlobalLogin.Enabled')) {
                $this->disable();
                Redirect(Url('/plugin/zendesk'));
            }
            Redirect(Url('/plugin/zendesk/authorize'));

        }
    }

    /**
     * Disable Global Login.
     */
    protected function disable() {
        RemoveFromConfig('Plugins.Zendesk.GlobalLogin.Enabled');
        RemoveFromConfig('Plugins.Zendesk.GlobalLogin.AccessToken');
    }

    /**
     * Add Zendesk to Dashboard menu.
     *
     * @param Controller $Sender Sending controller.
     * @param array $Arguments Event arguments.
     */
    public function base_getAppSettingsMenuItems_handler($Sender, $Arguments) {
        $LinkText = T('Zendesk');
        $Menu = $Arguments['SideMenu'];
        $Menu->AddItem('Forum', T('Forum'));
        $Menu->AddLink('Forum', $LinkText, 'plugin/zendesk', 'Garden.Settings.Manage');
    }

    /**
     * Setup to plugin.
     */
    public function setup() {

        SaveToConfig('Garden.AttachmentsEnabled', true);

        $Error = '';
        if (!function_exists('curl_init')) {
            $Error = ConcatSep("\n", $Error, 'This plugin requires curl.');
        }
        if ($Error) {
            throw new Gdn_UserException($Error, 400);
        }
        // Save the provider type.
        Gdn::SQL()->Replace(
            'UserAuthenticationProvider',
            array(
                'AuthenticationSchemeAlias' => 'zendesk',
                'URL' => '...',
                'AssociationSecret' => '...',
                'AssociationHashMethod' => '...'
            ),
            array('AuthenticationKey' => self::PROVIDER_KEY),
            true
        );
        Gdn::PermissionModel()->Define(array('Garden.Staff.Allow' => 'Garden.Moderation.Manage'));
        $this->setupConfig();

    }

    /**
     * Setup Config Settings.
     */
    protected function setupConfig() {
        SaveToConfig('Garden.AttachmentsEnabled', true);
        $ConfigSettings = array(
            'Url',
            'ApplicationID',
            'Secret'
        );
        //prevents resetting any previous values
        foreach ($ConfigSettings as $ConfigSetting) {
            if (!C('Plugins.Zendesk.' . $ConfigSetting)) {
                SaveToConfig('Plugins.Zendesk.' . $ConfigSetting, '');
            }
        }
    }

    //OAuth Methods

    /**
     * OAuth Method.  Gets the authorize Uri.
     *
     * @param bool|string $RedirectUri Redirect Url.
     *
     * @throws Gdn_UserException If Errors.
     * @return string Authorize URL Authorize Url.
     */
    public static function authorizeUri($RedirectUri = false) {
        if (!self::isConfigured()) {
            throw new Gdn_UserException('Zendesk is not configured yet');
        }
        $AppID = C('Plugins.Zendesk.ApplicationID');
        if (!$RedirectUri) {
            $RedirectUri = self::redirectUri();
        }
        $Query = array(
            'redirect_uri' => $RedirectUri,
            'client_id' => $AppID,
            'response_type' => 'code',
            'scope' => 'read write',

        );
        return C('Plugins.Zendesk.Url') . '/oauth/authorizations/new?' . http_build_query($Query);
    }

    /**
     * Used in the OAuth Process.
     *
     * @param null|string $NewValue A different redirect url.
     *
     * @return string Redirect Url.
     */
    public static function redirectUri($NewValue = null) {
        if ($NewValue !== null) {
            $RedirectUri = $NewValue;
        } else {
            $RedirectUri = Url('/profile/zendesk', true, true, true);
        }
        return $RedirectUri;
    }

    /**
     * OAuth Method.  Sends request to zendesk to validate tokens.
     *
     * @param string $Code OAuth Code.
     * @param string $RedirectUri Redirect Uri.
     *
     * @return string Response
     * @throws Gdn_UserException If error.
     */
    public static function getTokens($Code, $RedirectUri) {
        if (!self::isConfigured()) {
            throw new Gdn_UserException('Zendesk is not configured yet');
        }
        $Post = array(
            'grant_type' => 'authorization_code',
            'client_id' => C('Plugins.Zendesk.ApplicationID'),
            'client_secret' => C('Plugins.Zendesk.Secret'),
            'code' => $Code,
            'redirect_uri' => $RedirectUri,
        );
        $Proxy = new ProxyRequest();
        $Response = $Proxy->Request(
            array(
                'URL' => C('Plugins.Zendesk.Url') . '/oauth/tokens',
                'Method' => 'POST',
            ),
            $Post
        );

        if (strpos($Proxy->ContentType, 'application/json') !== false) {
            $Response = json_decode($Response);
        }
        if (isset($Response->error)) {
            throw new Gdn_UserException('Error Communicating with Zendesk API: ' . $Response->error_description);
        }

        return $Response;

    }

    /**
     * OAuth Method.
     *
     * @return string $Url
     */
    public static function profileConnectUrl() {
        return Gdn::Request()->Url('/profile/zendeskconnect', true, true, true);
    }

    /**
     * OAuth Method.
     *
     * @return bool
     */
    public static function isConfigured() {
        $Url = C('Plugins.Zendesk.Url');
        $AppID = C('Plugins.Zendesk.ApplicationID');
        $Secret = C('Plugins.Zendesk.Secret');
        if (!$AppID || !$Secret || !$Url) {
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
     * @param Controller $Sender Sending controller.
     * @param array $Args Event arguments.
     */
    public function Base_GetConnections_Handler($Sender, $Args) {
        if (!$this->isConfigured()) {
            return;
        }
        //Staff Only
        if (!Gdn::Session()->CheckPermission('Garden.Staff.Allow')) {
            return;
        }
        $Sf = GetValueR('User.Attributes.' . self::PROVIDER_KEY, $Args);
        Trace($Sf);
        $Profile = GetValueR('User.Attributes.' . self::PROVIDER_KEY . '.Profile', $Args);
        $Sender->Data["Connections"][self::PROVIDER_KEY] = array(
            'Icon' => $this->GetWebResource('zendesk.svg', '/'),
            'Name' => self::PROVIDER_KEY,
            'ProviderKey' => self::PROVIDER_KEY,
            'ConnectUrl' => self::authorizeUri(self::profileConnectUrl()),
            'Profile' => array(
                'Name' => GetValue('fullname', $Profile),
                'Photo' => GetValue('photo', $Profile)
            )
        );
    }

    /**
     * OAUth Method.  Code is Exchanged for Token.
     *
     * Token is stored for later use.  Token does not expire.  It can be revoked from Zendesk
     *
     * @param ProfileController $Sender Sending controller.
     * @param string $UserReference User Reference.
     * @param string $Username Username.
     * @param bool|string $Code Authorize Code.
     */
    public function profileController_zendeskConnect_create(
        $Sender,
        $UserReference = '',
        $Username = '',
        $Code = false
    ) {
        $Sender->Permission('Garden.SignIn.Allow');
        $Sender->GetUserInfo($UserReference, $Username, '', true);
        $Sender->_SetBreadcrumbs(T('Connections'), UserUrl($Sender->User, '', 'connections'));

        try {
            $Tokens = $this->getTokens($Code, self::profileConnectUrl());
        } catch (Gdn_UserException $e) {
            $Attributes = array(
                'AccessToken' => null,
                'Profile' => null,
            );
            Gdn::UserModel()->SaveAttribute($Sender->User->UserID, self::PROVIDER_KEY, $Attributes);
            $Message = $e->getMessage();
            Gdn::Dispatcher()->PassData('Exception', htmlspecialchars($Message))
                ->Dispatch('home/error');
            return;
        }
        $this->accessToken = GetValue('access_token', $Tokens);
        $this->setZendesk($this->accessToken);
        $profile = $this->zendesk->getProfile();

        Gdn::UserModel()->SaveAuthentication(
            array(
                'UserID' => $Sender->User->UserID,
                'Provider' => self::PROVIDER_KEY,
                'UniqueID' => $profile['id']
            )
        );
        $Attributes = array(
            'AccessToken' => $this->accessToken,
            'Profile' => $profile,
        );
        Gdn::UserModel()->SaveAttribute($Sender->User->UserID, self::PROVIDER_KEY, $Attributes);
        $this->EventArguments['Provider'] = self::PROVIDER_KEY;
        $this->EventArguments['User'] = $Sender->User;
        $this->FireEvent('AfterConnection');

        Redirect(UserUrl($Sender->User, '', 'connections'));
    }

    /**
     * OAuth Method. Redirects user to request access.
     */
    public function controller_authorize() {
        Redirect(self::authorizeUri(self::globalConnectUrl()));
    }

    /**
     * OAuth Method. Handles the redirect from Zendesk and stores AccessToken.
     *
     * @throws Gdn_UserException If Error.
     */
    public function controller_connect() {
        $Code = Gdn::Request()->Get('code');
        $Tokens = $this->getTokens($Code, self::globalConnectUrl());
        $AccessToken = GetValue('access_token', $Tokens);

        if ($AccessToken) {
            SaveToConfig(array(
                'Plugins.Zendesk.GlobalLogin.Enabled' => true,
                'Plugins.Zendesk.GlobalLogin.AccessToken' => $AccessToken
            ));
        } else {
            RemoveFromConfig(array(
                'Plugins.Zendesk.GlobalLogin.Enabled' => true,
                'Plugins.Zendesk.GlobalLogin.AccessToken' => $AccessToken
            ));
            throw new Gdn_UserException('Error Connecting to Zendesk');
        }
        Redirect(Url('/plugin/zendesk'));

    }

    /**
     * OAuth Method.
     *
     * @return string
     */
    public static function globalConnectUrl() {
        return Gdn::Request()->Url('/plugin/zendesk/connect', true, true, true);
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
                C('Plugins.Zendesk.Url'),
                $accessToken
            );
        }
    }

    /**
     * Add attachment views.
     *
     * @param DiscussionController $Sender Sending Controller.
     */
    public function DiscussionController_FetchAttachmentViews_Handler($Sender) {
        require_once $Sender->FetchViewLocation('attachment', '', 'plugins/Zendesk');
    }
}
