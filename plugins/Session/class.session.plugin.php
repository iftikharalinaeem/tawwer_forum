<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

// Define the plugin:
$PluginInfo['Session'] = array(
   'Name' => 'New Session Management',
   'Description' => "A new version of session management for Vanilla.",
   'Version' => '1.0a',
   'RequiredApplications' => array('Vanilla' => '2.0.18a1'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class SessionPlugin extends Gdn_Plugin {
   /// PROPERTIES ///

   /// METHODS ///
   
   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      Gdn::Structure()
         ->Table('Session')
         ->Column('SessionID', 'char(32)', FALSE, 'primary')
         ->Column('UserID', 'int', 0)
         ->Column('DateInserted', 'datetime', FALSE)
         ->Column('DateUpdated', 'datetime', FALSE)
         ->Column('TransientKey', 'varchar(12)', FALSE)
         ->Column('Attributes', 'text', NULL)
         ->Set();
   }
}