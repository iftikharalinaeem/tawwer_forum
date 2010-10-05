<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['Spoof'] = array(
   'Name' => 'Spoof',
   'Description' => 'Allows users with admin role to "spoof" users in their system. Helpful for debugging permission issues.',
   'Version' => '1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class SpoofPlugin implements Gdn_IPlugin {

   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      // Clean out entire menu & re-add everything
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Users', T('Spoof'), 'dashboard/user/spoof', 'Garden.Admin.Only');
	}
   
   public function UserController_Spoof_Create(&$Sender) {
      $Sender->Title('Spoof');
      $Sender->AddSideMenu('dashboard/user/spoof');
      $Sender->Form = new Gdn_Form();
      $UserReference = $Sender->Form->GetValue('UserReference', '');
      $Email = $Sender->Form->GetValue('Email', '');
      $Password = $Sender->Form->GetValue('Password', '');
      if ($UserReference != '' && $Email != '' && $Password != '') {
         $UserModel = Gdn::UserModel();
         $UserData = $UserModel->ValidateCredentials($Email, 0, $Password);
         if (is_object($UserData) && $UserData->Admin == '1') {
				if (is_numeric($Username)) {
					$SpoofUser = $UserModel->Get($UserReference);
				} else {
				   $SpoofUser = $UserModel->GetByUsername($UserReference);
				}
				if ($SpoofUser) {
					$Identity = new Gdn_CookieIdentity();
					$Identity->Init(array(
						'Salt' => Gdn::Config('Garden.Cookie.Salt'),
						'Name' => Gdn::Config('Garden.Cookie.Name'),
						'Domain' => Gdn::Config('Garden.Cookie.Domain')
					));
					$Identity->SetIdentity($SpoofUser->UserID, TRUE);
	            Redirect('profile');
				} else {
					$Sender->Form->AddError('Failed to find requested user.');
				}
         } else {
            $Sender->Form->AddError('Bad Credentials');
         }
      }
      $Sender->Render(PATH_PLUGINS . DS . 'Spoof' . DS . 'views' . DS . 'spoof.php');
   }

   public function Setup() {}
	
}