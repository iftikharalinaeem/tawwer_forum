<?php

if (!defined('APPLICATION'))
   exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */
// Define the plugin:
$PluginInfo['SEOLinks'] = array(
    'Name' => 'SEO Links',
    'Description' => "Changes the links to discussions and categories for pure SEO oriented forums.",
    'Version' => '1.0.9',
    'RequiredApplications' => array('Vanilla' => '2.1a'),
    'MobileFriendly' => TRUE,
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class SEOLinksPlugin extends Gdn_Plugin {

   /**
    *
    * @param Gdn_Router $Sender
    * @param type $Args 
    */
   public function Gdn_Router_AfterLoadRoutes_Handler($Sender, $Args) {
      $Routes = & $Args['Routes'];
      $Route = '/?[^/]+/(\d+)-(.*?)(?:-(p\d+))?.html';
      $Sender->Routes[$Route] = array(
          'Route' => $Route,
          'Key' => base64_encode($Route),
          'Destination' => '/discussion/$1/$2/$3',
          'Reserved' => FALSE,
          'Type' => 'Internal'
      );

      // Add all of the category routes.
      $Categories = CategoryModel::Categories();
      foreach ($Categories as $Category) {
         if (!$Category['UrlCode'])
            continue;
         
         $Route = '/?(' . preg_quote($Category['UrlCode']) . ')(?:/(p\d+))?/?(\?.*)?$';
         $Sender->Routes[$Route] = array(
             'Route' => $Route,
             'Key' => base64_encode($Route),
             'Destination' => '/categories/$1/$2',
             'Reserved' => FALSE,
             'Type' => 'Internal'
         );
      }

      $Route = '/?members/([^/]+)';
      $Sender->Routes[$Route] = array(
          'Route' => $Route,
          'Key' => base64_encode($Route),
          'Destination' => '/profile/$1',
          'Reserved' => FALSE,
          'Type' => 'Internal'
      );

//      decho($Sender->Routes);
//      die();
   }
   
   public function Setup() {
      if (class_exists('CategoryModel')) {
         CategoryModel::ClearCache();
      }
   }
}

if (!function_exists('CategoryUrl')):

   /**
    * Return a url for a category. This function is in here and not functions.general so that plugins can override.
    * @param array $Category
    * @return string
    */
   function CategoryUrl($Category, $Page = '', $WithDomain = TRUE) {
      if (is_string($Category))
         $Category = CategoryModel::Categories($Category);
      $Category = (array) $Category;

      $Result = '/' . rawurlencode($Category['UrlCode']) . '/';
      if ($Page && $Page > 1) {
         $Result .= 'p' . $Page . '/';
      }
      return Url($Result, $WithDomain);
   }

endif;

if (!function_exists('DiscussionUrl')):

   function DiscussionUrl($Discussion, $Page = '', $WithDomain = TRUE) {
      $Discussion = (object) $Discussion;
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

      $Path = "/$Cat/{$Discussion->DiscussionID}-$Name{$Page}.html";

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