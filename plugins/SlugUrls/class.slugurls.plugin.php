<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class SlugUrlsPlugin extends Gdn_Plugin {
   /// Methods ///

   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      Gdn::Structure()
         ->Table('Discussion')
         ->Column('Slug', 'varchar(191)', TRUE, 'index')
         ->Set();

      // Add a url for SimplePress.
//      Gdn::Router()->SetRoute('/?([^/]+)/([^/]+)/?(?:page-(\d+))?', '/discussion/slug/$2?category=$1&page=$3', 'Test');
   }

   public function Gdn_Router_AfterLoadRoutes_Handler($sender, $args) {
//      $Px = self::Prefix();
//      $PxEsc = preg_quote($Px);
      $pxEsc = '';

      // Add all of the category routes.
      $categories = CategoryModel::Categories();
      foreach ($categories as $category) {
         if (!$category['UrlCode'])
            continue;

         $route = '/?'.$pxEsc.'(' . preg_quote($category['UrlCode']) . ')/([^/.]+)/?(?:page-(\d+)/?)?$';
         $sender->Routes[$route] = [
             'Route' => $route,
             'Key' => base64_encode($route),
             'Destination' => '/discussion/slug/$2?page=$3&category=$1',
             'Reserved' => FALSE,
             'Type' => 'Internal'
         ];
      }
   }

   /// Event Handlers ///

   public function DiscussionController_Slug_Create($sender, $slug, $page = FALSE, $category = FALSE) {
      if (!$slug)
         throw NotFoundException('Discussion');

      // Grab the discussion.
      $discussion = Gdn::SQL()->GetWhere(
         'Discussion',
         ['Slug' => $slug])->FirstRow(DATASET_TYPE_ARRAY);

      if (!$discussion)
         throw NotFoundException('Discussion');

      $url = DiscussionUrl($discussion, $page);
      redirectTo($url, 301);
   }
}
