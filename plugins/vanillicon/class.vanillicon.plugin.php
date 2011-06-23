<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @author Todd Burry <todd@vanillaforums.com>
 */

// Define the plugin:
$PluginInfo['vanillicon'] = array(
   'Name' => 'Vanillicon',
   'Description' => "Provides fun default user icons from vanillicon.com.",
   'Version' => '1.0b',
   'RequiredApplications' => array('Vanilla' => '2.0.18b2'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class VanilliconPlugin extends Gdn_Plugin {
   
}

if (!function_exists('UserPhotoDefaultUrl')) {
   function UserPhotoDefaultUrl($User, $Options = array()) {

      $Email = GetValue('Email', $User);
      if (!$Email) {
         $Email = GetValue('UserID', $User, 100);
      }

      $PhotoUrl = 'http://vanillicon.com/'.md5($Email).'.png';
      return $PhotoUrl;
   }
}