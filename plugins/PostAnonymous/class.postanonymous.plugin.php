<?php if (!defined('APPLICATION')) exit();
/*
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

$PluginInfo['PostAnonymous'] = array(
   'Name' => 'Post Anonymous',
   'Description' => 'Allows users to post anonymously.',
   'Version' => '1.0.2',
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.org/profile/todd',
   'RequiredApplications' => array('Vanilla' => '2.0.18b'),
   'SettingsUrl' => '/dashboard/settings/postanonymous',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'MobileFriendly' => TRUE
);

class PostAnonymousPlugin extends Gdn_Plugin {
   /// Properties ///

   /// Methods ///
   
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
		$Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Forum', 'Post Anonymous', 'settings/postanonymous', 'Garden.Settings.Manage');
	}

   public function AttachForm($Sender, $CategoryID = '') {
		if ($CategoryID != '' && !in_array($CategoryID, self::CategoryIDs()))
			return;
			
      $Sender->AddJsFile('postanonymous.js', 'plugins/PostAnonymous'); //, array('hint' => 'inline'));
      $Sender->AddDefinition('AnonymousCategoryIDs', implode(',', self::CategoryIDs()));
      $this->Form = $Sender->Form;
      include $Sender->FetchViewLocation('AnonymousForm', '', 'plugins/PostAnonymous');
   }

   public static function CategoryIDs() {
      static $CategoryIDs = NULL;
      if ($CategoryIDs === NULL)
         $CategoryIDs = explode(',', C('Plugins.PostAnonymous.CategoryIDs'));

      return $CategoryIDs;
   }

   protected function SetAnonymous(&$Fields) {
      $Anonymous = GetValue('Anonymous', $Fields);

      // Make sure the category supports anonymous posting.
      $CategoryID = GetValue('CategoryID', $Fields);
      if ($CategoryID && !in_array($CategoryID, self::CategoryIDs()))
         $Anonymous = FALSE;

      if ($Anonymous) {
         $AnonUserID = C('Plugins.PostAnonymous.UserID');

         $Fields['InsertUserID'] = $AnonUserID;
         $Fields['InsertIPAddress'] = '0.0.0.0';

         if (isset($Fields['UpdateUserID'])) {
            $Fields['UpdateUserID'] = $AnonUserID;
            $Fields['UpdateIPAddress'] = '0.0.0.0';
         }
      }
   }


   /// Event Handlers ///

   public function CommentModel_BeforeSaveComment_Handler($Sender, $Args) {
      $this->SetAnonymous($Args['FormPostValues']);
   }
   
   /**
    * @param Gdn_Controller $Sender
    * @param args $Args
    */
   public function DiscussionController_AfterBodyField_Handler($Sender, $Args) {
		$CategoryID = GetValueR('Object.CategoryID', $Args);
		if (!$CategoryID)
			$CategoryID = GetValueR('Discussion.CategoryID', $Args);
		
		if ($CategoryID > 0)
			echo $Sender->Form->Hidden('CategoryID', array('value' => $CategoryID));
			
      $this->AttachForm($Sender);
   }

   public function DiscussionModel_BeforeSaveDiscussion_Handler($Sender, $Args) {
      $this->SetAnonymous($Args['FormPostValues']);
   }

   public function PostController_AfterBodyField_Handler($Sender, $Args) {
		// Don't show form if editing a comment
		if (property_exists($Sender, 'Comment'))
			return;

      $this->AttachForm($Sender, $CategoryID);
   }

   /**
    *
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function SettingsController_PostAnonymous_Create($Sender, $Args = array()) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->AddSideMenu('');

      $Sender->Title(T('Post Anonymous Settings'));

      if ($Sender->Form->AuthenticatedPostBack()) {
         $User = Gdn::UserModel()->GetByUsername($Sender->Form->GetFormValue('Username'));
         if (!$User)
            $Sender->Form->AddError('@'.sprintf(T('User "%s" does not exist.'), $Sender->Form->GetFormValue('Username')));
         else
            $UserID = GetValue('UserID', $User);

         if ($Sender->Form->ErrorCount() == 0) {
            $CategoryIDs = $Sender->Form->GetFormValue('CategoryIDs', array());
            if (is_array($CategoryIDs))
               $CategoryIDs = implode(',', $CategoryIDs);
            else
               $CategoryIDs = '';

            SaveToConfig(array(
                'Plugins.PostAnonymous.UserID' => $UserID,
                'Plugins.PostAnonymous.CategoryIDs' => $CategoryIDs
            ));

            $Sender->InformMessage(T('Saved'));
         }
      } else {
         // Grab the settings from the config.
         $Username = Gdn::UserModel()->Get(C('Plugins.PostAnonymous.UserID', 0));
         $Username = GetValue('Name', $Username);
         $Sender->Form->SetValue('Username', $Username);

         $CategoryIDs = explode(',', C('Plugins.PostAnonymous.CategoryIDs'));
         $Sender->Form->SetValue('CategoryIDs', $CategoryIDs);
         
      }

      $Categories = CategoryModel::Categories();
      $Categories = ConsolidateArrayValuesByKey($Categories, 'CategoryID', 'Name');
      unset($Categories[-1]);
      $Categories = array_flip($Categories);
      $Sender->SetData('_Categories', $Categories);

      $Sender->Render('Settings', '', 'plugins/PostAnonymous');
   }

   public function PostController_DiscussionFormOptions_Handler($Sender, $Args) {
      $this->AttachForm($Sender);
   }
}