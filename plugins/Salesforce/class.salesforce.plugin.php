<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
 // Define the plugin:
$PluginInfo['Salesforce'] = array(
   'Name' => 'Salesforce',
   'Description' => "Allow staff users to create leads and cases from discussions and comments.",
   'Version' => '0.1',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'SettingsUrl' => '/dashboard/plugin/Salesforce',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'RegisterPermissions' => array('Garden.Staff.Allow' => 'Garden.Moderation.Manage'),
   'MobileFriendly' => TRUE,
   'Author' => 'John Ashton',
   'AuthorEmail' => 'john@vanillaforums.com',
   'AuthorUrl' => 'http://www.github.com/John0x00',
   'SocialConnect' => false
);

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
            'AuthenticationSchemeAlias' => 'salesforce',
            'URL' => '...',
            'AssociationSecret' => '...',
            'AssociationHashMethod' => '...'
         ),
         array('AuthenticationKey' => self::ProviderKey), TRUE
      );
      Gdn::PermissionModel()->Define(array('Garden.Staff.Allow' => 'Garden.Moderation.Manage'));
      $this->Structure();
   }

   public function Structure() {
      require('structure.php');
   }

   /**
    * @param Controller $Sender
    * @param array $Args
    */
   public function Base_GetConnections_Handler($Sender, $Args) {
      if (!Salesforce::IsConfigured()) {
         return;
      }
      //Staff Only
      if (!Gdn::Session()->CheckPermission('Garden.Staff.Allow')) {
          return;
      }
      $Sf = GetValueR('User.Attributes.' . Salesforce::ProviderKey, $Args);
      Trace($Sf);
      $Profile = GetValueR('User.Attributes.' . Salesforce::ProviderKey . '.Profile', $Args);
      $Sender->Data["Connections"][Salesforce::ProviderKey] = array(
         'Icon' => $this->GetWebResource('icon.svg', '/'),
         'Name' => Salesforce::ProviderKey,
         'ProviderKey' => Salesforce::ProviderKey,
         'ConnectUrl' => Salesforce::AuthorizeUri(Salesforce::ProfileConnecUrl()),
         'Profile' => array(
            'Name' => GetValue('fullname', $Profile),
            'Photo' => null
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
   public function ProfileController_SalesforceConnect_Create($Sender, $UserReference = '', $Username = '', $Code = FALSE) {
      $Sender->Permission('Garden.SignIn.Allow');
      $Sender->GetUserInfo($UserReference, $Username, '', TRUE);
      $Sender->_SetBreadcrumbs(T('Connections'), UserUrl($Sender->User, '', 'connections'));
      //check $GET state // if DashboardConnection // then do global connection.
      $State = GetValue('state', $_GET, FALSE);
      if ($State == 'DashboardConnection') {
         try {
            $Tokens = Salesforce::GetTokens($Code, Salesforce::ProfileConnecUrl());
         } catch (Gdn_UserException $e) {
            $Message = $e->getMessage();
            Gdn::Dispatcher()->PassData('Exception', htmlspecialchars($Message))
               ->Dispatch('home/error');
            return;
         }
         Redirect(Url('/plugin/Salesforce/?DashboardConnection=1&' . http_build_query($Tokens)));
      }
      try {
         $Tokens = Salesforce::GetTokens($Code, Salesforce::ProfileConnecUrl());
      } catch (Gdn_UserException $e) {
         $Attributes = array(
            'RefreshToken' => NULL,
            'AccessToken' => NULL,
            'InstanceUrl' => NULL,
            'Profile' => NULL,
         );
         Gdn::UserModel()->SaveAttribute($Sender->User->UserID, Salesforce::ProviderKey, $Attributes);
         $Message = $e->getMessage();
         Gdn::Dispatcher()->PassData('Exception', htmlspecialchars($Message))
            ->Dispatch('home/error');
         return;
      }
      $AccessToken = GetValue('access_token', $Tokens);
      $InstanceUrl = GetValue('instance_url', $Tokens);
      $LoginID = GetValue('id', $Tokens);
      $RefreshToken = GetValue('refresh_token', $Tokens);
      $Salesforce = new Salesforce($AccessToken, $InstanceUrl);
      $Profile = $Salesforce->GetLoginProfile($LoginID);
      Gdn::UserModel()->SaveAuthentication(array(
         'UserID' => $Sender->User->UserID,
         'Provider' => Salesforce::ProviderKey,
         'UniqueID' => $Profile['id']
      ));
      $Attributes = array(
         'RefreshToken' => $RefreshToken,
         'AccessToken' => $AccessToken,
         'InstanceUrl' => $InstanceUrl,
         'Profile' => $Profile,
      );
      Gdn::UserModel()->SaveAttribute($Sender->User->UserID, Salesforce::ProviderKey, $Attributes);
      $this->EventArguments['Provider'] = Salesforce::ProviderKey;
      $this->EventArguments['User'] = $Sender->User;
      $this->FireEvent('AfterConnection');

      $RedirectUrl = UserUrl($Sender->User, '', 'connections');

      Redirect($RedirectUrl);
   }

   /**
    * Add Salesforce to Dashboard menu.
    *
    * @param Controller $Sender
    * @param array $Arguments
    */
   public function Base_GetAppSettingsMenuItems_Handler($Sender, $Arguments) {
      $LinkText = T('Salesforce');
      $Menu = $Arguments['SideMenu'];
      $Menu->AddItem('Forum', T('Forum'));
      $Menu->AddLink('Forum', $LinkText, 'plugin/Salesforce', 'Garden.Settings.Manage');
   }

   /**
    * Creates the Virtual Controller
    *
    * @param DashboardController $Sender
    */
   public function PluginController_Salesforce_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('Salesforce');
      $Sender->AddSideMenu('plugin/Salesforce');
      $Sender->Form = new Gdn_Form();
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }

   /**
    * Redirect to allow for DashboardConnection
    */
   public function Controller_Connect() {
      $AuthorizeUrl = Salesforce::AuthorizeUri(FALSE, 'DashboardConnection');
      Redirect($AuthorizeUrl);
   }

   /**
    * Redirect to allow for DashboardConnection
    */
   public function Controller_Disconnect() {
      $Salesforce = Salesforce::Instance();
      $Salesforce->UseDashboardConnection();
      $Token = GetValue('token', $_GET, FALSE);
      if ($Token) {
         $Salesforce->Revoke($Token);
         RemoveFromConfig(array(
            'Plugins.Salesforce.DashboardConnection.Token' => FALSE,
            'Plugins.Salesforce.DashboardConnection.RefreshToken' => FALSE,
            'Plugins.Salesforce.DashboardConnection.Token' => FALSE,
            'Plugins.Salesforce.DashboardConnection.InstanceUrl' => FALSE
         ));
      }
      Redirect(Url('/plugin/Salesforce'));
   }

   /**
    * Redirect to allow for DashboardConnection
    * @param Controller $Sender
    */
   public function Controller_Reconnect($Sender) {
      $Salesforce = Salesforce::Instance();
      $Salesforce->UseDashboardConnection();
      $Token = GetValue('token', $_GET, FALSE);
      if ($Token) {
         $RefreshResponse = $Salesforce->Refresh($Token);
         $AccessToken = GetValue('access_token', $RefreshResponse);
         $InstanceUrl = GetValue('instance_url', $RefreshResponse);
         SaveToConfig(array(
            'Plugins.Salesforce.DashboardConnection.InstanceUrl' => $InstanceUrl,
            'Plugins.Salesforce.DashboardConnection.Token' => $AccessToken
         ));
         $Salesforce->SetAccessToken($AccessToken);
         $Salesforce->SetInstanceUrl($InstanceUrl);
         Redirect(Url('/plugin/Salesforce'));
      }
   }

   public function Controller_Enable() {
      SaveToConfig('Plugins.Salesforce.DashboardConnection.Enabled', TRUE);
      Redirect(Url('/plugin/Salesforce'));
   }

   public function Controller_Disable() {
      RemoveFromConfig('Plugins.Salesforce.DashboardConnection.Enabled');
      Redirect(Url('/plugin/Salesforce'));
   }

   /**
    * Dashboard Settings
    * Default method of virtual Salesforce controller.
    *
    * @param DashboardController $Sender
    */
   public function Controller_Index($Sender) {
      $Salesforce = Salesforce::Instance();
      if (GetValue('DashboardConnection', $_GET, FALSE)) {
         $Sender->SetData('DashboardConnection', TRUE);
         SaveToConfig(array(
            'Plugins.Salesforce.DashboardConnection.Enabled' => TRUE,
            'Plugins.Salesforce.DashboardConnection.LoginId' => GetValue('id', $_GET),
            'Plugins.Salesforce.DashboardConnection.InstanceUrl' => GetValue('instance_url', $_GET),
            'Plugins.Salesforce.DashboardConnection.Token' => GetValue('access_token', $_GET),
            'Plugins.Salesforce.DashboardConnection.RefreshToken' => GetValue('refresh_token', $_GET),
         ));

         $Sender->InformMessage('Changes Saved to Config');
         Redirect(Url('/plugin/Salesforce'));
      }
      $Sender->SetData(array(
         'DashboardConnection' => C('Plugins.Salesforce.DashboardConnection.Enabled'),
         'DashboardConnectionProfile'=> FALSE,
         'DashboardConnectionToken' => C('Plugins.Salesforce.DashboardConnection.Token', FALSE),
         'DashboardConnectionRefreshToken' => C('Plugins.Salesforce.DashboardConnection.RefreshToken', FALSE)
      ));
      if (C('Plugins.Salesforce.DashboardConnection.LoginId') && C('Plugins.Salesforce.DashboardConnection.Enabled')) {
//         $Salesforce->UseDashboardConnection();
         $DashboardConnectionProfile = $Salesforce->GetLoginProfile(C('Plugins.Salesforce.DashboardConnection.LoginId'));
         $Sender->SetData('DashboardConnectionProfile', $DashboardConnectionProfile);
         $Sender->AddCssFile('admin.css');
      }
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array(
         'Plugins.Salesforce.ApplicationID',
         'Plugins.Salesforce.Secret',
         'Plugins.Salesforce.AuthenticationUrl',
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
            $Sender->Form->ValidateRule('Plugins.Salesforce.ApplicationID', 'function:ValidateRequired', 'ApplicationID is required');
            $Sender->Form->ValidateRule('Plugins.Salesforce.Secret', 'function:ValidateRequired', 'Secret is required');
            $Sender->Form->ValidateRule('Plugins.Salesforce.AuthenticationUrl', 'function:ValidateRequired', 'Authentication Url is required');
            if ($Sender->Form->ErrorCount() == 0) {
               SaveToConfig('Plugins.Salesforce.ApplicationID', trim($FormValues['Plugins.Salesforce.ApplicationID']));
               SaveToConfig('Plugins.Salesforce.Secret', trim($FormValues['Plugins.Salesforce.Secret']));
               SaveToConfig('Plugins.Salesforce.AuthenticationUrl', rtrim(trim($FormValues['Plugins.Salesforce.AuthenticationUrl'])), '/');

               $Sender->InformMessage(T("Your changes have been saved."));
            } else {
               $Sender->InformMessage(T("Error saving settings to config."));
            }
         }
      }
      $Sender->Form->SetValue('Plugins.Salesforce.ApplicationID', C('Plugins.Salesforce.ApplicationID'));
      $Sender->Form->SetValue('Plugins.Salesforce.Secret', C('Plugins.Salesforce.Secret'));
      $Sender->Form->SetValue('Plugins.Salesforce.AuthenticationUrl', C('Plugins.Salesforce.AuthenticationUrl'));
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
      $UserID = $Args['Discussion']->InsertUserID;
      $DiscussionID = $Args['Discussion']->DiscussionID;
      if (isset($Args['DiscussionOptions'])) {
         $Args['DiscussionOptions']['SalesforceLead'] = array(
            'Label' => T('Salesforce - Add Lead'),
            'Url' => "/discussion/SalesforceLead/Discussion/$DiscussionID/$UserID",
            'Class' => 'Popup'
         );
         $Args['DiscussionOptions']['SalesforceCase'] = array(
            'Label' => T('Salesforce - Create Case'),
            'Url' => "/discussion/SalesforceCase/Discussion/$DiscussionID/$UserID",
            'Class' => 'Popup'
         );
         //remove create Create already created
         $Attachments = GetValue('Attachments', $Args['Discussion'], array());
         foreach ($Attachments as $Attachment) {
            if ($Attachment['Type'] == 'salesforce-case') {
               unset($Args['DiscussionOptions']['SalesforceCase']);
            }
            if ($Attachment['Type'] == 'salesforce-lead') {
               unset($Args['DiscussionOptions']['SalesforceLead']);
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
      $UserID = $Args['Comment']->InsertUserID;
      $CommentID = $Args['Comment']->CommentID;
      $Args['CommentOptions']['SalesforceLead'] = array(
         'Label' => T('Salesforce - Add Lead'),
         'Url' => "/discussion/SalesforceLead/Comment/$CommentID/$UserID",
         'Class' => 'Popup'
      );
      $Args['CommentOptions']['SalesforceCase'] = array(
         'Label' => T('Salesforce - Create Case'),
         'Url' => "/discussion/SalesforceCase/Comment/$CommentID/$UserID",
         'Class' => 'Popup'
      );
      //remove create Create already created
      $Attachments = GetValue('Attachments', $Args['Comment'], array());
      foreach ($Attachments as $Attachment) {
         if ($Attachment['Type'] == 'salesforce-case') {
            unset($Args['CommentOptions']['SalesforceCase']);
         }
         if ($Attachment['Type'] == 'salesforce-lead') {
            unset($Args['CommentOptions']['SalesforceLead']);
         }
      }
   }

   /**
    *
    * Creates the Add Salesforce Lead Panel
    *
    * @param DiscussionController $Sender
    * @param array $Args
    * @throws Exception
    * @throws Gdn_UserException
    */

   public function DiscussionController_SalesforceLead_Create($Sender, $Args) {
      // Signed in users only.
      if (!(Gdn::Session()->UserID)) {
         throw PermissionException('Garden.Signin.Allow');
      }
      // Check Permissions
      $Sender->Permission('Garden.Staff.Allow');
      // Check that we are connected to salesforce
      $Salesforce = Salesforce::Instance();
      if (!$Salesforce->IsConnected()) {
         $this->LoginModal($Sender);
         return;
      }
      // Setup Form
      $Sender->Form = new Gdn_Form();
      // Get Request Arguments
      $Arguments = $Sender->RequestArgs;
      if (sizeof($Arguments) != 3) {
         throw new Gdn_UserException('Invalid Request Url');
      }
      $Type = $Arguments[0];
      $ElementID = $Arguments[1];
      $UserID = $Arguments[2];
      $User = Gdn::UserModel()->GetID($UserID);
      // Get Content
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
      $Sender->Form->AddHidden('ForumUrl', $Url);
      $Sender->Form->AddHidden('Description', Gdn_Format::TextEx($Content->Body));

      //See if user is already registered in Sales Force
      if (!C('Plugins.Salesforce.AllowDuplicateLeads', FALSE)) {
         $ExistingLeadResponse = $Salesforce->FindLead($User->Email);
         if ($ExistingLeadResponse['HttpCode'] == 401) {
            $Salesforce->Reconnect();
            $ExistingLeadResponse = $Salesforce->FindLead($User->Email);
         }
         $ExistingLead = $ExistingLeadResponse['Response'];

         if ($ExistingLead) {
            $Sender->SetData('LeadID',  $ExistingLead['Id']);
            $Sender->Render('existinglead', '', 'plugins/Salesforce');
            return;
         }
      }
      $AttachmentModel = AttachmentModel::Instance();

      // If form is being submitted
      if ($Sender->Form->IsPostBack() && $Sender->Form->AuthenticatedPostBack() === TRUE) {
         // Form Validation
         $Sender->Form->ValidateRule('FirstName', 'function:ValidateRequired', 'First Name is required');
         $Sender->Form->ValidateRule('LastName', 'function:ValidateRequired', 'Last Name is required');
         $Sender->Form->ValidateRule('Email', 'function:ValidateRequired', 'Email is required');
         $Sender->Form->ValidateRule('Company', 'function:ValidateRequired', 'Company is required');
         // If no errors
         if ($Sender->Form->ErrorCount() == 0) {
            $FormValues = $Sender->Form->FormValues();
            // Create Lead in salesforce
            $LeadID = $Salesforce->CreateLead(array(
               'FirstName' => $FormValues['FirstName'],
               'LastName' => $FormValues['LastName'],
               'Email' => $FormValues['Email'],
               'LeadSource' => $FormValues['LeadSource'],
               'Company' => $FormValues['Company'],
               'Title' => $FormValues['Title'],
               'Status' => $FormValues['Status'],
               'Vanilla__ForumUrl__c' => $FormValues['ForumUrl'],
               'Description' => $FormValues['Description']
            ));
            // Save Lead information in our Attachment Table
            $ID = $AttachmentModel->Save(array(
               'Type' => 'salesforce-lead',
               'ForeignID' => $AttachmentModel->RowID($Content),
               'ForeignUserID' => $Content->InsertUserID,
               'Source' => 'salesforce',
               'SourceID' => $LeadID,
               'SourceURL' => C('Plugins.Salesforce.AuthenticationUrl') . '/' . $LeadID,
               'FirstName' => $FormValues['FirstName'],
               'LastName' => $FormValues['LastName'],
               'Company' => $FormValues['Company'],
               'Title' => $FormValues['Title'],
               'Status' => $FormValues['Status'],
               'LastModifiedDate' => Gdn_Format::ToDateTime(),
            ));

            if (!$ID) {
               $Sender->Form->SetValidationResults($AttachmentModel->ValidationResults());
            }

            $Sender->JsonTarget('', $Url, 'Redirect');
            $Sender->InformMessage('Salesforce Lead Created.');
         }

      }
      list($FirstName, $LastName) = $this->GetFirstNameLastName($User->Name);
      try {
         $Data = array(
            'DiscussionID' => $Content->DiscussionID,
            'FirstName' => $FirstName,
            'LastName' => $LastName,
            'Name' => $User->Name,
            'Email' => $User->Email,
            'Title' => $User->Title,
            'LeadSource' => 'Vanilla',
            'Options' => $Salesforce->GetLeadStatusOptions(),
         );
      } catch (Gdn_UserException $e) {
         $Salesforce->Reconnect();
      }
      $Sender->Form->SetData($Data);
      $Sender->SetData('Data', $Data);
      $Sender->Render('addlead', '', 'plugins/Salesforce');
   }

   /**
    * Popup to Add Salesforce Case
    *
    * @param DiscussionController $Sender
    * @param array $Args
    * @throws Gdn_UserException
    * @throws Exception
    *
    */
   public function DiscussionController_SalesforceCase_Create($Sender, $Args) {

      // Signed in users only.
      if (!(Gdn::Session()->IsValid())) {
         throw PermissionException('Garden.Signin.Allow');
      }
      //Permissions
      $Sender->Permission('Garden.Staff.Allow');
      // Check that we are connected to salesforce
      $Salesforce = Salesforce::Instance();
      if (!$Salesforce->IsConnected()) {
         $this->LoginModal($Sender);
         return;
      }
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
      $Sender->Form->AddHidden('Origin', 'Vanilla');
      $Sender->Form->AddHidden('LeadSource', 'Vanilla');
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
            $Contact = $Salesforce->FindContact($FormValues['Email']);
            if (!$Contact['Id']) {
               //If not a contact then add contact
               $Contact['Id'] = $Salesforce->CreateContact(array(
                  'FirstName' => $FormValues['FirstName'],
                  'LastName' => $FormValues['LastName'],
                  'Email' => $FormValues['Email'],
                  'LeadSource' => $FormValues['LeadSource'],
               ));
            }
            //Create Case using salesforce API
            $CaseID = $Salesforce->CreateCase(array(
               'ContactId' => $Contact['Id'],
               'Status' => $FormValues['Status'],
               'Origin' => $FormValues['Origin'],
               'Priority' => $FormValues['Priority'],
               'Subject' => $Sender->DiscussionModel->GetID($Content->DiscussionID)->Name,
               'Description' => $FormValues['Body'],
               'Vanilla__ForumUrl__c' => $FormValues['SourceUri']
            ));
            //Save information to our Attachment Table
            $ID = $AttachmentModel->Save(array(
               'Type' => 'salesforce-case',
               'ForeignID' => $AttachmentModel->RowID($Content),
               'ForeignUserID' => $Content->InsertUserID,
               'Source' => 'salesforce',
               'SourceID' => $CaseID,
               'SourceURL' => C('Plugins.Salesforce.AuthenticationUrl') . '/' . $CaseID,
               'Status' => $FormValues['Status'],
               'Priority' => $FormValues['Priority'],

            ));
            if (!$ID) {
               $Sender->Form->SetValidationResults($AttachmentModel->ValidationResults());
            }
            $Sender->JsonTarget('', $Url, 'Redirect');
            $Sender->InformMessage('Case Added to Salesforce');

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
               'LeadSource' => 'Vanilla',
               'Origin' => 'Vanilla',
               'Options' => $Salesforce->GetCaseStatusOptions(),
               'Priorities' => $Salesforce->GetCasePriorityOptions(),
               'Body' => Gdn_Format::TextEx($Content->Body)
            );
      } catch (Gdn_UserException $e) {
         $Salesforce->Reconnect();
      }

      $Sender->Form->SetData($Data);
      $Sender->SetData('Data', $Data);
      $Sender->Render('createcase', '', 'plugins/Salesforce');

   }

   /**
    * @param DiscussionController $Sender
    * @param array $Args
    */
   public function DiscussionController_AfterDiscussionBody_Handler($Sender, $Args) {
      $this->WriteAndUpdateAttachments($Sender, $Args);
   }

   /**
    * @param DiscussionController $Sender
    * @param array $Args
    */
   public function DiscussionController_AfterCommentBody_Handler($Sender, $Args) {
      $this->WriteAndUpdateAttachments($Sender, $Args);
   }


   protected function WriteAndUpdateAttachments($Sender, $Args) {
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
            if ($Attachment['Type'] == 'salesforce-case') {
               if ($Attachment['ForeignUserID'] == $Session->UserID) {
                  WriteGenericAttachment(array(
                     'Icon' => 'ticket',
                     'Body' => Wrap(T('A ticket has been generated from this post.'), 'p'),
                     'Fields' => array(
                        'one' => 'two'
                     )
                  ));

               } else {
                  WriteGenericAttachment(array(
                     'Icon' => 'ticket',
                     'Body' => Wrap(T('A ticket has been generated from this post.'), 'p')
                  ));
               }
            }
         }
         return;
      }
      if (!$Session->CheckPermission('Garden.Staff.Allow')) {
         return;
      }
      $Salesforce = Salesforce::Instance();
      if (isset($Args[$Content]->Attachments)) {
         if ($Salesforce->IsConnected()) {
            try {
               $this->UpdateAttachments($Args[$Content]->Attachments, $Sender, $Args);
            } catch (Gdn_UserException $e) {
               $Sender->InformMessage('Error Reconnecting to Salesforce');
            }
         }

        // WriteAttachments($Args[$Content]->Attachments);

      }
   }

   /**
    * @param array $Attachments
    * @param $Sender
    * @param array $Args
    */
   protected function UpdateAttachments(&$Attachments, $Sender, $Args) {
      $Salesforce = Salesforce::Instance();
      $AttachmentModel = new AttachmentModel();

      foreach ($Attachments as &$Attachment) {
         if ($Attachment['Type'] == 'salesforce-case') {
            if (!$this->IsToBeUpdated($Attachment)) {
               continue;
            }
            $CaseResponse = $Salesforce->GetCase($Attachment['SourceID']);
            $UpdatedAttachment = (array) $AttachmentModel->GetID($Attachment['AttachmentID']);
            if ($CaseResponse['HttpCode'] == 401) {
               $Salesforce->Reconnect();
               continue;
            } elseif ($CaseResponse['HttpCode'] == 404) {
               $UpdatedAttachment['DateUpdated'] = Gdn_Format::ToDateTime();
               $UpdatedAttachment['Error'] = T('Case has been deleted from Salesforce');
               $AttachmentModel->Save($UpdatedAttachment);
               $Attachment = $UpdatedAttachment;
               continue;
            } elseif ($CaseResponse['HttpCode'] == 200) {
               $Case = $CaseResponse['Response'];
               $UpdatedAttachment['Status'] = $Case['Status'];
               $UpdatedAttachment['Priority'] = $Case['Priority'];
               $UpdatedAttachment['LastModifiedDate'] = $Case['LastModifiedDate'];
               $UpdatedAttachment['CaseNumber'] = $Case['CaseNumber'];
               $UpdatedAttachment['DateUpdated'] = Gdn_Format::ToDateTime();
               $AttachmentModel->Save($UpdatedAttachment);
               $Attachment = $UpdatedAttachment;
            }

         } elseif ($Attachment['Type'] == 'salesforce-lead') {
            if (!$this->IsToBeUpdated($Attachment, $Attachment['Type'])) {
               continue;
            }
            $LeadResponse = $Salesforce->GetLead($Attachment['SourceID']);
            $UpdatedAttachment = (array) $AttachmentModel->GetID($Attachment['AttachmentID']);

            if ($LeadResponse['HttpCode'] == 401) {
               $Salesforce->Reconnect();
               continue;
            } elseif($LeadResponse['HttpCode'] == 404) {
               $UpdatedAttachment['Error'] = T('Lead has been deleted from Salesforce');
               $UpdatedAttachment['DateUpdated'] = Gdn_Format::ToDateTime();
               $AttachmentModel->Save($UpdatedAttachment);
               $Attachment = $UpdatedAttachment;
               continue;
            } elseif ($LeadResponse['HttpCode'] == 200) {
               $Lead = $LeadResponse['Response'];
               $UpdatedAttachment['Status'] = $Lead['Status'];
               $UpdatedAttachment['FirstName'] = $Lead['FirstName'];
               $UpdatedAttachment['LastName'] = $Lead['LastName'];
               $UpdatedAttachment['LastModifiedDate'] = $Lead['LastModifiedDate'];
               $UpdatedAttachment['Company'] = $Lead['Company'];
               $UpdatedAttachment['Title'] = $Lead['Title'];
               $UpdatedAttachment['DateUpdated'] = Gdn_Format::ToDateTime();
               $AttachmentModel->Save($UpdatedAttachment);
               $Attachment = $UpdatedAttachment;

            }

         }
      }
   }

   /**
    * @param ProfileController $Sender
    * @param array $Args
    */
   public function ProfileController_Render_Before($Sender, $Args) {
      $AttachmentModel = AttachmentModel::Instance();
      $AttachmentModel->JoinAttachmentsToUser($Sender, $Args, array('Type' => 'salesforce-lead'), 1);
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
    * @param string $FullName
    * @return array $Name
    *    [FirstName]
    *    [LastName]
    */
   public function GetFirstNameLastName($FullName) {
      $NameParts = explode(' ', $FullName);
      switch (count($NameParts)) {
         case 3:
            $FirstName = $NameParts[0] . ' ' . $NameParts[1];
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
      $Sender->AddCssFile('salesforce.css', 'plugins/Salesforce');
   }

   /**
    * Render Login Modal If staff triggers and action from popup and not connected to salesforce
    *
    * @param DiscussionController|CommentController $Sender
    * @return bool
    */
   public function LoginModal($Sender) {
      $LoginUrl = Url('/profile/connections');
      if (C('Plugins.Salesforce.DashboardConnection.Enabled', FALSE)) {
         $LoginUrl = Url('/plugin/Salesforce');
      }
      $Sender->SetData('LoginURL', $LoginUrl);
      $Sender->Render('reconnect', '', 'plugins/Salesforce');
   }

   /**
    * @param array $Attachment Attachment Data - see AttachmentModel
    * @param string $Type case or lead
    * @return bool
    */
   protected function IsToBeUpdated($Attachment, $Type = 'salesforce-case') {
      if (GetValue('Status', $Attachment) == $this->ClosedCaseStatusString) {
         return FALSE;
      }
      $TimeDiff = time() - strtotime($Attachment['DateUpdated']);
      if ($TimeDiff < $this->MinimumTimeForUpdate ) {
         Trace("Not Checking For Update: $TimeDiff seconds since last update");
         return FALSE;
      }
      if (isset($Attachment['LastModifiedDate'])) {
         if ($Type == 'salesforce-case') {
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
      require_once $Sender->FetchViewLocation('attachment', '', 'plugins/Salesforce');
   }

   public function isConfigured() {
      $salesforce = Salesforce::Instance();
      return $salesforce->IsConfigured();
   }

}
