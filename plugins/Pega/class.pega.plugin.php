<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
/**
 * Pega Plugin
 *
 * This plugin connects the forums to a Pega account; Once connected Staff users will
 * be able to Create Leads and Cases from Discussions and Comments in the forums.
 *
 */
class PegaPlugin extends Gdn_Plugin {

    /**
     * If status is set to this we will stop getting updates from Pega
     * @var string
     */
    protected $ClosedCaseStatusString = 'Closed';

    /**
     * If time since last update from Pega is less then this; we wont check for update - saving api calls.
     * @var int
     */
    protected $MinimumTimeForUpdate = 600;

    /**
     * Used in setup for Oauth.
     */
    const ProviderKey = 'Pega';


    /**
     * Setup the plugin
     *
     * @throws Gdn_UserException
     */
    public function Setup() {
        SaveToConfig('Garden.AttachmentsEnabled', true);
        $Error = '';
        if (!function_exists('curl_init')) {
            $Error = ConcatSep("\n", $Error, 'This plugin requires curl.');
        }
        if ($Error) {
            throw new Gdn_UserException($Error, 400);
        }
        // Save the provider type.
        Gdn::SQL()->Replace('UserAuthenticationProvider',
            array(
                'AuthenticationSchemeAlias' => 'Pega',
                'URL' => '...',
                'AssociationSecret' => '...',
                'AssociationHashMethod' => '...'
            ),
            array('AuthenticationKey' => self::ProviderKey), TRUE
        );
        Gdn::PermissionModel()->Define(array('Garden.Staff.Allow' => 'Garden.Moderation.Manage'));

        SaveToConfig('Plugins.Pega.CreateCases', true);
    }

    /**
     * @param Controller $Sender
     * @param array $Args
     */
    public function Base_GetConnections_Handler($Sender, $Args) {
        if (!Pega::IsConfigured()) {
            return;
        }
        //Staff Only
        if (!Gdn::Session()->CheckPermission('Garden.Staff.Allow')) {
            return;
        }
    }

    /**
     * @param ProfileController $Sender
     * @param string $UserReference
     * @param string $Username
     * @param bool $Code
     *
     */
    public function ProfileController_PegaConnect_Create($Sender, $UserReference = '', $Username = '', $Code = FALSE) {
        $Sender->Permission('Garden.SignIn.Allow');
        $Sender->GetUserInfo($UserReference, $Username, '', TRUE);
        $Sender->_SetBreadcrumbs(T('Connections'), UserUrl($Sender->User, '', 'connections'));
        //check $GET state // if DashboardConnection // then do global connection.
        $State = GetValue('state', $_GET, FALSE);
        if ($State == 'DashboardConnection') {
            try {
                $Tokens = Pega::GetTokens($Code, Pega::ProfileConnecUrl());
            } catch (Gdn_UserException $e) {
                $Message = $e->getMessage();
                Gdn::Dispatcher()->PassData('Exception', htmlspecialchars($Message))
                    ->Dispatch('home/error');
                return;
            }
            redirectTo('/plugin/Pega/?DashboardConnection=1&'.http_build_query($Tokens));
        }
        try {
            $Tokens = Pega::GetTokens($Code, Pega::ProfileConnecUrl());
        } catch (Gdn_UserException $e) {
            $Attributes = array(
                'RefreshToken' => NULL,
                'AccessToken' => NULL,
                'InstanceUrl' => NULL,
                'Profile' => NULL,
            );
            Gdn::UserModel()->SaveAttribute($Sender->User->UserID, Pega::ProviderKey, $Attributes);
            $Message = $e->getMessage();
            Gdn::Dispatcher()->PassData('Exception', htmlspecialchars($Message))
                ->Dispatch('home/error');
            return;
        }
        $AccessToken = GetValue('access_token', $Tokens);
        $InstanceUrl = GetValue('instance_url', $Tokens);
        $LoginID = GetValue('id', $Tokens);
        $RefreshToken = GetValue('refresh_token', $Tokens);
        $Pega = new Pega($AccessToken, $InstanceUrl);
        $Profile = $Pega->GetLoginProfile($LoginID);
        Gdn::UserModel()->SaveAuthentication(array(
            'UserID' => $Sender->User->UserID,
            'Provider' => Pega::ProviderKey,
            'UniqueID' => $Profile['id']
        ));
        $Attributes = array(
            'RefreshToken' => $RefreshToken,
            'AccessToken' => $AccessToken,
            'InstanceUrl' => $InstanceUrl,
            'Profile' => $Profile,
        );
        Gdn::UserModel()->SaveAttribute($Sender->User->UserID, Pega::ProviderKey, $Attributes);
        $this->EventArguments['Provider'] = Pega::ProviderKey;
        $this->EventArguments['User'] = $Sender->User;
        $this->FireEvent('AfterConnection');

        $RedirectUrl = UserUrl($Sender->User, '', 'connections');

        redirectTo($RedirectUrl);
    }

