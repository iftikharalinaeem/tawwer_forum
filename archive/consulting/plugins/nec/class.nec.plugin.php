<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

// Define the plugin:
$PluginInfo['nec'] = array(
   'Name' => 'Norton Energy Community Compatibility',
   'Description' => "Provides some compatibility functionality for the Norton Energy Community.",
   'Version' => '1.0b',
   'RequiredApplications' => array('Vanilla' => '2.0.16'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class NecPlugin extends Gdn_Plugin {
}