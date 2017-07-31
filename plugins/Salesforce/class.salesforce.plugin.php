<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
 /**
 * Salesforce Plugin
 *
 * This plugin connects the forums to a salesforce account; Once connected Staff users will
 * be able to Create Leads and Cases from Discussions and Comments in the forums.
 *
 */
class SalesforcePlugin extends Gdn_Plugin {

   /**
    * If status is set to this we will stop getting updates from Salesforce
    * @var string
    */
   protected $ClosedCaseStatusString = 'Closed';

   /**
    * If time since last update from Salesforce is less then this; we wont check for update - saving api calls.
    * @var int
    */
   protected $MinimumTimeForUpdate = 600;

   /**
    * Used in setup for OAuth.
    */
   const ProviderKey = 'Salesforce';


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
            'AuthenticationSchemeAlias' => 'salesforce',
            'URL' => '...',
            'AssociationSecret' => '...',
            'AssociationHashMethod' => '...'
         ],
         ['AuthenticationKey' => self::ProviderKey], TRUE
      );
      Gdn::PermissionModel()->Define(['Garden.Staff.Allow' => 'Garden.Moderation.Manage']);
   }

   /**
    * @param Controller $sender
    * @param array $args
    */
   public function Base_GetConnections_Handler($sender, $args) {
      if (!Salesforce::IsConfigured()) {
         return;
      }
      //Staff Only
      if (!Gdn::Session()->CheckPermission('Garden.Staff.Allow')) {
          return;
      }
      $sf = GetValueR('User.Attributes.' . Salesforce::ProviderKey, $args);
      Trace($sf);
      $profile = GetValueR('User.Attributes.' . Salesforce::ProviderKey . '.Profile', $args);
      $sender->Data["Connections"][Salesforce::ProviderKey] = [
         'Icon' => $this->GetWebResource('icon.svg', '/'),
         'Name' => Salesforce::ProviderKey,
         'ProviderKey' => Salesforce::ProviderKey,
         'ConnectUrl' => Salesforce::AuthorizeUri(Salesforce::ProfileConnecUrl()),
         'Profile' => [
            'Name' => GetValue('fullname', $profile),
            'Photo' => null
         ]
      ];
   }

   /**
    * @param ProfileController $sender
    * @param string $userReference
    * @param string $username
    * @param bool $code
    *
    */
   public function ProfileController_SalesforceConnect_Create($sender, $userReference = '', $username = '', $code = FALSE) {
      $sender->Permission('Garden.SignIn.Allow');
      $sender->GetUserInfo($userReference, $username, '', TRUE);
      $sender->_SetBreadcrumbs(T('Connections'), UserUrl($sender->User, '', 'connections'));
      //check $GET state // if DashboardConnection // then do global connection.
      $state = GetValue('state', $_GET, FALSE);
      if ($state == 'DashboardConnection') {
         try {
            $tokens = Salesforce::GetTokens($code, Salesforce::ProfileConnecUrl());
         } catch (Gdn_UserException $e) {
            $message = $e->getMessage();
            Gdn::Dispatcher()->PassData('Exception', htmlspecialchars($message))
               ->Dispatch('home/error');
            return;
         }
         redirectTo('/plugin/Salesforce/?DashboardConnection=1&'.http_build_query($tokens));
      }
      try {
         $tokens = Salesforce::GetTokens($code, Salesforce::ProfileConnecUrl());
      } catch (Gdn_UserException $e) {
         $attributes = [
            'RefreshToken' => NULL,
            'AccessToken' => NULL,
            'InstanceUrl' => NULL,
            'Profile' => NULL,
         ];
         Gdn::UserModel()->SaveAttribute($sender->User->UserID, Salesforce::ProviderKey, $attributes);
         $message = $e->getMessage();
         Gdn::Dispatcher()->PassData('Exception', htmlspecialchars($message))
            ->Dispatch('home/error');
         return;
      }
      $accessToken = GetValue('access_token', $tokens);
      $instanceUrl = GetValue('instance_url', $tokens);
      $loginID = GetValue('id', $tokens);
      $refreshToken = GetValue('refresh_token', $tokens);
      $salesforce = new Salesforce($accessToken, $instanceUrl);
      $profile = $salesforce->GetLoginProfile($loginID);
      Gdn::UserModel()->SaveAuthentication([
         'UserID' => $sender->User->UserID,
         'Provider' => Salesforce::ProviderKey,
         'UniqueID' => $profile['id']
      ]);
      $attributes = [
         'RefreshToken' => $refreshToken,
         'AccessToken' => $accessToken,
         'InstanceUrl' => $instanceUrl,
         'Profile' => $profile,
      ];
      Gdn::UserModel()->SaveAttribute($sender->User->UserID, Salesforce::ProviderKey, $attributes);
      $this->EventArguments['Provider'] = Salesforce::ProviderKey;
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
   public function PluginController_Salesforce_Create($sender) {
      $sender->Permission('Garden.Settings.Manage');
      $sender->Title('Salesforce');
      $sender->AddSideMenu('plugin/Salesforce');
      $sender->Form = new Gdn_Form();
      $this->Dispatch($sender, $sender->RequestArgs);
   }

   /**
    * Redirect to allow for DashboardConnection
    */
   public function Controller_Connect() {
      $authorizeUrl = Salesforce::AuthorizeUri(FALSE, 'DashboardConnection');
      redirectTo($authorizeUrl, 302, false);
   }

   /**
    * Redirect to allow for DashboardConnection
    */
   public function Controller_Disconnect() {
      $salesforce = Salesforce::Instance();
      $salesforce->UseDashboardConnection();
      $token = GetValue('token', $_GET, FALSE);
      if ($token) {
         $salesforce->Revoke($token);
         RemoveFromConfig([
            'Plugins.Salesforce.DashboardConnection.Token' => FALSE,
            'Plugins.Salesforce.DashboardConnection.RefreshToken' => FALSE,
            'Plugins.Salesforce.DashboardConnection.Token' => FALSE,
            'Plugins.Salesforce.DashboardConnection.InstanceUrl' => FALSE
         ]);
      }
      redirectTo('/plugin/Salesforce');
   }

   /**
    * Redirect to allow for DashboardConnection
    * @param Controller $sender
    */
   public function Controller_Reconnect($sender) {
      $salesforce = Salesforce::Instance();
      $salesforce->UseDashboardConnection();
      $token = GetValue('token', $_GET, FALSE);
      if ($token) {
         $refreshResponse = $salesforce->Refresh($token);
         $accessToken = GetValue('access_token', $refreshResponse);
         $instanceUrl = GetValue('instance_url', $refreshResponse);
         SaveToConfig([
            'Plugins.Salesforce.DashboardConnection.InstanceUrl' => $instanceUrl,
            'Plugins.Salesforce.DashboardConnection.Token' => $accessToken
         ]);
         $salesforce->SetAccessToken($accessToken);
         $salesforce->SetInstanceUrl($instanceUrl);
         redirectTo('/plugin/Salesforce');
      }
   }

   public function Controller_Enable() {
      SaveToConfig('Plugins.Salesforce.DashboardConnection.Enabled', TRUE);
      redirectTo('/plugin/Salesforce');
   }

   public function Controller_Disable() {
      RemoveFromConfig('Plugins.Salesforce.DashboardConnection.Enabled');
      redirectTo('/plugin/Salesforce');
   }

   /**
    * Dashboard Settings
    * Default method of virtual Salesforce controller.
    *
    * @param DashboardController $sender
    */
   public function Controller_Index($sender) {
      $salesforce = Salesforce::Instance();
      if (GetValue('DashboardConnection', $_GET, FALSE)) {
         $sender->SetData('DashboardConnection', TRUE);
         SaveToConfig([
            'Plugins.Salesforce.DashboardConnection.Enabled' => TRUE,
            'Plugins.Salesforce.DashboardConnection.LoginId' => GetValue('id', $_GET),
            'Plugins.Salesforce.DashboardConnection.InstanceUrl' => GetValue('instance_url', $_GET),
            'Plugins.Salesforce.DashboardConnection.Token' => GetValue('access_token', $_GET),
            'Plugins.Salesforce.DashboardConnection.RefreshToken' => GetValue('refresh_token', $_GET),
         ]);

         $sender->InformMessage('Changes Saved to Config');
         redirectTo('/plugin/Salesforce');
      }
      $sender->SetData([
         'DashboardConnection' => C('Plugins.Salesforce.DashboardConnection.Enabled'),
         'DashboardConnectionProfile'=> FALSE,
         'DashboardConnectionToken' => C('Plugins.Salesforce.DashboardConnection.Token', FALSE),
         'DashboardConnectionRefreshToken' => C('Plugins.Salesforce.DashboardConnection.RefreshToken', FALSE)
      ]);
      if (C('Plugins.Salesforce.DashboardConnection.LoginId') && C('Plugins.Salesforce.DashboardConnection.Enabled')) {
//         $Salesforce->UseDashboardConnection();
         $dashboardConnectionProfile = $salesforce->GetLoginProfile(C('Plugins.Salesforce.DashboardConnection.LoginId'));
         $sender->SetData('DashboardConnectionProfile', $dashboardConnectionProfile);
         $sender->AddCssFile('admin.css');
      }
      $validation = new Gdn_Validation();
      $configurationModel = new Gdn_ConfigurationModel($validation);
      $configurationModel->SetField([
         'Plugins.Salesforce.ApplicationID',
         'Plugins.Salesforce.Secret',
         'Plugins.Salesforce.AuthenticationUrl',
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
            $sender->Form->ValidateRule('Plugins.Salesforce.ApplicationID', 'function:ValidateRequired', 'ApplicationID is required');
            $sender->Form->ValidateRule('Plugins.Salesforce.Secret', 'function:ValidateRequired', 'Secret is required');
            $sender->Form->ValidateRule('Plugins.Salesforce.AuthenticationUrl', 'function:ValidateRequired', 'Authentication Url is required');
            if ($sender->Form->ErrorCount() == 0) {
               SaveToConfig('Plugins.Salesforce.ApplicationID', trim($formValues['Plugins.Salesforce.ApplicationID']));
               SaveToConfig('Plugins.Salesforce.Secret', trim($formValues['Plugins.Salesforce.Secret']));
               SaveToConfig('Plugins.Salesforce.AuthenticationUrl', rtrim(trim($formValues['Plugins.Salesforce.AuthenticationUrl'])), '/');

               $sender->InformMessage(T("Your changes have been saved."));
            } else {
               $sender->InformMessage(T("Error saving settings to config."));
            }
         }
      }
      $sender->Form->SetValue('Plugins.Salesforce.ApplicationID', C('Plugins.Salesforce.ApplicationID'));
      $sender->Form->SetValue('Plugins.Salesforce.Secret', C('Plugins.Salesforce.Secret'));
      $sender->Form->SetValue('Plugins.Salesforce.AuthenticationUrl', C('Plugins.Salesforce.AuthenticationUrl'));
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
      $userID = $args['Discussion']->InsertUserID;
      $discussionID = $args['Discussion']->DiscussionID;
      if (isset($args['DiscussionOptions'])) {
         $args['DiscussionOptions']['SalesforceLead'] = [
            'Label' => T('Salesforce - Add Lead'),
            'Url' => "/discussion/SalesforceLead/Discussion/$discussionID/$userID",
            'Class' => 'Popup'
         ];
         $args['DiscussionOptions']['SalesforceCase'] = [
            'Label' => T('Salesforce - Create Case'),
            'Url' => "/discussion/SalesforceCase/Discussion/$discussionID/$userID",
            'Class' => 'Popup'
         ];
         //remove create Create already created
         $attachments = GetValue('Attachments', $args['Discussion'], []);
         foreach ($attachments as $attachment) {
            if ($attachment['Type'] == 'salesforce-case') {
               unset($args['DiscussionOptions']['SalesforceCase']);
            }
            if ($attachment['Type'] == 'salesforce-lead') {
               unset($args['DiscussionOptions']['SalesforceLead']);
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
      $userID = $args['Comment']->InsertUserID;
      $commentID = $args['Comment']->CommentID;
      $args['CommentOptions']['SalesforceLead'] = [
         'Label' => T('Salesforce - Add Lead'),
         'Url' => "/discussion/SalesforceLead/Comment/$commentID/$userID",
         'Class' => 'Popup'
      ];
      $args['CommentOptions']['SalesforceCase'] = [
         'Label' => T('Salesforce - Create Case'),
         'Url' => "/discussion/SalesforceCase/Comment/$commentID/$userID",
         'Class' => 'Popup'
      ];
      //remove create Create already created
      $attachments = GetValue('Attachments', $args['Comment'], []);
      foreach ($attachments as $attachment) {
         if ($attachment['Type'] == 'salesforce-case') {
            unset($args['CommentOptions']['SalesforceCase']);
         }
         if ($attachment['Type'] == 'salesforce-lead') {
            unset($args['CommentOptions']['SalesforceLead']);
         }
      }
   }

   /**
    *
    * Creates the Add Salesforce Lead Panel
    *
    * @param DiscussionController $sender
    * @param array $args
    * @throws Exception
    * @throws Gdn_UserException
    */

   public function DiscussionController_SalesforceLead_Create($sender, $args) {
      // Signed in users only.
      if (!(Gdn::Session()->UserID)) {
         throw PermissionException('Garden.Signin.Allow');
      }
      // Check Permissions
      $sender->Permission('Garden.Staff.Allow');
      // Check that we are connected to salesforce
      $salesforce = Salesforce::Instance();
      if (!$salesforce->IsConnected()) {
         $this->LoginModal($sender);
         return;
      }
      // Setup Form
      $sender->Form = new Gdn_Form();
      // Get Request Arguments
      $arguments = $sender->RequestArgs;
      if (sizeof($arguments) != 3) {
         throw new Gdn_UserException('Invalid Request Url');
      }
      $type = $arguments[0];
      $elementID = $arguments[1];
      $userID = $arguments[2];
      $user = Gdn::UserModel()->GetID($userID);
      // Get Content
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
      $sender->Form->AddHidden('ForumUrl', $url);
      $sender->Form->AddHidden('Description', Gdn_Format::TextEx($content->Body));

      //See if user is already registered in Sales Force
      if (!C('Plugins.Salesforce.AllowDuplicateLeads', FALSE)) {
         $existingLeadResponse = $salesforce->FindLead($user->Email);
         if ($existingLeadResponse['HttpCode'] == 401) {
            $salesforce->Reconnect();
            $existingLeadResponse = $salesforce->FindLead($user->Email);
         }
         $existingLead = $existingLeadResponse['Response'];

         if ($existingLead) {
            $sender->SetData('LeadID',  $existingLead['Id']);
            $sender->Render('existinglead', '', 'plugins/Salesforce');
            return;
         }
      }
      $attachmentModel = AttachmentModel::Instance();

      // If form is being submitted
      if ($sender->Form->IsPostBack() && $sender->Form->AuthenticatedPostBack() === TRUE) {
         // Form Validation
         $sender->Form->ValidateRule('FirstName', 'function:ValidateRequired', 'First Name is required');
         $sender->Form->ValidateRule('LastName', 'function:ValidateRequired', 'Last Name is required');
         $sender->Form->ValidateRule('Email', 'function:ValidateRequired', 'Email is required');
         $sender->Form->ValidateRule('Company', 'function:ValidateRequired', 'Company is required');
         // If no errors
         if ($sender->Form->ErrorCount() == 0) {
            $formValues = $sender->Form->FormValues();
            // Create Lead in salesforce
            $leadID = $salesforce->CreateLead([
               'FirstName' => $formValues['FirstName'],
               'LastName' => $formValues['LastName'],
               'Email' => $formValues['Email'],
               'LeadSource' => $formValues['LeadSource'],
               'Company' => $formValues['Company'],
               'Title' => $formValues['Title'],
               'Status' => $formValues['Status'],
               'Vanilla__ForumUrl__c' => $formValues['ForumUrl'],
               'Description' => $formValues['Description']
            ]);
            // Save Lead information in our Attachment Table
            $iD = $attachmentModel->Save([
               'Type' => 'salesforce-lead',
               'ForeignID' => $attachmentModel->RowID($content),
               'ForeignUserID' => $content->InsertUserID,
               'Source' => 'salesforce',
               'SourceID' => $leadID,
               'SourceURL' => C('Plugins.Salesforce.AuthenticationUrl') . '/' . $leadID,
               'FirstName' => $formValues['FirstName'],
               'LastName' => $formValues['LastName'],
               'Company' => $formValues['Company'],
               'Title' => $formValues['Title'],
               'Status' => $formValues['Status'],
               'LastModifiedDate' => Gdn_Format::ToDateTime(),
            ]);

            if (!$iD) {
               $sender->Form->SetValidationResults($attachmentModel->ValidationResults());
            }

            $sender->JsonTarget('', $url, 'Redirect');
            $sender->InformMessage('Salesforce Lead Created.');
         }

      }
      list($firstName, $lastName) = $this->GetFirstNameLastName($user->Name);
      try {
         $data = [
            'DiscussionID' => $content->DiscussionID,
            'FirstName' => $firstName,
            'LastName' => $lastName,
            'Name' => $user->Name,
            'Email' => $user->Email,
            'Title' => $user->Title,
            'LeadSource' => 'Vanilla',
            'Options' => $salesforce->GetLeadStatusOptions(),
         ];
      } catch (Gdn_UserException $e) {
         $salesforce->Reconnect();
      }
      $sender->Form->SetData($data);
      $sender->SetData('Data', $data);
      $sender->Render('addlead', '', 'plugins/Salesforce');
   }

   /**
    * Popup to Add Salesforce Case
    *
    * @param DiscussionController $sender
    * @param array $args
    * @throws Gdn_UserException
    * @throws Exception
    *
    */
   public function DiscussionController_SalesforceCase_Create($sender, $args) {

      // Signed in users only.
      if (!(Gdn::Session()->IsValid())) {
         throw PermissionException('Garden.Signin.Allow');
      }
      //Permissions
      $sender->Permission('Garden.Staff.Allow');
      // Check that we are connected to salesforce
      $salesforce = Salesforce::Instance();
      if (!$salesforce->IsConnected()) {
         $this->LoginModal($sender);
         return;
      }
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
      $sender->Form->AddHidden('Origin', 'Vanilla');
      $sender->Form->AddHidden('LeadSource', 'Vanilla');
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
            $contact = $salesforce->FindContact($formValues['Email']);
            if (!$contact['Id']) {
               //If not a contact then add contact
               $contact['Id'] = $salesforce->CreateContact([
                  'FirstName' => $formValues['FirstName'],
                  'LastName' => $formValues['LastName'],
                  'Email' => $formValues['Email'],
                  'LeadSource' => $formValues['LeadSource'],
               ]);
            }
            //Create Case using salesforce API
            $caseID = $salesforce->CreateCase([
               'ContactId' => $contact['Id'],
               'Status' => $formValues['Status'],
               'Origin' => $formValues['Origin'],
               'Priority' => $formValues['Priority'],
               'Subject' => $sender->DiscussionModel->GetID($content->DiscussionID)->Name,
               'Description' => $formValues['Body'],
               'Vanilla__ForumUrl__c' => $formValues['SourceUri']
            ]);
            //Save information to our Attachment Table
            $iD = $attachmentModel->Save([
               'Type' => 'salesforce-case',
               'ForeignID' => $attachmentModel->RowID($content),
               'ForeignUserID' => $content->InsertUserID,
               'Source' => 'salesforce',
               'SourceID' => $caseID,
               'SourceURL' => C('Plugins.Salesforce.AuthenticationUrl') . '/' . $caseID,
               'Status' => $formValues['Status'],
               'Priority' => $formValues['Priority'],

            ]);
            if (!$iD) {
               $sender->Form->SetValidationResults($attachmentModel->ValidationResults());
            }
            $sender->JsonTarget('', $url, 'Redirect');
            $sender->InformMessage('Case Added to Salesforce');

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
               'LeadSource' => 'Vanilla',
               'Origin' => 'Vanilla',
               'Options' => $salesforce->GetCaseStatusOptions(),
               'Priorities' => $salesforce->GetCasePriorityOptions(),
               'Body' => Gdn_Format::TextEx($content->Body)
            ];
      } catch (Gdn_UserException $e) {
         $salesforce->Reconnect();
      }

      $sender->Form->SetData($data);
      $sender->SetData('Data', $data);
      $sender->Render('createcase', '', 'plugins/Salesforce');

   }

   /**
    * @param DiscussionController $sender
    * @param array $args
    */
   public function DiscussionController_AfterDiscussionBody_Handler($sender, $args) {
      $this->WriteAndUpdateAttachments($sender, $args);
   }

   /**
    * @param DiscussionController $sender
    * @param array $args
    */
   public function DiscussionController_AfterCommentBody_Handler($sender, $args) {
      $this->WriteAndUpdateAttachments($sender, $args);
   }


   protected function WriteAndUpdateAttachments($sender, $args) {
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
            if ($attachment['Type'] == 'salesforce-case') {
               if ($attachment['ForeignUserID'] == $session->UserID) {
                  WriteGenericAttachment([
                     'Icon' => 'ticket',
                     'Body' => Wrap(T('A ticket has been generated from this post.'), 'p'),
                     'Fields' => [
                        'one' => 'two'
                     ]
                  ]);

               } else {
                  WriteGenericAttachment([
                     'Icon' => 'ticket',
                     'Body' => Wrap(T('A ticket has been generated from this post.'), 'p')
                  ]);
               }
            }
         }
         return;
      }
      if (!$session->CheckPermission('Garden.Staff.Allow')) {
         return;
      }
      $salesforce = Salesforce::Instance();
      if (isset($args[$content]->Attachments)) {
         if ($salesforce->IsConnected()) {
            try {
               $this->UpdateAttachments($args[$content]->Attachments, $sender, $args);
            } catch (Gdn_UserException $e) {
               $sender->InformMessage('Error Reconnecting to Salesforce');
            }
         }

        // WriteAttachments($Args[$Content]->Attachments);

      }
   }

   /**
    * @param array $attachments
    * @param $sender
    * @param array $args
    */
   protected function UpdateAttachments(&$attachments, $sender, $args) {
      $salesforce = Salesforce::Instance();
      $attachmentModel = new AttachmentModel();

      foreach ($attachments as &$attachment) {
         if ($attachment['Type'] == 'salesforce-case') {
            if (!$this->IsToBeUpdated($attachment)) {
               continue;
            }
            $caseResponse = $salesforce->GetCase($attachment['SourceID']);
            $updatedAttachment = (array) $attachmentModel->GetID($attachment['AttachmentID']);
            if ($caseResponse['HttpCode'] == 401) {
               $salesforce->Reconnect();
               continue;
            } elseif ($caseResponse['HttpCode'] == 404) {
               $updatedAttachment['DateUpdated'] = Gdn_Format::ToDateTime();
               $updatedAttachment['Error'] = T('Case has been deleted from Salesforce');
               $attachmentModel->Save($updatedAttachment);
               $attachment = $updatedAttachment;
               continue;
            } elseif ($caseResponse['HttpCode'] == 200) {
               $case = $caseResponse['Response'];
               $updatedAttachment['Status'] = $case['Status'];
               $updatedAttachment['Priority'] = $case['Priority'];
               $updatedAttachment['LastModifiedDate'] = $case['LastModifiedDate'];
               $updatedAttachment['CaseNumber'] = $case['CaseNumber'];
               $updatedAttachment['DateUpdated'] = Gdn_Format::ToDateTime();
               $attachmentModel->Save($updatedAttachment);
               $attachment = $updatedAttachment;
            }

         } elseif ($attachment['Type'] == 'salesforce-lead') {
            if (!$this->IsToBeUpdated($attachment, $attachment['Type'])) {
               continue;
            }
            $leadResponse = $salesforce->GetLead($attachment['SourceID']);
            $updatedAttachment = (array) $attachmentModel->GetID($attachment['AttachmentID']);

            if ($leadResponse['HttpCode'] == 401) {
               $salesforce->Reconnect();
               continue;
            } elseif($leadResponse['HttpCode'] == 404) {
               $updatedAttachment['Error'] = T('Lead has been deleted from Salesforce');
               $updatedAttachment['DateUpdated'] = Gdn_Format::ToDateTime();
               $attachmentModel->Save($updatedAttachment);
               $attachment = $updatedAttachment;
               continue;
            } elseif ($leadResponse['HttpCode'] == 200) {
               $lead = $leadResponse['Response'];
               $updatedAttachment['Status'] = $lead['Status'];
               $updatedAttachment['FirstName'] = $lead['FirstName'];
               $updatedAttachment['LastName'] = $lead['LastName'];
               $updatedAttachment['LastModifiedDate'] = $lead['LastModifiedDate'];
               $updatedAttachment['Company'] = $lead['Company'];
               $updatedAttachment['Title'] = $lead['Title'];
               $updatedAttachment['DateUpdated'] = Gdn_Format::ToDateTime();
               $attachmentModel->Save($updatedAttachment);
               $attachment = $updatedAttachment;

            }

         }
      }
   }

   /**
    * @param ProfileController $sender
    * @param array $args
    */
   public function ProfileController_Render_Before($sender, $args) {
      $attachmentModel = AttachmentModel::Instance();
      $attachmentModel->JoinAttachmentsToUser($sender, $args, ['Type' => 'salesforce-lead'], 1);
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
         require_once $Sender->FetchViewLocation('attachment', '', 'plugins/Salesforce');
      }
      foreach ($Attachments as $Attachment) {
         if ($Attachment['Type'] == 'salesforce-lead') {
            WriteSalesforceLeadAttachment($Attachment);
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
            $firstName = $nameParts[0] . ' ' . $nameParts[1];
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
      $sender->AddCssFile('salesforce.css', 'plugins/Salesforce');
   }

   /**
    * Render Login Modal If staff triggers and action from popup and not connected to salesforce
    *
    * @param DiscussionController|CommentController $sender
    * @return bool
    */
   public function LoginModal($sender) {
      $loginUrl = Url('/profile/connections');
      if (C('Plugins.Salesforce.DashboardConnection.Enabled', FALSE)) {
         $loginUrl = Url('/plugin/Salesforce');
      }
      $sender->SetData('LoginURL', $loginUrl);
      $sender->Render('reconnect', '', 'plugins/Salesforce');
   }

   /**
    * @param array $attachment Attachment Data - see AttachmentModel
    * @param string $type case or lead
    * @return bool
    */
   protected function IsToBeUpdated($attachment, $type = 'salesforce-case') {
      if (GetValue('Status', $attachment) == $this->ClosedCaseStatusString) {
         return FALSE;
      }
      $timeDiff = time() - strtotime($attachment['DateUpdated']);
      if ($timeDiff < $this->MinimumTimeForUpdate ) {
         Trace("Not Checking For Update: $timeDiff seconds since last update");
         return FALSE;
      }
      if (isset($attachment['LastModifiedDate'])) {
         if ($type == 'salesforce-case') {
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
      require_once $Sender->FetchViewLocation('attachment', '', 'plugins/Salesforce');
   }

   public function isConfigured() {
      $salesforce = Salesforce::Instance();
      return $salesforce->IsConfigured();
   }

}
