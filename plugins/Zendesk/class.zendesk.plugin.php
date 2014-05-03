<?php
/**
 * @copyright 2014 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Zendesk'] = array(
    'Name' => 'Zendesk',
    'Description' => "Users may designate a discussion as a Support Issue and the message will be submitted to Zendesk. Reply will be added to thread",
    'Version' => '0.0.4',
    'RequiredApplications' => array('Vanilla' => '2.1.18'),
    'SettingsUrl' => '/dashboard/plugin/zendesk',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => true,
    'Author' => 'John Ashton',
    'AuthorEmail' => 'johnashton@vanillaforums.com',
    'AuthorUrl' => 'http://www.github.com/John0x00'
);

/**
 * Class ZendeskPlugin
 *
 */
class ZendeskPlugin extends Gdn_Plugin {

    /**
     * Used for OAuth
     * @var string
     */
    const PROVIDER_KEY = 'Zendesk';

    /**
     * Used for OAuth
     * @var string
     */
    protected $accessToken;

    /**
     * If status is set to this we will stop getting updates from Salesforce
     * @var string
     */
    protected $closedCaseStatusString = 'solved';

    /**
     * If time since last update from Zendesk is less then this; we wont check for update - saving api calls.
     * @var int
     */
    protected $minimumTimeForUpdate = 600;

    /** @var \Zendesk Zendesk */
    protected $zendesk;



    public function __construct() {
        parent::__construct();

        $this->accessToken = GetValueR('Attributes.' . self::PROVIDER_KEY . '.AccessToken', Gdn::Session()->User);
        if (!$this->accessToken) {
            $this->accessToken = C('Plugins.Zendesk.GlobalLogin.AccessToken');
            if ($this->accessToken) {
                Trace('Zendesk Using Global Login');
            }
        }

        if (!$this->accessToken) {
            Trace('Zendesk Not Connected');
        }
    }

    /**
     * @param DiscussionController $Sender
     * @param $Args
     */
    public function DiscussionController_AfterDiscussionBody_Handler($Sender, $Args) {


        // Signed in users only.
        if (!Gdn::Session()->UserID) {
            return;
        }

        if (!Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
            return;
        }
        $Attachments = GetValue('Attachments', $Args['Discussion']);
        if ($Attachments) {
            foreach ($Attachments as $Attachment) {
                if ($Attachment['Type'] == 'zendesk-ticket') {
                    $this->UpdateAttachment($Attachment);
                }
            }
        }

    }

    /**
     * @param DiscussionController $Sender
     * @param array $Args
     */
    public function DiscussionController_AfterCommentBody_Handler($Sender, $Args) {


        // Signed in users only. No guest reporting!
        if (!Gdn::Session()->UserID) {
            return;
        }

        if (!Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
            return;
        }
        $Attachments = GetValue('Attachments', $Args['Comment']);
        if ($Attachments) {
            foreach ($Attachments as $Attachment) {
                if ($Attachment['Type'] == 'zendesk-ticket') {
                    $this->UpdateAttachment($Attachment);
                }
            }
        }
    }

    /**
     * @param AssetModel $Sender
     */
    public function AssetModel_StyleCss_Handler($Sender) {
        $Sender->AddCssFile('zendesk.css', 'plugins/Zendesk');
    }

    /**
     * @see AttachmentModel
     * @param array $Attachment Attachment Data - see AttachmentModel
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
     * Update the Attachment
     *
     * @see AttachmentModel
     * @param array $Attachment
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
     * Creates the Virtual Zendesk Controller and adds Link to SideMenu in the dashboard
     *
     * @param PluginController $Sender
     */
    public function PluginController_Zendesk_Create($Sender) {

        $Sender->Permission('Garden.Settings.Manage');
        $Sender->Title('Zendesk');
        $Sender->AddSideMenu('plugin/zendesk');
        $Sender->Form = new Gdn_Form();
        $this->Dispatch($Sender, $Sender->RequestArgs);
    }

