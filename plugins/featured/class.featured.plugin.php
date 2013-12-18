<?php if(!defined('APPLICATION')) die();

$PluginInfo['featured'] = array(
   'Name' => 'Featured Discussions',
   'Description' => 'Feature discussions.',
   'Version' => '1.0b',
   'Author' => "Dane MacMillan",
   'AuthorEmail' => 'dane@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/dane',
   'RequiredApplications' => array(
       'Vanilla' => '2.2'
       //'Reactions' => '>=1.0'
   ),
   'RequiredTheme' => false,
   'RequiredPlugins' => array(
      'Reactions' => '1.0'
   ),
   'HasLocale' => false,
   'RegisterPermissions' => false,
//   'SettingsUrl' => '/settings/featured',
   'SettingsPermission' => 'Garden.Setttings.Manage'
);

class Featured extends Gdn_Plugin {

   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      if (class_exists('ReactionModel')) {
         $Rm = new ReactionModel();
         $Rm->DefineReactionType(array('UrlCode' => 'Feature', 'Name' => 'Feature', 'Sort' => '0', 'Class' => 'Good', 'IncrementColumn' => 'Score', 'Points' => 1,
         'Description' => 'Feature a discussion.', 'Permission' => 'Garden.Curation.Manage', 'RecordTypes' => array('discussion')), 'Featured');
      }
   }
}
