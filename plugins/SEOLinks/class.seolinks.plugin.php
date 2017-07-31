<?php

if (!defined('APPLICATION'))
   exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */
class SEOLinksPlugin extends Gdn_Plugin {


   public static function Prefix() {
      return C('Plugins.SEOLinks.Prefix', '');
   }

   /// Event Handlers ///

   /**
    *
    * @param Gdn_Router $sender
    * @param type $args
    */
   public function Gdn_Dispatcher_BeforeDispatch_Handler($sender, $args) {
      $px = self::Prefix();
      $pxEsc = preg_quote($px);

      $route = '/?'.$pxEsc.'[^/]+/(\d+)-(.*?)(?:-(p\d+))?.html';
      Gdn::Router()->Routes[$route] = [
          'Route' => $route,
          'Key' => base64_encode($route),
          'Destination' => '/discussion/$1/$2/$3',
          'Reserved' => FALSE,
          'Type' => 'Internal'
      ];

      $path = trim(Gdn::Request()->Path(), '/');

      if (preg_match('`^'.$pxEsc.'([^/]+)(?:/(p\d+))?$`', $path, $matches)) {
         $urlCode = $matches[1];
         $page = GetValue(2, $matches);

         $category = CategoryModel::Categories($urlCode);
         if ($category) {
            $route = '/?'.$pxEsc.'(' . preg_quote($category['UrlCode']) . ')(?:/(p\d+))?/?(\?.*)?$';
            Gdn::Router()->Routes[$route] = [
                'Route' => $route,
                'Key' => base64_encode($route),
                'Destination' => '/categories/$1/$2',
                'Reserved' => FALSE,
                'Type' => 'Internal'
            ];
         }
      }
   }

   public function SettingsController_SEOLinks_Create($sender, $args) {
      $sender->Permission('Garden.Settings.Manage');
      $sender->SetData('Title', sprintf(T('%s Settings'), 'SEO Links'));

      $cf = new ConfigurationModule($sender);
      $cf->Initialize([
          'Plugins.SEOLinks.Prefix' => ['Description' => 'A prefix to put before every link (ex. forum/). The prefix should almost always be empty.'],
          ]);

      if (Gdn::Request()->IsPostBack()) {
         CategoryModel::ClearCache();
      }

      $sender->AddSideMenu('settings/plugins');
      $cf->RenderAll();
  }

   public function Setup() {
      if (class_exists('CategoryModel')) {
         CategoryModel::ClearCache();
      }

      // Set /members route once so it is editable
      $router = Gdn::Router();
      $router->SetRoute('/?members/([^/]+)', '/profile/$1', 'Internal');
   }
}

if (!function_exists('CategoryUrl')):

   /**
    * Return a url for a category. This function is in here and not functions.general so that plugins can override.
    * @param array $category
    * @return string
    */
   function CategoryUrl($category, $page = '', $withDomain = TRUE) {
      static $px;
      if (!isset($px))
         $px = SEOLinksPlugin::Prefix();

      if (is_string($category))
         $category = CategoryModel::Categories($category);
      $category = (array) $category;

      $result = '/' . $px . rawurlencode($category['UrlCode']) . '/';
      if ($page && $page > 1) {
         $result .= 'p' . $page . '/';
      }
      return Url($result, $withDomain);
   }

endif;

if (!function_exists('DiscussionUrl')):

   function DiscussionUrl($discussion, $page = '', $withDomain = TRUE) {
      static $px;
      if (!isset($px))
         $px = SEOLinksPlugin::Prefix();

      $discussion = (object) $discussion;
      $cat = FALSE;

      // Some places call DiscussionUrl with a custom query that doesn't select CategoryID
      if (GetValue('CategoryID', $discussion))
         $cat = CategoryModel::Categories($discussion->CategoryID);

      if ($cat)
         $cat = rawurlencode($cat['UrlCode']);
      else
         $cat = 'x';

      $name = Gdn_Format::Url(html_entity_decode($discussion->Name, ENT_QUOTES, 'UTF-8'));
      // Make sure the forum doesn't end with the page number notation.
      if (preg_match('`(-p\d+)$`', $name, $matches)) {
         $name = substr($name, 0, -strlen($matches[1]));
      }

      if ($page) {
         if ($page == 1 && !Gdn::Session()->UserID)
            $page = '';
         else
            $page = '-p' . $page;
      }

      $path = "/$px$cat/{$discussion->DiscussionID}-$name{$page}.html";

      return Url($path, $withDomain);
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
