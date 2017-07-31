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
        $error = '';
        if (!function_exists('curl_init')) {
            $error = ConcatSep("\n", $error, 'This plugin requires curl.');
        }
        if ($error) {
            throw new Gdn_UserException($error, 400);
        }
        // Save the provider type.
        Gdn::SQL()->Replace('UserAuthenticationProvider',
            [
                'AuthenticationSchemeAlias' => 'Pega',
                'URL' => '...',
                'AssociationSecret' => '...',
                'AssociationHashMethod' => '...'
            ],
            ['AuthenticationKey' => self::ProviderKey], TRUE
        );
        Gdn::PermissionModel()->Define(['Garden.Staff.Allow' => 'Garden.Moderation.Manage']);

        SaveToConfig('Plugins.Pega.CreateCases', true);
    }

    /**
     * @param Controller $sender
     * @param array $args
     */
    public function Base_GetConnections_Handler($sender, $args) {
        if (!Pega::IsConfigured()) {
            return;
        }
        //Staff Only
        if (!Gdn::Session()->CheckPermission('Garden.Staff.Allow')) {
            return;
        }
    }

    /**
     * @param ProfileController $sender
     * @param string $userReference
     * @param string $username
     * @param bool $code
     *
     */
    public function ProfileController_PegaConnect_Create($sender, $userReference = '', $username = '', $code = FALSE) {
        $sender->Permission('Garden.SignIn.Allow');
        $sender->GetUserInfo($userReference, $username, '', TRUE);
        $sender->_SetBreadcrumbs(T('Connections'), UserUrl($sender->User, '', 'connections'));
        //check $GET state // if DashboardConnection // then do global connection.
        $state = GetValue('state', $_GET, FALSE);
        if ($state == 'DashboardConnection') {
            try {
                $tokens = Pega::GetTokens($code, Pega::ProfileConnecUrl());
            } catch (Gdn_UserException $e) {
                $message = $e->getMessage();
                Gdn::Dispatcher()->PassData('Exception', htmlspecialchars($message))
                    ->Dispatch('home/error');
                return;
            }
            redirectTo('/plugin/Pega/?DashboardConnection=1&'.http_build_query($tokens));
        }
        try {
            $tokens = Pega::GetTokens($code, Pega::ProfileConnecUrl());
        } catch (Gdn_UserException $e) {
            $attributes = [
                'RefreshToken' => NULL,
                'AccessToken' => NULL,
                'InstanceUrl' => NULL,
                'Profile' => NULL,
            ];
            Gdn::UserModel()->SaveAttribute($sender->User->UserID, Pega::ProviderKey, $attributes);
            $message = $e->getMessage();
            Gdn::Dispatcher()->PassData('Exception', htmlspecialchars($message))
                ->Dispatch('home/error');
            return;
        }
        $accessToken = GetValue('access_token', $tokens);
        $instanceUrl = GetValue('instance_url', $tokens);
        $loginID = GetValue('id', $tokens);
        $refreshToken = GetValue('refresh_token', $tokens);
        $pega = new Pega($accessToken, $instanceUrl);
        $profile = $pega->GetLoginProfile($loginID);
        Gdn::UserModel()->SaveAuthentication([
            'UserID' => $sender->User->UserID,
            'Provider' => Pega::ProviderKey,
            'UniqueID' => $profile['id']
        ]);
        $attributes = [
            'RefreshToken' => $refreshToken,
            'AccessToken' => $accessToken,
            'InstanceUrl' => $instanceUrl,
            'Profile' => $profile,
        ];
        Gdn::UserModel()->SaveAttribute($sender->User->UserID, Pega::ProviderKey, $attributes);
        $this->EventArguments['Provider'] = Pega::ProviderKey;
        $this->EventArguments['User'] = $sender->User;
        $this->FireEvent('AfterConnection');

        $redirectUrl = UserUrl($sender->User, '', 'connections');

        redirectTo($redirectUrl);
    }

    /**
     * Creates the Virtual Controller
     *
     * @param DashboardController $sender
     */
    public function PluginController_Pega_Create($sender) {
        $sender->Permission('Garden.Settings.Manage');
        $sender->Title('Pega');
        $sender->AddSideMenu('plugin/Pega');
        $sender->Form = new Gdn_Form();
        $this->Dispatch($sender, $sender->RequestArgs);
    }

    /**
     * Redirect to allow for DashboardConnection
     */
    public function Controller_Connect() {
        $authorizeUrl = Pega::AuthorizeUri(FALSE, 'DashboardConnection');
        redirectTo($authorizeUrl, 302, false);
    }

    /**
     * Redirect to allow for DashboardConnection
     */
    public function Controller_Disconnect() {
        $pega = Pega::Instance();
        $pega->UseDashboardConnection();
        $token = GetValue('token', $_GET, FALSE);
        if ($token) {
            $pega->Revoke($token);
            RemoveFromConfig([
                'Plugins.Pega.DashboardConnection.Token' => FALSE,
                'Plugins.Pega.DashboardConnection.RefreshToken' => FALSE,
                'Plugins.Pega.DashboardConnection.Token' => FALSE,
                'Plugins.Pega.DashboardConnection.InstanceUrl' => FALSE
            ]);
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
     * @param DashboardController $sender
     */
    public function Controller_Index($sender) {
        $pega = Pega::Instance();
        if (GetValue('DashboardConnection', $_GET, FALSE)) {
            $sender->SetData('DashboardConnection', TRUE);
            SaveToConfig([
                'Plugins.Pega.DashboardConnection.Enabled' => TRUE,
                'Plugins.Pega.DashboardConnection.LoginId' => GetValue('id', $_GET),
                'Plugins.Pega.DashboardConnection.InstanceUrl' => GetValue('instance_url', $_GET),
                'Plugins.Pega.DashboardConnection.Token' => GetValue('access_token', $_GET),
                'Plugins.Pega.DashboardConnection.RefreshToken' => GetValue('refresh_token', $_GET),
            ]);

            $sender->InformMessage('Changes Saved to Config');
            redirectTo('/plugin/Pega');
        }
        $sender->SetData([
            'DashboardConnection' => C('Plugins.Pega.DashboardConnection.Enabled'),
            'DashboardConnectionProfile' => FALSE,
            'DashboardConnectionToken' => C('Plugins.Pega.DashboardConnection.Token', FALSE),
            'DashboardConnectionRefreshToken' => C('Plugins.Pega.DashboardConnection.RefreshToken', FALSE)
        ]);
        if (C('Plugins.Pega.DashboardConnection.LoginId') && C('Plugins.Pega.DashboardConnection.Enabled')) {
//         $Pega->UseDashboardConnection();
            $dashboardConnectionProfile = $pega->GetLoginProfile(C('Plugins.Pega.DashboardConnection.LoginId'));
            $sender->SetData('DashboardConnectionProfile', $dashboardConnectionProfile);
            $sender->AddCssFile('admin.css');
        }
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->SetField([
            'Plugins.Pega.ApplicationID',
            'Plugins.Pega.Secret',
            'Plugins.Pega.AuthenticationUrl',
        ]);
        // Set the model on the form.
        $sender->Form->SetModel($configurationModel);
        // If seeing the form for the first time...
        if ($sender->Form->AuthenticatedPostBack() === FALSE) {
            // Apply the config settings to the form.
            $sender->Form->SetData($configurationModel->Data);
        } else {
            $formValues = $sender->Form->FormValues();
            if ($sender->Form->IsPostBack()) {
                $sender->Form->ValidateRule('Plugins.Pega.ApplicationID', 'function:ValidateRequired', 'ApplicationID is required');
                $sender->Form->ValidateRule('Plugins.Pega.Secret', 'function:ValidateRequired', 'Secret is required');
                $sender->Form->ValidateRule('Plugins.Pega.AuthenticationUrl', 'function:ValidateRequired', 'Authentication Url is required');
                if ($sender->Form->ErrorCount() == 0) {
                    SaveToConfig('Plugins.Pega.ApplicationID', trim($formValues['Plugins.Pega.ApplicationID']));
                    SaveToConfig('Plugins.Pega.Secret', trim($formValues['Plugins.Pega.Secret']));
                    SaveToConfig('Plugins.Pega.AuthenticationUrl', rtrim(trim($formValues['Plugins.Pega.AuthenticationUrl'])), '/');

                    $sender->InformMessage(T("Your changes have been saved."));
                } else {
                    $sender->InformMessage(T("Error saving settings to config."));
                }
            }
        }
        $sender->Form->SetValue('Plugins.Pega.ApplicationID', C('Plugins.Pega.ApplicationID'));
        $sender->Form->SetValue('Plugins.Pega.Secret', C('Plugins.Pega.Secret'));
        $sender->Form->SetValue('Plugins.Pega.AuthenticationUrl', C('Plugins.Pega.AuthenticationUrl'));
        $sender->Render($this->GetView('dashboard.php'));
    }

    /**
     * @param DiscussionController $sender
     * @param array $args
     */
    public function DiscussionController_DiscussionOptions_Handler($sender, $args) {
        //Staff Only
        $session = Gdn::Session();
        if (!$session->CheckPermission('Garden.Staff.Allow')) {
            return;
        }

        $pega = Pega::Instance();

        $userID = $args['Discussion']->InsertUserID;
        $discussionID = $args['Discussion']->DiscussionID;

        $css_class = null;

        if (isset($args['DiscussionOptions'])) {

            if (C('Plugins.Pega.CreateCases')) {
                $args['DiscussionOptions']['PegaCase'] = [
                    'Label' => T('Pega - Create Case'),
                    'Url' => "/discussion/PegaCase/Discussion/$discussionID/$userID",
                    'Class' => 'Popup'.$css_class
                ];
            }
            //remove create Create already created
            $attachments = GetValue('Attachments', $args['Discussion'], []);
            foreach ($attachments as $attachment) {
                if ($attachment['Type'] == 'pega-case') {
                    unset($args['DiscussionOptions']['PegaCase']);
                }
            }
        }

    }

    /**
     * @param CommentController $sender
     * @param $args
     */
    public function DiscussionController_CommentOptions_Handler($sender, $args) {
        //Staff Only
        $session = Gdn::Session();
        if (!$session->CheckPermission('Garden.Staff.Allow')) {
            return;
        }

        $pega = Pega::Instance();

        $userID = $args['Comment']->InsertUserID;
        $commentID = $args['Comment']->CommentID;
        $css_class = null;


        if (C('Plugins.Pega.CreateCases')) {
            $args['CommentOptions']['PegaCase'] = [
                'Label' => T('Pega - Create Case'),
                'Url' => "/discussion/PegaCase/Comment/$commentID/$userID",
                'Class' => 'Popup'.$css_class
            ];
        }
        //remove create Create already created
        $attachments = GetValue('Attachments', $args['Comment'], []);
        foreach ($attachments as $attachment) {
            if ($attachment['Type'] == 'pega-case') {
                unset($args['CommentOptions']['PegaCase']);
            }
        }
    }

    /**
     * Popup to Add Pega Case
     *
     * @param DiscussionController $sender
     * @param array $args
     * @throws Gdn_UserException
     * @throws Exception
     *
     */
    public function DiscussionController_PegaCase_Create($sender, $args) {

        // Signed in users only.
        if (!(Gdn::Session()->IsValid())) {
            throw PermissionException('Garden.Signin.Allow');
        }
        //Permissions
        $sender->Permission('Garden.Staff.Allow');
        // Check that we are connected to Pega
        $pega = Pega::Instance();

        //Get Request Arguments
        $arguments = $sender->RequestArgs;
        if (sizeof($arguments) != 3) {
            throw new Gdn_UserException('Invalid Request Url');
        }
        $type = $arguments[0];
        $elementID = $arguments[1];
        $userID = $arguments[2];
        //Get User
        $user = Gdn::UserModel()->GetID($userID);
        //Setup Form
        $sender->Form = new Gdn_Form();
        //Get Content
        if ($type == 'Discussion') {
            $content = $sender->DiscussionModel->GetID($elementID);
            $url = DiscussionUrl($content, 1);
        } elseif ($type == 'Comment') {
            $commentModel = new CommentModel();
            $content = $commentModel->GetID($elementID);
            $url = CommentUrl($content);
        } else {
            throw new Gdn_UserException('Content Type not supported');
        }
        $sender->Form->AddHidden('SourceUri', $url);

        $attachmentModel = AttachmentModel::Instance();

        //If form is being submitted
        if ($sender->Form->IsPostBack() && $sender->Form->AuthenticatedPostBack() === TRUE) {
            //Form Validation
            $sender->Form->ValidateRule('FirstName', 'function:ValidateRequired', 'First Name is required');
            $sender->Form->ValidateRule('LastName', 'function:ValidateRequired', 'Last Name is required');
            $sender->Form->ValidateRule('Email', 'function:ValidateRequired', 'Email is required');
            //if no errors
            if ($sender->Form->ErrorCount() == 0 && $attachmentModel->Validate($sender->Form->FormValues())) {
                $formValues = $sender->Form->FormValues();

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
                $caseID = $pega->CreateCase([
                    'Status' => 1,
                    'Subject' => $sender->DiscussionModel->GetID($content->DiscussionID)->Name,
                    'Description' => $formValues['Body'],
                    'Vanilla__ForumUrl__c' => $formValues['SourceUri']
                ]);
                //Save information to our Attachment Table
                $iD = $attachmentModel->Save([
                    'Type' => 'pega-case',
                    'ForeignID' => $attachmentModel->RowID($content),
                    'ForeignUserID' => $content->InsertUserID,
                    'Source' => 'Pega',
                    'SourceID' => $caseID,
                    'SourceURL' => C('Plugins.Pega.BaseUrl').'/forum/interaction/'.$caseID,
                    'Status' => "N/A",
                    'Priority' => "N/A", // pega does not need these
                ]);
                if (!$iD) {
                    $sender->Form->SetValidationResults($attachmentModel->ValidationResults());
                }
                $sender->JsonTarget('', $url, 'Redirect');
                $sender->InformMessage('Case Added to Pega');

            }

        } else {
            $sender->Form->SetValidationResults($attachmentModel->ValidationResults());
        }
        list($firstName, $lastName) = $this->GetFirstNameLastName($user->Name);

        try {
            $data = [
                'DiscussionID' => $content->DiscussionID,
                'FirstName' => $firstName,
                'LastName' => $lastName,
                'Email' => $user->Email,
                'Body' => Gdn_Format::TextEx($content->Body)
            ];
        } catch (Gdn_UserException $e) {
            $this->Reconnect($sender, $args);
        }

        $sender->Form->SetData($data);
        $sender->SetData('Data', $data);
        $sender->Render('createcase', '', 'plugins/Pega');

    }

    /**
     * @param DiscussionController $sender
     * @param array $args
     */
    public function DiscussionController_AfterDiscussionBody_Handler($sender, $args) {
        Trace('DiscussionController_AfterDiscussionBody_Handler');
        $this->WriteAndUpdateAttachments($sender, $args);
    }

    /**
     * @param DiscussionController $sender
     * @param array $args
     */
    public function DiscussionController_AfterCommentBody_Handler($sender, $args) {
        Trace('AfterCommentBody_Handler');
        $this->WriteAndUpdateAttachments($sender, $args);
    }


    protected function WriteAndUpdateAttachments($sender, $args) {

//        error_log("WriteAndUpdateAttachments\n\n\n", 3, "/Users/patrick/my-errors.log");

        $type = GetValue('Type', $args);

        if ($type == 'Discussion') {
            $content = 'Discussion';
        } elseif ($type == 'Comment') {
            $content = 'Comment';
        } else {
            return;
        }
        $session = Gdn::Session();
        if (!$session->CheckPermission('Garden.SignIn.Allow')) {
            return;
        }

        if (!$session->CheckPermission('Garden.Staff.Allow') && $session->IsValid() && isset($args[$content]->Attachments)) {
            foreach ($args[$content]->Attachments as $attachment) {
                if ($attachment['Type'] == 'pega-case') {;
                    if ($attachment['ForeignUserID'] == $session->UserID) {
//                        error_log("WriteGenericAttachment line 529\n\n\n", 3, "/Users/patrick/my-errors.log");
                        WriteGenericAttachment([
                            'Icon' => 'case',
                            'Body' => Wrap(T('A ticket has been generated from this post.'), 'p'),
                            'Fields' => [
                                'one' => 'two'
                            ]
                        ]);

                    } else {
//                        error_log("WriteGenericAttachment line 540\n\n\n", 3, "/Users/patrick/my-errors.log");
                        WriteGenericAttachment([
                            'Icon' => 'case',
                            'Body' => Wrap(T('A case has been generated from this post.'), 'p'),
                        ]);
                    }
                }
            }
            return;
        }
        if (!$session->CheckPermission('Garden.Staff.Allow')) {
//            error_log("WriteAttachment aborted, no permissions\n\n\n", 3, "/Users/patrick/my-errors.log");
            return;
        }

        if (isset($args[$content]->Attachments)) {
//            error_log("Attempt to update attachments. " . json_encode($Args[$Content]->Attachments) . "\n\n\n", 3, "/Users/patrick/my-errors.log");
            try {
                $this->UpdateAttachments($args[$content]->Attachments, $sender, $args);
            } catch (Gdn_UserException $e) {
                $sender->InformMessage('Error Reconnecting to Pega');
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
            [
                'Plugins.Pega.BaseUrl' => [],
                'Plugins.Pega.Username' => [],
                'Plugins.Pega.Password' => []
            ]
        );

        $sender->AddSideMenu();
        $sender->SetData('Title', T('Pega Settings'));
        $cf->RenderAll();
    }

    /**
     * @param array $attachments
     * @param $sender
     * @param array $args
     */
    protected function UpdateAttachments(&$attachments, $sender, $args) {
        return; //temporarily disable this functionality; response time too slow
        $pega = Pega::Instance();
        $attachmentModel = new AttachmentModel();

        foreach ($attachments as &$attachment) {

            if (!$this->IsToBeUpdated($attachment)) {
                continue;
            }

            $caseResponse = $pega->GetCase($attachment['SourceID']);
//            $CaseResponse['HttpCode'] = 200;
//            $CaseResponse['Status'] = 1;
//            $CaseResponse['Priority'] = 1;
//            $CaseResponse['LastModifiedDate'] = date();
//            $CaseResponse['CaseNumber'] = 10;


            $updatedAttachment = (array)$attachmentModel->GetID($attachment['AttachmentID']);

            if ($caseResponse['HttpCode'] == 401) {
                $this->Reconnect($sender, $args);
                continue;
            } elseif ($caseResponse['HttpCode'] == 404) {
                $updatedAttachment['DateUpdated'] = Gdn_Format::ToDateTime();
                $updatedAttachment['Error'] = T('Case has been deleted from Pega');
                $attachmentModel->Save($updatedAttachment);
                $attachment = $updatedAttachment;
                continue;
            } elseif ($caseResponse['HttpCode'] == 200) {
//                error_log("Saving attachement" . json_encode($CaseResponse) . "\n\n\n", 3, "/Users/patrick/my-errors.log");
                $case = $caseResponse['Response'];
                $updatedAttachment['Status'] = $case['pyWorkObjectStatus'];
                $updatedAttachment['Priority'] = 1;
                $updatedAttachment['LastModifiedDate'] = Gdn_Format::ToDateTime();
                $updatedAttachment['CaseNumber'] = $case['pyID'];
                $updatedAttachment['DateUpdated'] = Gdn_Format::ToDateTime();
                $attachmentModel->Save($updatedAttachment);
                $attachment = $updatedAttachment;
            }
        }
    }

    /**
     * @param ProfileController $sender
     * @param array $args
     */
    public function ProfileController_Render_Before($sender, $args) {
        $attachmentModel = AttachmentModel::Instance();
        $attachmentModel->JoinAttachmentsToUser($sender, $args, ['Type' => 'pega-lead'], 1);
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
     * @param string $fullName
     * @return array $Name
     *    [FirstName]
     *    [LastName]
     */
    public function GetFirstNameLastName($fullName) {
        $nameParts = explode(' ', $fullName);
        switch (count($nameParts)) {
            case 3:
                $firstName = $nameParts[0].' '.$nameParts[1];
                $lastName = $nameParts[2];
                break;
            case 2:
                $firstName = $nameParts[0];
                $lastName = $nameParts[1];
                break;
            default:
                $firstName = $fullName;
                $lastName = '';
                break;
        }
        return [$firstName, $lastName];
    }

    /**
     * @param AssetModel $sender
     */
    public function AssetModel_StyleCss_Handler($sender) {
        $sender->AddCssFile('pega.css', 'plugins/Pega');
    }

    /**
     * @param array $attachment Attachment Data - see AttachmentModel
     * @param string $type case or lead
     * @return bool
     */
    protected function IsToBeUpdated($attachment, $type = 'pega-case') {
        if (GetValue('Status', $attachment) == $this->ClosedCaseStatusString) {
            return FALSE;
        }
        $timeDiff = time() - strtotime($attachment['DateUpdated']);
        if ($timeDiff < $this->MinimumTimeForUpdate) {
            Trace("Not Checking For Update: $timeDiff seconds since last update");
            return FALSE;
        }
        if (isset($attachment['LastModifiedDate'])) {
            if ($type == 'pega-case') {
                $timeDiff = time() - strtotime($attachment['LastModifiedDate']);
                if ($timeDiff < $this->MinimumTimeForUpdate && $attachment['Status'] != $this->ClosedCaseStatusString) {
                    Trace("Not Checking For Update: $timeDiff seconds since last update");
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
        $pega = Pega::Instance();
        return $pega->IsConfigured();
    }

}
