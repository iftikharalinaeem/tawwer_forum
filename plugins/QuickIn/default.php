<?php if (!defined('APPLICATION')) exit();

/*
Copyright 2009 Mark O'Sullivan
This file is part of the QuickIn plugin for Vanilla 2.
The QuickIn plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
The QuickIn plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with the Vanilla QuickIn plugin.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['QuickIn'] = array(
   'Name' => 'QuickIn',
   'Description' => 'QuickIn allows users from outside applications to be quickly and automagically registered and signed into your Vanilla forum.',
   'Version' => '1.0',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca'
);

class QuickInPlugin implements Gdn_IPlugin {
   
   // Adds a "Quick-in" menu option to the dashboard
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Site Settings', 'QuickIn', 'garden/settings/quickin', 'Garden.Settings.Manage');
   }

   /**
    * A url through which users can be signed in automatigically.
    */
   public function EntryController_QuickIn_Create($Sender, $EventArguments) {
      // Retrieve information about the user from the url.
      $UserInfo = array(
         'UniqueID' => GetIncomingValue('UniqueID'),
         'Email' => GetIncomingValue('Email'),
         'Name' => GetIncomingValue('Name'),
         'Attributes' => GetIncomingValue('Attributes')
      );
      
      // TODO: Check these are valid before setting the cookie.
      
      // Store this information in a cookie
      setcookie('QuickIn', Format::Serialize($UserInfo));
   }
   
   /**
    * If the user does not have an active session, but they do have a quickin cookie, send them to handshake.
    */
   public function Base_Render_Before(&$Sender) {
      $Session = Gdn::Session();
      $QuickIn = ArrayValue('QuickIn', $_COOKIE);
      if (!$Session->IsValid() && $QuickIn) {
         
      }
   }

   public function SettingsController_QuickIn_Create($Sender, $EventArguments) {
      $Sender->Permission('Garden.Admin.Only');
      $Sender->Title('QuickIn');
      $Sender->AddSideMenu('garden/settings/quickin');
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array(
         'Garden.Authenticator.Type',
         'Garden.Authenticator.Encoding',
         'Garden.Authenticator.SignInUrl',
         'Garden.Authenticator.SignOutUrl',
         'Garden.Authenticator.RegisterUrl',
         'Garden.SignIn.Popup',
         'Garden.UserAccount.AllowEdit'
      ));

      // Set the model on the form.
      $Sender->Form = new Gdn_Form();
      $Sender->Form->SetModel($ConfigurationModel);
      
      // If seeing the form for the first time...
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $Sender->Form->SetData($ConfigurationModel->Data);
         $Sender->Form->SetValue('EnableQuickIn', Gdn::Config('Garden.Authenticator.Type') == 'Handshake' ? 'TRUE' : '');
      } else {
         $Enabled = $Sender->Form->GetFormValue('EnableQuickIn', '') == 'TRUE';
         // Make sure to force some values
         $Sender->Form->SetFormValue('Garden.Authenticator.Type', $Enabled ? 'Handshake' : 'Password');
         $Sender->Form->SetFormValue('Garden.Authenticator.Encoding', 'ini');
         $Sender->Form->SetFormValue('Garden.SignIn.Popup', $Enabled ? FALSE : TRUE); // <-- Make sure that sign in links don't ajaxy popup.
         $Sender->Form->SetFormValue('Garden.UserAccount.AllowEdit', $Enabled ? FALSE : TRUE); // <-- Make sure that users cannot edit their account information through garden.
         if ($Sender->Form->Save() !== FALSE)
            $Sender->StatusMessage = Translate("Your changes have been saved successfully.");
            $Sender->RedirectUrl = Url('/settings/quickin');

         // If QuickIn has been enabled, redirect the user to the external site's
         // login url (this will force the currently authenticated user to link
         // their account with one in the other system).
         if ($Enabled) {
            // De-authenticate the currently signed in user, and redirect to the external system.
            $Password = new Gdn_PasswordAuthenticator();
            $Password->DeAuthenticate();
            $Password->SetIdentity(NULL);
            // Once signed in, we need to come back here to make sure there was no problem with the handshake.
            $Target = Url('/entry/handshake/?Target=/', TRUE);
            // Redirect to the external server to sign in.
            $Handshake = new Gdn_HandshakeAuthenticator(Gdn::Config('Garden.Authenticator'));
            Redirect($Handshake->RemoteSignInUrl($Target));
         }
      }

      $Sender->Render(PATH_PLUGINS . DS . 'QuickIn' . DS . 'views' . DS . 'index.php');
   }
   
   public function Setup() {
      // No setup required.
   }
}