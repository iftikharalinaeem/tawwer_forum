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
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'MobileFriendly' => TRUE,
);

class VanilliconPlugin extends Gdn_Plugin {
   public function ProfileController_AfterAddSideMenu_Handler($Sender, $Args) {
      if (!$Sender->User->Photo) {
         $Email = GetValue('Email', $Sender->User);
         $Sender->User->Photo = 'http://vanillicon.com/'.md5($Email).'_200.png';
      }
   }
}

if (!function_exists('UserPhotoDefaultUrl')) {
   function UserPhotoDefaultUrl($User, $Options = array()) {

      $Email = GetValue('Email', $User);
      if (!$Email) {
         $Email = GetValue('UserID', $User, 100);
      }

      $PhotoUrl = 'http://vanillicon.com/'.md5($Email).'_50.png';
      return $PhotoUrl;
   }
}