    /**
     * Creates the Virtual Controller
     *
     * @param DashboardController $Sender
     */
    public function PluginController_Pega_Create($Sender) {
        $Sender->Permission('Garden.Settings.Manage');
        $Sender->Title('Pega');
        $Sender->AddSideMenu('plugin/Pega');
        $Sender->Form = new Gdn_Form();
        $this->Dispatch($Sender, $Sender->RequestArgs);
    }

    /**
     * Redirect to allow for DashboardConnection
     */
    public function Controller_Connect() {
        $AuthorizeUrl = Pega::AuthorizeUri(FALSE, 'DashboardConnection');
        redirectTo($AuthorizeUrl, 302, false);
    }

    /**
     * Redirect to allow for DashboardConnection
     */
    public function Controller_Disconnect() {
        $Pega = Pega::Instance();
        $Pega->UseDashboardConnection();
        $Token = GetValue('token', $_GET, FALSE);
        if ($Token) {
            $Pega->Revoke($Token);
            RemoveFromConfig(array(
                'Plugins.Pega.DashboardConnection.Token' => FALSE,
                'Plugins.Pega.DashboardConnection.RefreshToken' => FALSE,
                'Plugins.Pega.DashboardConnection.Token' => FALSE,
                'Plugins.Pega.DashboardConnection.InstanceUrl' => FALSE
            ));
        }
        redirectTo('/plugin/Pega');
    }


    public function Controller_Enable() {
        SaveToConfig('Plugins.Pega.DashboardConnection.Enabled', TRUE);
        redirectTo('/plugin/Pega');
    }

    public function Controller_Disable() {
        RemoveFromConfig('Plugins.Pega.DashboardConnection.Enabled');
        redirectTo('/plugin/Pega');
    }

