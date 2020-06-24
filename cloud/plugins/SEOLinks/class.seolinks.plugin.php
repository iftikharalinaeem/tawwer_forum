<?php

if (!defined('APPLICATION'))
   exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */
class SEOLinksPlugin extends Gdn_Plugin {


   public static function prefix() {
      return c('Plugins.SEOLinks.Prefix', '');
   }

   /// Event Handlers ///

   /**
    *
    * @param Gdn_Router $sender
    * @param type $args
    */
   public function gdn_Dispatcher_BeforeDispatch_Handler($sender, $args) {
      $px = self::prefix();
      $pxEsc = preg_quote($px);

      $route = '/?'.$pxEsc.'[^/]+/(\d+)-(.*?)(?:-(p\d+))?.html';
      Gdn::router()->Routes[$route] = [
          'Route' => $route,
          'Key' => base64_encode($route),
          'Destination' => '/discussion/$1/$2/$3',
          'Reserved' => FALSE,
          'Type' => 'Internal'
      ];

      $path = trim(Gdn::request()->path(), '/');

      if (preg_match('`^'.$pxEsc.'([^/]+)(?:/(p\d+))?$`', $path, $matches)) {
         $urlCode = $matches[1];
         $page = getValue(2, $matches);

         $category = CategoryModel::categories($urlCode);
         if ($category) {
            $route = '/?'.$pxEsc.'(' . preg_quote($category['UrlCode']) . ')(?:/(p\d+))?/?(\?.*)?$';
            Gdn::router()->Routes[$route] = [
                'Route' => $route,
                'Key' => base64_encode($route),
                'Destination' => '/categories/$1/$2',
                'Reserved' => FALSE,
                'Type' => 'Internal'
            ];
         }
      }
   }

   public function settingsController_sEOLinks_create($sender, $args) {
      $sender->permission('Garden.Settings.Manage');
      $sender->setData('Title', sprintf(t('%s Settings'), 'SEO Links'));

      $cf = new ConfigurationModule($sender);
      $cf->initialize([
          'Plugins.SEOLinks.Prefix' => ['Description' => 'A prefix to put before every link (ex. forum/). The prefix should almost always be empty.'],
          ]);

      if (Gdn::request()->isPostBack()) {
         CategoryModel::clearCache();
      }

      $sender->addSideMenu('settings/plugins');
      $cf->renderAll();
  }

   public function setup() {
      if (class_exists('CategoryModel')) {
         CategoryModel::clearCache();
      }

      // Set /members route once so it is editable
      $router = Gdn::router();
      $router->setRoute('/?members/([^/]+)', '/profile/$1', 'Internal');
   }
}

if (!function_exists('CategoryUrl')):

   /**
    * Return a url for a category. This function is in here and not functions.general so that plugins can override.
    * @param array $category
    * @return string
    */
   function categoryUrl($category, $page = '', $withDomain = TRUE) {
      static $px;
      if (!isset($px))
         $px = SEOLinksPlugin::prefix();

      if (is_string($category))
         $category = CategoryModel::categories($category);
      $category = (array) $category;

      $result = '/' . $px . rawurlencode($category['UrlCode']) . '/';
      if ($page && $page > 1) {
         $result .= 'p' . $page . '/';
      }
      return url($result, $withDomain);
   }

endif;

if (!function_exists('DiscussionUrl')):

   function discussionUrl($discussion, $page = '', $withDomain = TRUE) {
      static $px;
      if (!isset($px))
         $px = SEOLinksPlugin::prefix();

      $discussion = (object) $discussion;
      $cat = FALSE;

      // Some places call DiscussionUrl with a custom query that doesn't select CategoryID
      if (getValue('CategoryID', $discussion))
         $cat = CategoryModel::categories($discussion->CategoryID);

      if ($cat)
         $cat = rawurlencode($cat['UrlCode']);
      else
         $cat = 'x';

      $name = Gdn_Format::url(html_entity_decode($discussion->Name, ENT_QUOTES, 'UTF-8'));
      // Make sure the forum doesn't end with the page number notation.
      if (preg_match('`(-p\d+)$`', $name, $matches)) {
         $name = substr($name, 0, -strlen($matches[1]));
      }

      if ($page) {
         if ($page == 1 && !Gdn::session()->UserID)
            $page = '';
         else
            $page = '-p' . $page;
      }

      $path = "/$px$cat/{$discussion->DiscussionID}-$name{$page}.html";

      return url($path, $withDomain);
   }

endif;

//if (!function_exists('UserUrl')):
//
//   /**
//    * Return the url for a user.
//    * @param array|object $User The user to get the url for.
//    * @param string $Px The prefix to apply before fieldnames. @since 2.1
//    * @return string The url suitable to be passed into the url() function.
//    */
//   function userUrl($User, $Px = '', $Method = '') {
//      static $NameUnique = NULL;
//      if ($NameUnique === NULL)
//         $NameUnique = c('Garden.Registration.NameUnique');
//
//      if ($Method)
//         $Method .= '/';
//
//      return '/members/' .$Method. ($NameUnique ? '' : getValue($Px . 'UserID', $User, 0) . '/') . rawurlencode(getValue($Px . 'Name', $User));
//   }
//
//
//
//endif;