    /**
     * Dashboard Settings
     *
     * Default method of virtual Zendesk controller.
     * @param Gdn_Controller $Sender
     */
    public function Controller_Index($Sender) {

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
            'ToggleUrl' => '/plugin/zendesk/toggle/' . Gdn::Session()->TransientKey()
        ));


        $Sender->Render($this->GetView('dashboard.php'));
    }


    /**
     * Adds Option to Create Ticket to Discussion Gear.  Will be removed if Discussion has
     * already been submitted as a Ticket
     *
     * @param DiscussionController $Sender
     * @param array $Args
     * @rodo login prompts
     */
    public function DiscussionController_DiscussionOptions_Handler($Sender, $Args) {

        if (!$this->isConfigured() || !$this->isConnected()) {
            return;
        }

        // Signed in users only. No guest reporting!
        if (!Gdn::Session()->UserID) {
            return;
        }

        $DiscussionID = $Args['Discussion']->DiscussionID;
        $ElementAuthorID = $Args['Discussion']->InsertUserID;

        if (!C('Plugins.Zendesk.AllowTicketForSelf', false) && $ElementAuthorID == Gdn::Session()->UserID) {
            //no need to create support tickets for your self
            return;
        }

        $LinkText = 'Create Zendesk Ticket';
        if (isset($Args['DiscussionOptions'])) {
            $Args['DiscussionOptions']['Zendesk'] = array(
                'Label' => T($LinkText),
                'Url' => "/discussion/zendesk/$DiscussionID",
                'Class' => 'Popup'
            );
        }
        //remove create Create already created
        $Attachments = GetValue('Attachments', $Args['Discussion'], array());
        foreach ($Attachments as $Attachment) {
            if ($Attachment['Type'] == 'zendesk-ticket') {
                unset($Args['DiscussionOptions']['Zendesk']);
            }
        }
    }

    /**
     * Adds Option to Create Ticket to Comment Gear.  Will be removed if comment has
     * already been submitted as a Ticket
     *
     * @param CommentController $Sender
     * @param array $Args
     * @todo login prompts
     */
    public function DiscussionController_CommentOptions_Handler($Sender, $Args) {

        if (!$this->isConfigured() || !$this->isConnected()) {
            return;
        }

        // Signed in users only. No guest reporting!
        if (!Gdn::Session()->UserID) {
            return;
        }

        $ElementAuthorID = $Args['Comment']->InsertUserID;
        $CommentID = $Args['Comment']->CommentID;


        if (!C('Plugins.Zendesk.AllowTicketForSelf', false) && $ElementAuthorID == Gdn::Session()->UserID) {
            //no need to create support tickets for your self
            return;
        }

        $LinkText = 'Create Zendesk Ticket';
        if (isset($Args['CommentOptions'])) {
            $Args['CommentOptions']['Zendesk'] = array(
                'Label' => T($LinkText),
                'Url' => "/discussion/zendesk/Comment/$CommentID",
                'Class' => 'Popup'
            );
        }
        //remove create Create already created
        $Attachments = GetValue('Attachments', $Args['Comment'], array());
        foreach ($Attachments as $Attachment) {
            if ($Attachment['Type'] == 'zendesk-ticket') {
                unset($Args['CommentOptions']['Zendesk']);
            }
        }
    }

    /**
     * Handle Zendesk popup to create ticket in discussions
     *
     * @throws Exception
     * @param DiscussionController $Sender
     */
    public function DiscussionController_Zendesk_Create($Sender) {
        // Signed in users only.
        if (!($UserID = Gdn::Session()->UserID)) {
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

        if ($Context == 'Comment') {
            $CommentModel = new CommentModel();
            $Content = $CommentModel->GetID($ContentID);
            $DiscussionID = $Content->DiscussionID;
            $Url = CommentUrl($Content);

            $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);
            $TicketTitle = $Discussion->Name;

        } else {
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
                        'custom_fields' =>
                            array('DiscussionID' => $DiscussionID)
                    )
                );

                if ($TicketID > 0) {

                    //Save to Attachments
                    $AttachmentModel->Save(
                        array(
                            'Type' => 'zendesk-ticket',
                            'ForeignID' => $AttachmentModel->RowID($Content),
                            'ForeignUserID' => $Content->InsertUserID,
                            'Source' => 'zendesk',
                            'SourceID' => $TicketID,
                            'SourceURL' => C('Plugins.Zendesk.Url') . '/agent/#/tickets/' . $TicketID,
                            'Status' => 'open',
                            'LastModifiedDate' => Gdn_Format::ToDateTime()
                        )
                    );
                    $Sender->InformMessage('Zendesk Ticket Created');
                    $Sender->JsonTarget('', $Url, 'Redirect');

                } else {
                    $Sender->InformMessage(T("Error creating ticket with Zendesk"));
                }

            }

        }


        $Data = array(
            'DiscussionID' => $DiscussionID,
            'UserID' => $UserID,
            'UserName' => $UserName,
            'Body' => $Content->Body,
            'InsertName' => $Content->InsertName,
            'InsertEmail' => $Content->InsertEmail,
            'Title' => $TicketTitle,
        );

        $Sender->Form->AddHidden('Url', $Url);
        $Sender->Form->AddHidden('UserId', $UserID);
        $Sender->Form->AddHidden('UserName', $UserName);
        $Sender->Form->AddHidden('InsertName', $Content->InsertName);
        $Sender->Form->AddHidden('InsertEmail', $Content->InsertEmail);

        $Sender->Form->SetValue('Title', $TicketTitle);
        $Sender->Form->SetValue('Body', $Content->Body);

        $Sender->SetData('Data', $Data);

        $Sender->Render('createticket', '', 'plugins/Zendesk');


    }


    /**
     * Enable/Disable Global Login.
     * @param Controller $Sender
     */
    public function Controller_Toggle($Sender) {
        // Enable/Disable
        if (Gdn::Session()->ValidateTransientKey(GetValue(1, $Sender->RequestArgs))) {
            if (C('Plugins.Zendesk.GlobalLogin.Enabled')) {
                $this->disable();
                Redirect('plugin/zendesk');
            }
            Redirect(Url('/plugin/zendesk/authorize'));

        }
    }


    /**
     * Disable Zendesk Plugin
     */
    protected function disable() {
        RemoveFromConfig('Plugins.Zendesk.GlobalLogin.Enabled');
        RemoveFromConfig('Plugins.Zendesk.GlobalLogin.AccessToken');
    }

    /**
     * Add Zendesk to Dashboard menu.
     * @param Controller $Sender
     * @param array $Arguments
     */
    public function Base_GetAppSettingsMenuItems_Handler($Sender, $Arguments) {
        $LinkText = T('Zendesk');
        $Menu = $Arguments['SideMenu'];
        $Menu->AddItem('Forum', T('Forum'));
        $Menu->AddLink('Forum', $LinkText, 'plugin/zendesk', 'Garden.Settings.Manage');
    }

    /**
     * Setup to plugin.
     */
    public function setup() {
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
     * Setup Config Settings
     */
    protected function setupConfig() {
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
     * Used in the Oauth Process
     *
     * @param bool|string $RedirectUri
     * @return string Authorize URL
     * @throws Gdn_UserException
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
     * Used in the OAuth Process
     * @param null $NewValue a different redirect url
     * @return null|string
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
     * Used in the Oath Process
     *
     * @param $Code - OAuth Code
     * @param $RedirectUri - Redirect Uri
     * @return string Response
     * @throws Gdn_UserException
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
     * Used in the Oauth Process
     *
     * @return string $Url
     */
    public static function profileConnectUrl() {
        return Gdn::Request()->Url('/profile/zendeskconnect', true, true, true);
    }

    /**
     * Used in the Oauth Process
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

    public function isConnected() {
        if ($this->accessToken) {
            return true;
        }
        return false;
    }


    /**
     * @param Controller $Sender
     * @param array $Args
     */
    public function Base_GetConnections_Handler($Sender, $Args) {
        if (!$this->isConfigured()) {
            return;
        }
        $Sf = GetValueR('User.Attributes.' . self::PROVIDER_KEY, $Args);
        Trace($Sf);
        $Profile = GetValueR('User.Attributes.' . self::PROVIDER_KEY . '.Profile', $Args);
        $Sender->Data["Connections"][self::PROVIDER_KEY] = array(
            'Icon' => $this->GetWebResource('icon.png', '/'),
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
     * Part of the Oath Process.  Code is Exchanged for Token
     *
     * Token is stored for later use.  Token does not expire.  It can be revoked from Zendesk
     *
     * @todo test revoking.
     *
     * @param ProfileController $Sender
     * @param string $UserReference
     * @param string $Username
     * @param bool $Code
     *
     */
    public function ProfileController_ZendeskConnect_Create(
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
        $AccessToken = GetValue('access_token', $Tokens);
        //@todo profile
        $Profile = array();
        Gdn::UserModel()->SaveAuthentication(
            array(
                'UserID' => $Sender->User->UserID,
                'Provider' => self::PROVIDER_KEY,
                'UniqueID' => $Profile['id']
            )
        );
        $Attributes = array(
            'AccessToken' => $AccessToken,
            'Profile' => $Profile,
        );
        Gdn::UserModel()->SaveAttribute($Sender->User->UserID, self::PROVIDER_KEY, $Attributes);
        $this->EventArguments['Provider'] = self::PROVIDER_KEY;
        $this->EventArguments['User'] = $Sender->User;
        $this->FireEvent('AfterConnection');

        $RedirectUrl = UserUrl($Sender->User, '', 'connections');

        Redirect($RedirectUrl);
    }


    public function Controller_Authorize() {
        Redirect(self::authorizeUri(self::globalConnectUrl()));
    }


    public function Controller_Connect() {
        $Code = Gdn::Request()->Get('code');
        $Tokens = $this->getTokens($Code, self::globalConnectUrl());
        $AccessToken = GetValue('access_token', $Tokens);

        if ($AccessToken) {
            SaveToConfig(
                array(
                    'Plugins.Zendesk.GlobalLogin.Enabled' => true,
                    'Plugins.Zendesk.GlobalLogin.AccessToken' => $AccessToken
                )
            );
        } else {
            RemoveFromConfig(
                array(
                    'Plugins.Zendesk.GlobalLogin.Enabled' => true,
                    'Plugins.Zendesk.GlobalLogin.AccessToken' => $AccessToken
                )
            );
            trigger_error('Error Accessing Zendesk');
        }
        Redirect('/plugin/zendesk');

    }

    public static function globalConnectUrl() {
        return Gdn::Request()->Url('/plugin/zendesk/connect', true, true, true);
    }

    //end of OAUTH

    /**
     *
     * Handler to Parse Attachments for Staff Users
     *
     * @param $Sender
     * @param $Args
     */
    public function SalesforcePlugin_BeforeWriteAttachments_Handler($Sender, &$Args) {

        foreach ($Args['Attachments'] as &$Attachment) {
            if ($Attachment['Source'] == 'zendesk') {
                $ParsedAttachment = $this->ParseAttachmentForHtmlView($Attachment);
                $Attachment = $ParsedAttachment + $Attachment;
            }
        }
    }

    /**
     *
     * Handler to Parse Attachment for the Owner of the Attachment
     *
     * @param $Sender
     * @param $Args
     */
    public function SalesforcePlugin_BeforeWriteAttachmentForOwner_Handler($Sender, &$Args) {
        if (GetValueR('Attachment.Source', $Args) == 'zendesk') {
            $Args['Attachment'] = $this->ParseAttachmentForHtmlView($Args['Attachment']);
        }
    }

    /**
     *
     * Handler to Parse Attachments for All Other (Not staff or Owner) Users
     *
     * @param $Sender
     * @param $Args
     */
    public function SalesforcePlugin_BeforeWriteAttachmentForOther_Handler($Sender, &$Args) {
        if ($Args['Attachment']['Source'] == 'zendesk') {
            $Args['Attachment'] = $this->ParseAttachmentForHtmlView($Args['Attachment']);
        }
    }


    /**
     * Given an instance of the attachment model, parse it into a format that
     * the attachment view can digest.
     *
     * @param array $Attachment
     * @return array
     */
    public static function parseAttachmentForHtmlView($Attachment) {

        $UserModel = new UserModel();
        $InsertUser = $UserModel->GetID($Attachment['InsertUserID']);

        $Parsed = array();

        $Parsed['Icon'] = 'ticket';

        $Parsed['Title'] = T('Ticket') . ' &middot; ' . Anchor(
                T($Attachment['Source']),
                $Attachment['SourceURL']
            );
        $Parsed['Meta'] = array(
            Gdn_Format::Date($Attachment['DateInserted'], 'html') . ' ' . T('by') . ' ' . UserAnchor($InsertUser)
        );

        if (GetValue('Error', $Attachment)) {
            $Parsed['Type'] = 'info';
            $Parsed['Body'] = $Attachment['Error'];
        } else {
            $Parsed['Fields'] = array();
            $Status = GetValue('Status', $Attachment);
            $LastModified = GetValue('Status', $Attachment);
            if ($Status) {
                $Parsed['Fields']['Status'] = $Status;
            }
            if ($LastModified) {
                $Parsed['Fields']['Last Updated'] = Gdn_Format::Date($LastModified, 'html');
            }

        }

        return $Parsed;
    }

    public function setZendesk() {
        if (!$this->zendesk) {
            $this->zendesk = new Zendesk(
                new ZendeskCurlRequest(),
                C('Plugins.Zendesk.Url'),
                $this->accessToken
            );
        }
    }
}
