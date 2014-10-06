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
   'Version' => '2.0.0-beta',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'MobileFriendly' => TRUE,
   'SettingsUrl' => '/settings/vanillicon',
   'SettingsPermission' => 'Garden.Settings.Manage'
);

class VanilliconPlugin extends Gdn_Plugin {
   /// Methods ///

   public function setup() {
      $this->structure();
   }

   public function structure() {
      TouchConfig('Plugins.Vanillicon.Type', 'v1');
   }

   /// Properties ///

   public function ProfileController_AfterAddSideMenu_Handler($Sender, $Args) {
      if (!$Sender->User->Photo) {
         $Sender->User->Photo = UserPhotoDefaultUrl($Sender->User, array('Size' => 200));
      }
   }

   /**
    * @param Gdn_Controller $sender
    */
   public function SettingsController_Vanillicon_Create($sender) {
      $sender->Permission('Garden.Settings.Manage');
      $cf = new ConfigurationModule($sender);

      $items = array(
         'v1' => 'Vanillicon 1',
         'v2' => 'Vanillicon 2 (beta)'
      );

      $cf->Initialize(array(
         'Plugins.Vanillicon.Type' => array(
            'LabelCode' => 'Vanillicon Set',
            'Control' => 'radiolist',
            'Description' => 'Which vanillicon set do you want to use?',
            'Items' => $items,
            'Options' => array('list' => true, 'listclass' => 'icon-list', 'display' => 'after'),
            'Default' => 'v1'
         )
      ));

      $sender->AddSideMenu();
      $sender->SetData('Title', sprintf(T('%s Settings'), 'Vanillicon'));
      $cf->RenderAll();
   }
}

if (!function_exists('UserPhotoDefaultUrl')) {
   function UserPhotoDefaultUrl($User, $Options = array()) {
      static $iconSize = NULL, $type = null;
      if ($iconSize === NULL) {
         $thumbSize = C('Garden.Thumbnail.Size');
         $iconSize = $thumbSize <= 50 ? 50 : 100;
      }
      if ($type === null) {
         $type = C('Plugins.Vanillicon.Type');
      }
      $size = val('Size', $Options, $iconSize);

      $email = GetValue('Email', $User);
      if (!$email) {
         $email = GetValue('UserID', $User, 100);
      }
      $hash = md5($email);
      $px = substr($hash, 0, 1);

      switch ($type) {
         case 'v2':
            $photourl = "//w$px.vanillicon.com/v2/{$hash}.svg";
            break;
         default:
            $photourl = "//w$px.vanillicon.com/{$hash}_{$size}.png";
            break;
      }

      return $photourl;
   }
}