<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['ModList'] = array(
   'Name' => 'Mod List',
   'Description' => "Add's a list of moderators to categories.",
   'Version' => '1.1.3',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class ModListPlugin extends Gdn_Plugin {
   /// Methods ///
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      Gdn::Structure()
         ->Table('CategoryModerator')
         ->Column('CategoryID', 'int', FALSE, 'primary')
         ->Column('UserID', 'int', FALSE, 'primary')
         ->Set();
   }
   
}