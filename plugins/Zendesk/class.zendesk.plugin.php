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
    'SettingsUrl' => '/dashboard/plugin/Zendesk',
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
class ZendeskPlugin extends Gdn_Plugin
{

    /**
     * If status is set to this we will stop getting updates from Salesforce
     * @var string
     */
    protected $closedCaseStatusString = 'solved';

    /**
     * If time since last update from Salesforce is less then this; we wont check for update - saving api calls.
     * @var int
     */
    protected $minimumTimeForUpdate = 600;

    //methods

    /** @var \Zendesk Zendesk */
    protected $zendesk;

    public function __construct()
    {
        parent::__construct();

        $this->zendesk = new Zendesk(
            new ZendeskCurlRequest(),
            C('Plugins.Zendesk.ApiUrl'),
            C('Plugins.Zendesk.User'),
            C('Plugins.Zendesk.ApiKey')
        );


    }

    /**
     * @param DiscussionController $Sender
     * @param $Args
     */
    public function DiscussionController_AfterDiscussionBody_Handler($Sender, $Args)
    {

        if (!C('Plugins.Zendesk.Enabled')) {
            return;
        }

        // Signed in users only. No guest reporting!
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

    public function DiscussionController_AfterCommentBody_Handler($Sender, $Args)
    {
        if (!C('Plugins.Zendesk.Enabled')) {
            return;
        }

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
    public function AssetModel_StyleCss_Handler($Sender)
    {
        $Sender->AddCssFile('zendesk.css', 'plugins/Zendesk');
    }

    /**
     * @param array $Attachment Attachment Data - see AttachmentModel
     * @return bool
     */
    protected function isToBeUpdated($Attachment)
    {
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

    protected function updateAttachment($Attachment)
    {
        if ($this->IsToBeUpdated($Attachment)) {
            try {
                $Ticket = $this->zendesk->getTicket($Attachment['SourceID']);
            } catch (Gdn_UserException $e) {
                if ($e->getCode() == 404) {
                    $Attachment['Error'] = 'This task has been deleted from Zendesk';
                    $AttachmentModel = AttachmentModel::Instance();
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
     * @param Controller $Sender
     */
    public function PluginController_Zendesk_Create($Sender)
    {

        $Sender->Permission('Garden.Settings.Manage');
        $Sender->Title('Zendesk');
        $Sender->AddSideMenu('plugin/Zendesk');
        $Sender->Form = new Gdn_Form();
        $this->Dispatch($Sender, $Sender->RequestArgs);
    }

    /**
     * Dashboard Settings
     * Default method of virtual Zendesk controller.
     * @param Gdn_Controller $Sender
     */
    public function Controller_Index($Sender)
    {

        $Sender->AddCssFile('admin.css');

        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->SetField(
            array(
                'ApiKey',
                'User',
                'Url',
                'ApiUrl',
            )
        );

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
                    'ApiKey',
                    'function:ValidateRequired',
                    'API Key is required'
                );
                $Sender->Form->ValidateRule('User', 'function:ValidateRequired', 'User is required');
                $Sender->Form->ValidateRule('Url', 'function:ValidateRequired', 'Url is required');
                $Sender->Form->ValidateRule(
                    'ApiUrl',
                    'function:ValidateRequired',
                    'API Url is required'
                );

                if ($Sender->Form->ErrorCount() == 0) {
                    SaveToConfig('Plugins.Zendesk.ApiKey', trim($FormValues['ApiKey']));
                    SaveToConfig('Plugins.Zendesk.User', trim($FormValues['User']));
                    SaveToConfig('Plugins.Zendesk.Url', trim($FormValues['Url']));
                    SaveToConfig('Plugins.Zendesk.ApiUrl', trim($FormValues['ApiUrl']));
                    $Sender->InformMessage(T("Your changes have been saved."));
                } else {
                    $Sender->InformMessage(T("Error saving settings to config."));
                }
            }


        }


        $Sender->Form->SetValue('Url', C('Plugins.Zendesk.Url'));
        $Sender->Form->SetValue('ApiKey', C('Plugins.Zendesk.ApiKey'));
        $Sender->Form->SetValue('User', C('Plugins.Zendesk.User'));
        $Sender->Form->SetValue('ApiUrl', C('Plugins.Zendesk.ApiUrl'));

        $Sender->Render($this->GetView('dashboard.php'));
    }

    public function DiscussionController_DiscussionOptions_Handler($Sender, $Args)
    {

        if (!C('Plugins.Zendesk.Enabled')) {
            return;
        }

        // Signed in users only. No guest reporting!
        if (!Gdn::Session()->UserID) {
            return;
        }

        $DiscussionID = $Args['Discussion']->DiscussionID;
        $ElementAuthorID = $Args['Discussion']->InsertUserID;

//      if ($ElementAuthorID == Gdn::Session()->UserID) {
//         //no need to create support tickets for your self
//         return;
//      }

        $LinkText = 'Create Zendesk Ticket';
        if (isset($Args['DiscussionOptions'])) {
            $Args['DiscussionOptions']['Zendesk'] = array(
                'Label' => T($LinkText),
                'Url' => "/discussion/Zendesk/$DiscussionID",
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

    public function DiscussionController_CommentOptions_Handler($Sender, $Args)
    {

        if (!C('Plugins.Zendesk.Enabled')) {
            return;
        }

        // Signed in users only. No guest reporting!
        if (!Gdn::Session()->UserID) {
            return;
        }

        $ElementAuthorID = $Args['Comment']->InsertUserID;
        $CommentID = $Args['Comment']->CommentID;

//      if ($ElementAuthorID == Gdn::Session()->UserID) {
//         //no need to create support tickets for your self
//         return;
//      }

        $LinkText = 'Create Zendesk Ticket';
        if (isset($Args['CommentOptions'])) {
            $Args['CommentOptions']['Zendesk'] = array(
                'Label' => T($LinkText),
                'Url' => "/discussion/Zendesk/Comment/$CommentID",
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
     * Handle Zendesk popup in discussions
     * @throws Exception
     * @param DiscussionController $Sender
     */
    public function DiscussionController_Zendesk_Create($Sender)
    {
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

                    $AttachmentModel->Save(
                        array(
                            'Type' => 'zendesk-ticket',
                            'ForeignID' => $AttachmentModel->RowID($Content),
                            'ForeignUserID' => $Content->InsertUserID,
                            'Source' => 'zendesk',
                            'SourceID' => $TicketID,
                            'SourceURL' => 'https://amazinghourse.zendesk.com/agent/#/tickets/' . $TicketID,
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
     * Enable/Disable .
     * @param Controller $Sender
     */
    public function Controller_Toggle($Sender)
    {

        // Enable/Disable
        if (Gdn::Session()->ValidateTransientKey(GetValue(1, $Sender->RequestArgs))) {
            if (C('Plugins.Zendesk.Enabled')) {
                $this->disable();
            } else {
                $this->enable();
            }
            Redirect('plugin/Zendesk');
        }
    }


    /**
     * Add Zendesk to Dashboard menu.
     * @param Controller $Sender
     * @param array $Arguments
     */
    public function Base_GetAppSettingsMenuItems_Handler($Sender, $Arguments)
    {
        $LinkText = T('Zendesk');
        $Menu = $Arguments['SideMenu'];
        $Menu->AddItem('Forum', T('Forum'));
        $Menu->AddLink('Forum', $LinkText, 'plugin/Zendesk', 'Garden.Settings.Manage');
    }


    protected function enable()
    {
        SaveToConfig('Plugins.Zendesk.Enabled', true);
    }

    protected function disable()
    {
        RemoveFromConfig('Plugins.Zendesk.Enabled');
    }

    public function setup()
    {

        $this->setupConfig();
        $this->structure();

    }

    public function structure()
    {


    }

    private function setupConfig()
    {
        SaveToConfig('Plugins.Zendesk.Enabled', false);
        $ConfigSettings = array(
            'ApiKey',
            'User',
            'Url',
            'ApiUrl'
        );
        //prevents resetting any previous values
        foreach ($ConfigSettings as $ConfigSetting) {
            if (C('Plugins.Zendesk.' . $ConfigSetting)) {
                SaveToConfig('Plugins.Zendesk.' . $ConfigSetting, '');
            }
        }
    }


    //OAUTH - NOT WORKING

    const PROVIDER_KEY = 'Zendesk';
    const BASE_URL = 'https://amazinghourse.zendesk.com';
    const AUTHORIZE_URL = 'https://amazinghourse.zendesk.com/oauth/authorizations/new';
    const TOKEN_URL = 'https://amazinghourse.zendesk.com/oauth/tokens';
    const REDIRECT_URL = 'https://amazinghourse.zendesk.com/profile/connections';

    const SECRET = 'd3591711ad3dcc2dd3d12303fcbd75df9255744a546862cdc0f4e5cb7cdd52fa';
    const APPLICATION_ID = 'vanilla';

    /**
     * Used in the Oauth Process
     *
     * @param bool|string $RedirectUri
     * @param bool|string $State
     * @return string Authorize URL
     */
    public static function authorizeUri($RedirectUri = false, $State = false)
    {
        $AppID = self::APPLICATION_ID;
        if (!$RedirectUri) {
            $RedirectUri = self::redirectUri();
        }
        $Query = array(
            'redirect_uri' => $RedirectUri,
            'client_id' => $AppID,
            'response_type' => 'code',
            'scope' => 'read'
        );
        if ($State) {
            $Query['state'] = $State;
        }
        $Return = self::AUTHORIZE_URL . "?"
            . http_build_query($Query);
        return $Return;
    }

    /**
     * Used in the OAuth Process
     * @param null $NewValue a different redirect url
     * @return null|string
     */
    public static function redirectUri($NewValue = null)
    {
        if ($NewValue !== null) {
            $RedirectUri = $NewValue;
        } else {
            $RedirectUri = Url('/profile/zendesk', true, true, true);
            if (strpos($RedirectUri, '=') !== false) {
                $p = strrchr($RedirectUri, '=');
                $Uri = substr($RedirectUri, 0, -strlen($p));
                $p = urlencode(ltrim($p, '='));
                $RedirectUri = $Uri . '=' . $p;
            }
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
    public static function getTokens($Code, $RedirectUri)
    {
        $Post = array(
            'grant_type' => 'authorization_code',
            'client_id' => self::APPLICATION_ID,
            'client_secret' => self::SECRET,
            'code' => $Code,
            'redirect_uri' => $RedirectUri,
        );
        $Proxy = new ProxyRequest();
        $Response = $Proxy->Request(
            array(
                'URL' => self::TOKEN_URL,
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
    public static function profileConnectUrl()
    {
        return Gdn::Request()->Url('/profile/zendeskconnect', true, true, true);
    }

    /**
     * Used in the Oauth Process
     *
     * @return bool
     */
    public static function isConfigured()
    {
        $AppID = self::APPLICATION_ID;
        $Secret = self::SECRET;
        if (!$AppID || !$Secret) {
            return false;
        }
        return true;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function setInstanceUrl($instanceUrl)
    {
        $this->instanceUrl = $instanceUrl;
    }

    /**
     * @param Controller $Sender
     * @param array $Args
     */
    public function Base_GetConnections_Handler($Sender, $Args)
    {
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
     * @param ProfileController $Sender
     * @param string $UserReference
     * @param string $Username
     * @param bool $Code
     *
     */
    public function ProfileController_ZendeskConnect_Create($Sender, $UserReference = '', $Username = '', $Code = false)
    {
        $Sender->Permission('Garden.SignIn.Allow');
        $Sender->GetUserInfo($UserReference, $Username, '', true);
        $Sender->_SetBreadcrumbs(T('Connections'), UserUrl($Sender->User, '', 'connections'));
        //check $GET state // if DashboardConnection // then do global connection.
//      $State = GetValue('state', $_GET, FALSE);
//      if ($State == 'DashboardConnection') {
//         try {
//            $Tokens = self::getTokens($Code, self::profileConnectUrl());
//         } catch (Gdn_UserException $e) {
//            $Message = $e->getMessage();
//            Gdn::Dispatcher()->PassData('Exception', htmlspecialchars($Message))
//               ->Dispatch('home/error');
//            return;
//         }
//         Redirect('/plugin/Zendesk/?DashboardConnection=1&' . http_build_query($Tokens));
//      }
        try {
            $Tokens = $this->getTokens($Code, self::profileConnectUrl());
        } catch (Gdn_UserException $e) {
            $Attributes = array(
                'RefreshToken' => null,
                'accessToken' => null,
                'instanceUrl' => null,
                'Profile' => null,
            );
            Gdn::UserModel()->SaveAttribute($Sender->User->UserID, self::PROVIDER_KEY, $Attributes);
            $Message = $e->getMessage();
            Gdn::Dispatcher()->PassData('Exception', htmlspecialchars($Message))
                ->Dispatch('home/error');
            return;
        }
        $accessToken = GetValue('access_token', $Tokens);
        $instanceUrl = GetValue('instance_url', $Tokens);
        $RefreshToken = GetValue('refresh_token', $Tokens);

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
            'RefreshToken' => $RefreshToken,
            'accessToken' => $accessToken,
            'instanceUrl' => $instanceUrl,
            'Profile' => $Profile,
        );
        Gdn::UserModel()->SaveAttribute($Sender->User->UserID, self::PROVIDER_KEY, $Attributes);
        $this->EventArguments['Provider'] = self::PROVIDER_KEY;
        $this->EventArguments['User'] = $Sender->User;
        $this->FireEvent('AfterConnection');

        $RedirectUrl = UserUrl($Sender->User, '', 'connections');

        Redirect($RedirectUrl);
    }

    //end of OAUTH

    /**
     *
     * Handler to Parse Attachments for Staff Users
     *
     * @param $Sender
     * @param $Args
     */
    public function SalesforcePlugin_BeforeWriteAttachments_Handler($Sender, &$Args)
    {

        foreach ($Args['Attachments'] as &$Attachment) {
            if ($Attachment['Source'] == 'zendesk') {
                $ParsedAttachment = $this->ParseAttachmentForHtmlView($Attachment);
                $Attachment = $Attachment + $ParsedAttachment;
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
    public function SalesforcePlugin_BeforeWriteAttachmentForOwner_Handler($Sender, &$Args)
    {
        if ($Args['Attachment']['Source'] == 'zendesk') {
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
    public function SalesforcePlugin_BeforeWriteAttachmentForOther_Handler($Sender, &$Args)
    {
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
    public static function parseAttachmentForHtmlView($Attachment)
    {

        $UserModel = new UserModel();
        $InsertUser = $UserModel->GetID($Attachment['InsertUserID']);

        $Parsed = array();

        $Parsed['Icon'] = 'ticket';

        $Parsed['Title'] = T($Attachment['Type']) . ' &middot; ' . Anchor(
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

//         if ($Attachment['Type'] === 'salesforce-case') {
//            $Parsed['Fields']['Case Number'] = Anchor(htmlspecialchars($Attachment['CaseNumber']), $Attachment['SourceURL']);
//         } else {
//            $Parsed['Fields']['Name'] = Anchor(htmlspecialchars($Attachment['FirstName'].' '.$Attachment['LastName']), $Attachment['SourceURL']);
//         }
//
            $Parsed['Fields']['Status'] = $Attachment['Status'];
            $Parsed['Fields']['Last Updated'] = Gdn_Format::Date($Attachment['LastModifiedDate'], 'html');
//
//         if ($Attachment['Type'] === 'salesforce-case') {
//            $Parsed['Fields']['Priority'] = htmlspecialchars($Attachment['Priority']);
//         } else {
//            $Parsed['Fields']['Company'] = htmlspecialchars(GetValue('Company', $Attachment, ''));
//            $Title = GetValue('Title', $Attachment);
//            if ($Title) {
//               $Parsed['Fields']['Title'] = htmlspecialchars($Title);
//            }
//         }
        }

        return $Parsed;
    }
}