    /**
     * Dashboard Settings
     * Default method of virtual Pega controller.
     *
     * @param DashboardController $Sender
     */
    public function Controller_Index($Sender) {
        $Pega = Pega::Instance();
        if (GetValue('DashboardConnection', $_GET, FALSE)) {
            $Sender->SetData('DashboardConnection', TRUE);
            SaveToConfig(array(
                'Plugins.Pega.DashboardConnection.Enabled' => TRUE,
                'Plugins.Pega.DashboardConnection.LoginId' => GetValue('id', $_GET),
                'Plugins.Pega.DashboardConnection.InstanceUrl' => GetValue('instance_url', $_GET),
                'Plugins.Pega.DashboardConnection.Token' => GetValue('access_token', $_GET),
                'Plugins.Pega.DashboardConnection.RefreshToken' => GetValue('refresh_token', $_GET),
            ));

            $Sender->InformMessage('Changes Saved to Config');
            redirectTo('/plugin/Pega');
        }
        $Sender->SetData(array(
            'DashboardConnection' => C('Plugins.Pega.DashboardConnection.Enabled'),
            'DashboardConnectionProfile' => FALSE,
            'DashboardConnectionToken' => C('Plugins.Pega.DashboardConnection.Token', FALSE),
            'DashboardConnectionRefreshToken' => C('Plugins.Pega.DashboardConnection.RefreshToken', FALSE)
        ));
        if (C('Plugins.Pega.DashboardConnection.LoginId') && C('Plugins.Pega.DashboardConnection.Enabled')) {
//         $Pega->UseDashboardConnection();
            $DashboardConnectionProfile = $Pega->GetLoginProfile(C('Plugins.Pega.DashboardConnection.LoginId'));
            $Sender->SetData('DashboardConnectionProfile', $DashboardConnectionProfile);
            $Sender->AddCssFile('admin.css');
        }
        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->SetField(array(
            'Plugins.Pega.ApplicationID',
            'Plugins.Pega.Secret',
            'Plugins.Pega.AuthenticationUrl',
        ));
        // Set the model on the form.
        $Sender->Form->SetModel($ConfigurationModel);
        // If seeing the form for the first time...
        if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
            // Apply the config settings to the form.
            $Sender->Form->SetData($ConfigurationModel->Data);
        } else {
            $FormValues = $Sender->Form->FormValues();
            if ($Sender->Form->IsPostBack()) {
                $Sender->Form->ValidateRule('Plugins.Pega.ApplicationID', 'function:ValidateRequired', 'ApplicationID is required');
                $Sender->Form->ValidateRule('Plugins.Pega.Secret', 'function:ValidateRequired', 'Secret is required');
                $Sender->Form->ValidateRule('Plugins.Pega.AuthenticationUrl', 'function:ValidateRequired', 'Authentication Url is required');
                if ($Sender->Form->ErrorCount() == 0) {
                    SaveToConfig('Plugins.Pega.ApplicationID', trim($FormValues['Plugins.Pega.ApplicationID']));
                    SaveToConfig('Plugins.Pega.Secret', trim($FormValues['Plugins.Pega.Secret']));
                    SaveToConfig('Plugins.Pega.AuthenticationUrl', rtrim(trim($FormValues['Plugins.Pega.AuthenticationUrl'])), '/');

                    $Sender->InformMessage(T("Your changes have been saved."));
                } else {
                    $Sender->InformMessage(T("Error saving settings to config."));
                }
            }
        }
        $Sender->Form->SetValue('Plugins.Pega.ApplicationID', C('Plugins.Pega.ApplicationID'));
        $Sender->Form->SetValue('Plugins.Pega.Secret', C('Plugins.Pega.Secret'));
        $Sender->Form->SetValue('Plugins.Pega.AuthenticationUrl', C('Plugins.Pega.AuthenticationUrl'));
        $Sender->Render($this->GetView('dashboard.php'));
    }

    /**
     * @param DiscussionController $Sender
     * @param array $Args
     */
    public function DiscussionController_DiscussionOptions_Handler($Sender, $Args) {
        //Staff Only
        $Session = Gdn::Session();
        if (!$Session->CheckPermission('Garden.Staff.Allow')) {
            return;
        }

        $Pega = Pega::Instance();

        $UserID = $Args['Discussion']->InsertUserID;
        $DiscussionID = $Args['Discussion']->DiscussionID;

        $Css_class = null;

        if (isset($Args['DiscussionOptions'])) {

            if (C('Plugins.Pega.CreateCases')) {
                $Args['DiscussionOptions']['PegaCase'] = array(
                    'Label' => T('Pega - Create Case'),
                    'Url' => "/discussion/PegaCase/Discussion/$DiscussionID/$UserID",
                    'Class' => 'Popup'.$Css_class
                );
            }
            //remove create Create already created
            $Attachments = GetValue('Attachments', $Args['Discussion'], array());
            foreach ($Attachments as $Attachment) {
                if ($Attachment['Type'] == 'pega-case') {
                    unset($Args['DiscussionOptions']['PegaCase']);
                }
            }
        }

    }

    /**
     * @param CommentController $Sender
     * @param $Args
     */
    public function DiscussionController_CommentOptions_Handler($Sender, $Args) {
        //Staff Only
        $Session = Gdn::Session();
        if (!$Session->CheckPermission('Garden.Staff.Allow')) {
            return;
        }

        $Pega = Pega::Instance();

        $UserID = $Args['Comment']->InsertUserID;
        $CommentID = $Args['Comment']->CommentID;
        $Css_class = null;


        if (C('Plugins.Pega.CreateCases')) {
            $Args['CommentOptions']['PegaCase'] = array(
                'Label' => T('Pega - Create Case'),
                'Url' => "/discussion/PegaCase/Comment/$CommentID/$UserID",
                'Class' => 'Popup'.$Css_class
            );
        }
        //remove create Create already created
        $Attachments = GetValue('Attachments', $Args['Comment'], array());
        foreach ($Attachments as $Attachment) {
            if ($Attachment['Type'] == 'pega-case') {
                unset($Args['CommentOptions']['PegaCase']);
            }
        }
    }

    /**
     * Popup to Add Pega Case
     *
     * @param DiscussionController $Sender
     * @param array $Args
     * @throws Gdn_UserException
     * @throws Exception
     *
     */
    public function DiscussionController_PegaCase_Create($Sender, $Args) {

        // Signed in users only.
        if (!(Gdn::Session()->IsValid())) {
            throw PermissionException('Garden.Signin.Allow');
        }
        //Permissions
        $Sender->Permission('Garden.Staff.Allow');
        // Check that we are connected to Pega
        $Pega = Pega::Instance();

        //Get Request Arguments
        $Arguments = $Sender->RequestArgs;
        if (sizeof($Arguments) != 3) {
            throw new Gdn_UserException('Invalid Request Url');
        }
        $Type = $Arguments[0];
        $ElementID = $Arguments[1];
        $UserID = $Arguments[2];
        //Get User
        $User = Gdn::UserModel()->GetID($UserID);
        //Setup Form
        $Sender->Form = new Gdn_Form();
        //Get Content
        if ($Type == 'Discussion') {
            $Content = $Sender->DiscussionModel->GetID($ElementID);
            $Url = DiscussionUrl($Content, 1);
        } elseif ($Type == 'Comment') {
            $CommentModel = new CommentModel();
            $Content = $CommentModel->GetID($ElementID);
            $Url = CommentUrl($Content);
        } else {
            throw new Gdn_UserException('Content Type not supported');
        }
        $Sender->Form->AddHidden('SourceUri', $Url);

        $AttachmentModel = AttachmentModel::Instance();

        //If form is being submitted
        if ($Sender->Form->IsPostBack() && $Sender->Form->AuthenticatedPostBack() === TRUE) {
            //Form Validation
            $Sender->Form->ValidateRule('FirstName', 'function:ValidateRequired', 'First Name is required');
            $Sender->Form->ValidateRule('LastName', 'function:ValidateRequired', 'Last Name is required');
            $Sender->Form->ValidateRule('Email', 'function:ValidateRequired', 'Email is required');
            //if no errors
            if ($Sender->Form->ErrorCount() == 0 && $AttachmentModel->Validate($Sender->Form->FormValues())) {
                $FormValues = $Sender->Form->FormValues();

                //check to see if user is a contact
                //$Contact = $Pega->FindContact($FormValues['Email']); // presume that the user is a contact
//            if (!$Contact['Id']) {
//               $this->Reconnect($Sender, $Args);
//               //If not a contact then add contact
//               $Contact['Id'] = $Pega->CreateContact(array(
//                  'FirstName' => $FormValues['FirstName'],
//                  'LastName' => $FormValues['LastName'],
//                  'Email' => $FormValues['Email'],
//                  'LeadSource' => $FormValues['LeadSource'],
//               ));
//            }

                //Create Case using Pega API
                $CaseID = $Pega->CreateCase(array(
                    'Status' => 1,
                    'Subject' => $Sender->DiscussionModel->GetID($Content->DiscussionID)->Name,
                    'Description' => $FormValues['Body'],
                    'Vanilla__ForumUrl__c' => $FormValues['SourceUri']
                ));
                //Save information to our Attachment Table
                $ID = $AttachmentModel->Save(array(
                    'Type' => 'pega-case',
                    'ForeignID' => $AttachmentModel->RowID($Content),
                    'ForeignUserID' => $Content->InsertUserID,
                    'Source' => 'Pega',
                    'SourceID' => $CaseID,
                    'SourceURL' => C('Plugins.Pega.BaseUrl').'/forum/interaction/'.$CaseID,
                    'Status' => "N/A",
                    'Priority' => "N/A", // pega does not need these
                ));
                if (!$ID) {
                    $Sender->Form->SetValidationResults($AttachmentModel->ValidationResults());
                }
                $Sender->JsonTarget('', $Url, 'Redirect');
                $Sender->InformMessage('Case Added to Pega');

            }

        } else {
            $Sender->Form->SetValidationResults($AttachmentModel->ValidationResults());
        }
        list($FirstName, $LastName) = $this->GetFirstNameLastName($User->Name);

        try {
            $Data = array(
                'DiscussionID' => $Content->DiscussionID,
                'FirstName' => $FirstName,
                'LastName' => $LastName,
                'Email' => $User->Email,
                'Body' => Gdn_Format::TextEx($Content->Body)
            );
        } catch (Gdn_UserException $e) {
            $this->Reconnect($Sender, $Args);
        }

        $Sender->Form->SetData($Data);
        $Sender->SetData('Data', $Data);
        $Sender->Render('createcase', '', 'plugins/Pega');

    }

    /**
     * @param DiscussionController $Sender
     * @param array $Args
     */
    public function DiscussionController_AfterDiscussionBody_Handler($Sender, $Args) {
        Trace('DiscussionController_AfterDiscussionBody_Handler');
        $this->WriteAndUpdateAttachments($Sender, $Args);
    }

    /**
     * @param DiscussionController $Sender
     * @param array $Args
     */
    public function DiscussionController_AfterCommentBody_Handler($Sender, $Args) {
        Trace('AfterCommentBody_Handler');
        $this->WriteAndUpdateAttachments($Sender, $Args);
    }


    protected function WriteAndUpdateAttachments($Sender, $Args) {

//        error_log("WriteAndUpdateAttachments\n\n\n", 3, "/Users/patrick/my-errors.log");

        $Type = GetValue('Type', $Args);

        if ($Type == 'Discussion') {
            $Content = 'Discussion';
        } elseif ($Type == 'Comment') {
            $Content = 'Comment';
        } else {
            return;
        }
        $Session = Gdn::Session();
        if (!$Session->CheckPermission('Garden.SignIn.Allow')) {
            return;
        }

        if (!$Session->CheckPermission('Garden.Staff.Allow') && $Session->IsValid() && isset($Args[$Content]->Attachments)) {
            foreach ($Args[$Content]->Attachments as $Attachment) {
                if ($Attachment['Type'] == 'pega-case') {;
                    if ($Attachment['ForeignUserID'] == $Session->UserID) {
//                        error_log("WriteGenericAttachment line 529\n\n\n", 3, "/Users/patrick/my-errors.log");
                        WriteGenericAttachment(array(
                            'Icon' => 'case',
                            'Body' => Wrap(T('A ticket has been generated from this post.'), 'p'),
                            'Fields' => array(
                                'one' => 'two'
                            )
                        ));

                    } else {
//                        error_log("WriteGenericAttachment line 540\n\n\n", 3, "/Users/patrick/my-errors.log");
                        WriteGenericAttachment(array(
                            'Icon' => 'case',
                            'Body' => Wrap(T('A case has been generated from this post.'), 'p'),
                        ));
                    }
                }
            }
            return;
        }
        if (!$Session->CheckPermission('Garden.Staff.Allow')) {
//            error_log("WriteAttachment aborted, no permissions\n\n\n", 3, "/Users/patrick/my-errors.log");
            return;
        }

        if (isset($Args[$Content]->Attachments)) {
//            error_log("Attempt to update attachments. " . json_encode($Args[$Content]->Attachments) . "\n\n\n", 3, "/Users/patrick/my-errors.log");
            try {
                $this->UpdateAttachments($Args[$Content]->Attachments, $Sender, $Args);
            } catch (Gdn_UserException $e) {
                $Sender->InformMessage('Error Reconnecting to Pega');
            }
//            error_log("Write attachments. " . json_encode($Args[$Content]->Attachments) . "\n\n\n", 3, "/Users/patrick/my-errors.log");
//            WriteAttachments($Args[$Content]->Attachments);
        }
    }

    /**
     * @param Gdn_Controller $sender
     */
    public function settingsController_pega_create($sender) {
        $sender->Permission('Garden.Settings.Manage');

        $cf = new ConfigurationModule($sender);

        $cf->Initialize(
            array(
                'Plugins.Pega.BaseUrl' => [],
                'Plugins.Pega.Username' => [],
                'Plugins.Pega.Password' => []
            )
        );

        $sender->AddSideMenu();
        $sender->SetData('Title', T('Pega Settings'));
        $cf->RenderAll();
    }

    /**
     * @param array $Attachments
     * @param $Sender
     * @param array $Args
     */
    protected function UpdateAttachments(&$Attachments, $Sender, $Args) {
        return; //temporarily disable this functionality; response time too slow
        $Pega = Pega::Instance();
        $AttachmentModel = new AttachmentModel();

        foreach ($Attachments as &$Attachment) {

            if (!$this->IsToBeUpdated($Attachment)) {
                continue;
            }

            $CaseResponse = $Pega->GetCase($Attachment['SourceID']);
//            $CaseResponse['HttpCode'] = 200;
//            $CaseResponse['Status'] = 1;
//            $CaseResponse['Priority'] = 1;
//            $CaseResponse['LastModifiedDate'] = date();
//            $CaseResponse['CaseNumber'] = 10;


            $UpdatedAttachment = (array)$AttachmentModel->GetID($Attachment['AttachmentID']);

            if ($CaseResponse['HttpCode'] == 401) {
                $this->Reconnect($Sender, $Args);
                continue;
            } elseif ($CaseResponse['HttpCode'] == 404) {
                $UpdatedAttachment['DateUpdated'] = Gdn_Format::ToDateTime();
                $UpdatedAttachment['Error'] = T('Case has been deleted from Pega');
                $AttachmentModel->Save($UpdatedAttachment);
                $Attachment = $UpdatedAttachment;
                continue;
            } elseif ($CaseResponse['HttpCode'] == 200) {
//                error_log("Saving attachement" . json_encode($CaseResponse) . "\n\n\n", 3, "/Users/patrick/my-errors.log");
                $Case = $CaseResponse['Response'];
                $UpdatedAttachment['Status'] = $Case['pyWorkObjectStatus'];
                $UpdatedAttachment['Priority'] = 1;
                $UpdatedAttachment['LastModifiedDate'] = Gdn_Format::ToDateTime();
                $UpdatedAttachment['CaseNumber'] = $Case['pyID'];
                $UpdatedAttachment['DateUpdated'] = Gdn_Format::ToDateTime();
                $AttachmentModel->Save($UpdatedAttachment);
                $Attachment = $UpdatedAttachment;
            }
        }
    }

    /**
     * @param ProfileController $Sender
     * @param array $Args
     */
    public function ProfileController_Render_Before($Sender, $Args) {
        $AttachmentModel = AttachmentModel::Instance();
        $AttachmentModel->JoinAttachmentsToUser($Sender, $Args, array('Type' => 'pega-lead'), 1);
    }

    /**
     * @param ProfileController $Sender
     * @param array $Args
     */
    public function ProfileController_AfterUserInfo_Handler($Sender, $Args) {
        //check permissions
        if (!Gdn::Session()->CheckPermission('Garden.Staff.Allow')) {
            return;
        }
        $Sender->AddCssFile('vanillicon.css', 'static');
        $Attachments = $Sender->Data['Attachments'];
        if ($Sender->DeliveryMethod() === DELIVERY_METHOD_XHTML) {
            require_once $Sender->FetchViewLocation('attachment', '', 'plugins/Pega');
        }
        foreach ($Attachments as $Attachment) {
            if ($Attachment['Type'] == 'pega-lead') {
                WritePegaLeadAttachment($Attachment);
            }
        }
    }

    /**
     * Take a Full Name and attempt to split it into FirstName LastName
     *
     * @param string $FullName
     * @return array $Name
     *    [FirstName]
     *    [LastName]
     */
    public function GetFirstNameLastName($FullName) {
        $NameParts = explode(' ', $FullName);
        switch (count($NameParts)) {
            case 3:
                $FirstName = $NameParts[0].' '.$NameParts[1];
                $LastName = $NameParts[2];
                break;
            case 2:
                $FirstName = $NameParts[0];
                $LastName = $NameParts[1];
                break;
            default:
                $FirstName = $FullName;
                $LastName = '';
                break;
        }
        return array($FirstName, $LastName);
    }

    /**
     * @param AssetModel $Sender
     */
    public function AssetModel_StyleCss_Handler($Sender) {
        $Sender->AddCssFile('pega.css', 'plugins/Pega');
    }

    /**
     * @param array $Attachment Attachment Data - see AttachmentModel
     * @param string $Type case or lead
     * @return bool
     */
    protected function IsToBeUpdated($Attachment, $Type = 'pega-case') {
        if (GetValue('Status', $Attachment) == $this->ClosedCaseStatusString) {
            return FALSE;
        }
        $TimeDiff = time() - strtotime($Attachment['DateUpdated']);
        if ($TimeDiff < $this->MinimumTimeForUpdate) {
            Trace("Not Checking For Update: $TimeDiff seconds since last update");
            return FALSE;
        }
        if (isset($Attachment['LastModifiedDate'])) {
            if ($Type == 'pega-case') {
                $TimeDiff = time() - strtotime($Attachment['LastModifiedDate']);
                if ($TimeDiff < $this->MinimumTimeForUpdate && $Attachment['Status'] != $this->ClosedCaseStatusString) {
                    Trace("Not Checking For Update: $TimeDiff seconds since last update");
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    /**
     * Add attachment views.
     *
     * @param DiscussionController $Sender Sending Controller.
     */
    public function DiscussionController_FetchAttachmentViews_Handler($Sender) {
        require_once $Sender->FetchViewLocation('attachment', '', 'plugins/Pega');
    }

    public function isConfigured() {
        $Pega = Pega::Instance();
        return $Pega->IsConfigured();
    }

}
