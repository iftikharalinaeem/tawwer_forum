<?php

if (!defined('APPLICATION'))
   exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */
// Define the plugin:
$PluginInfo['SEOLinks'] = array(
    'Name' => 'HTML Links',
    'Description' => "Changes the links to discussions and categories for backwards compatibility purposes.",
    'Version' => '1.1',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'MobileFriendly' => TRUE,
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
    'SettingsUrl' => '/settings/seolinks',
    'SettingsPermission' => 'Garden.Settings.Manage'
);

class SEOLinksPlugin extends Gdn_Plugin {


   public static function Prefix() {
      return C('Plugins.SEOLinks.Prefix', '');
   }

   /// Event Handlers ///

   /**
    *
    * @param Gdn_Router $Sender
    * @param type $Args
    */
   public function Gdn_Dispatcher_BeforeDispatch_Handler($Sender, $Args) {
      $Px = self::Prefix();
      $PxEsc = preg_quote($Px);

      $Route = '/?'.$PxEsc.'[^/]+/(\d+)-(.*?)(?:-(p\d+))?.html';
      Gdn::Router()->Routes[$Route] = array(
          'Route' => $Route,
          'Key' => base64_encode($Route),
          'Destination' => '/discussion/$1/$2/$3',
          'Reserved' => FALSE,
          'Type' => 'Internal'
      );

      $Path = trim(Gdn::Request()->Path(), '/');

      if (preg_match('`^'.$PxEsc.'([^/]+)(?:/(p\d+))?$`', $Path, $Matches)) {
         $UrlCode = $Matches[1];
         $Page = GetValue(2, $Matches);

         $Category = CategoryModel::Categories($UrlCode);
         if ($Category) {
            $Route = '/?'.$PxEsc.'(' . preg_quote($Category['UrlCode']) . ')(?:/(p\d+))?/?(\?.*)?$';
            Gdn::Router()->Routes[$Route] = array(
                'Route' => $Route,
                'Key' => base64_encode($Route),
                'Destination' => '/categories/$1/$2',
                'Reserved' => FALSE,
                'Type' => 'Internal'
            );
         }
      }
   }

   public function SettingsController_SEOLinks_Create($Sender, $Args) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->SetData('Title', sprintf(T('%s Settings'), 'SEO Links'));

      $Cf = new ConfigurationModule($Sender);
      $Cf->Initialize(array(
          'Plugins.SEOLinks.Prefix' => array('Description' => 'A prefix to put before every link (ex. forum/). The prefix should almost always be empty.'),
          ));

      if (Gdn::Request()->IsPostBack()) {
         CategoryModel::ClearCache();
      }

      $Sender->AddSideMenu('dashboard/settings/plugins');
      $Cf->RenderAll();
  }

   public function Setup() {
      if (class_exists('CategoryModel')) {
         CategoryModel::ClearCache();
      }

      // Set /members route once so it is editable
      $Router = Gdn::Router();
      $Router->SetRoute('/?members/([^/]+)', '/profile/$1', 'Internal');
   }
}

if (!function_exists('CategoryUrl')):

   /**
    * Return a url for a category. This function is in here and not functions.general so that plugins can override.
    * @param array $Category
    * @return string
    */
   function CategoryUrl($Category, $Page = '', $WithDomain = TRUE) {
      static $Px;
      if (!isset($Px))
         $Px = SEOLinksPlugin::Prefix();

      if (is_string($Category))
         $Category = CategoryModel::Categories($Category);
      $Category = (array) $Category;

      $Result = '/' . $Px . rawurlencode($Category['UrlCode']) . '/';
      if ($Page && $Page > 1) {
         $Result .= 'p' . $Page . '/';
      }
      return Url($Result, $WithDomain);
   }

endif;

if (!function_exists('DiscussionUrl')):

   function DiscussionUrl($Discussion, $Page = '', $WithDomain = TRUE) {
      static $Px;
      if (!isset($Px))
         $Px = SEOLinksPlugin::Prefix();

      $Discussion = (object) $Discussion;
      $Cat = FALSE;

      // Some places call DiscussionUrl with a custom query that doesn't select CategoryID
      if (GetValue('CategoryID', $Discussion))
         $Cat = CategoryModel::Categories($Discussion->CategoryID);

      if ($Cat)
         $Cat = rawurlencode($Cat['UrlCode']);
      else
         $Cat = 'x';

      $Name = Gdn_Format::Url(html_entity_decode($Discussion->Name, ENT_QUOTES, 'UTF-8'));
      // Make sure the forum doesn't end with the page number notation.
      if (preg_match('`(-p\d+)$`', $Name, $Matches)) {
         $Name = substr($Name, 0, -strlen($Matches[1]));
      }

      if ($Page) {
         if ($Page == 1 && !Gdn::Session()->UserID)
            $Page = '';
         else
            $Page = '-p' . $Page;
      }

      $Path = "/$Px$Cat/{$Discussion->DiscussionID}-$Name{$Page}.html";

      return Url($Path, $WithDomain);
   }

endif;

//if (!function_exists('UserUrl')):
//
//   /**
//    * Return the url for a user.
//    * @param array|object $User The user to get the url for.
//    * @param string $Px The prefix to apply before fieldnames. @since 2.1
//    * @return string The url suitable to be passed into the Url() function.
//    */
//   function UserUrl($User, $Px = '', $Method = '') {
//      static $NameUnique = NULL;
//      if ($NameUnique === NULL)
//         $NameUnique = C('Garden.Registration.NameUnique');
//
//      if ($Method)
//         $Method .= '/';
//
//      return '/members/' .$Method. ($NameUnique ? '' : GetValue($Px . 'UserID', $User, 0) . '/') . rawurlencode(GetValue($Px . 'Name', $User));
//   }
//
//
//
//endif;