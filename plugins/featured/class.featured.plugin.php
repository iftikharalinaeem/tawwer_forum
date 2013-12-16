<?php if(!defined('APPLICATION')) die();

$PluginInfo['featured'] = array(
   'Name' => 'Featured',
   'Description' => 'Feature discussions.',
   'Version' => '1.0.0',
   'Author' => "Dane MacMillan",
   'AuthorEmail' => 'dane@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/dane',
   'RequiredApplications' => array(
       'Vanilla' => '>=2.2',
       'Reactions' => '>1.0'
   ),
   'RequiredTheme' => false,
   'RequiredPlugins' => false,
   'HasLocale' => false,
   'RegisterPermissions' => false,
   'SettingsUrl' => '/settings/featured',
   'SettingsPermission' => 'Garden.Setttings.Manage'
);

class Featured extends Gdn_Plugin {

   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      if (class_exists('ReactionModel')) {
         $Rm = new ReactionModel();
         $Rm->DefineReactionType(array('UrlCode' => 'featured', 'Name' => 'Feature Discussion', 'Sort' => pol0, 'Class' => 'Good', 'IncrementColumn' => 'Score', 'Points' => 1,
         'Description' => 'Feature a discussion.', 'Permission' => 'Garden.Curation.Manage', 'RecordTypes' => array('discussion')));
      }
   }

   public function Base_Render_Before($Sender) {
      // Only display this info to signed in members.
		if ($Session->IsValid())
		{
			include_once(PATH_PLUGINS . DS .'featured'. DS .'class.featuredmodule.php');

			$FeaturedModule = new FeaturedModule($Sender);
			$Sender->AddModule($FeaturedModule);
		}
   }

   public function OnDisable() {
	}

   public function CleanUp() {
	}
}
