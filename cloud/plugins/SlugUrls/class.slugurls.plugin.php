<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class SlugUrlsPlugin extends Gdn_Plugin {
   /// Methods ///

   public function setup() {
      $this->structure();
   }

   public function structure() {
      Gdn::structure()
         ->table('Discussion')
         ->column('Slug', 'varchar(191)', TRUE, 'index')
         ->set();

      // Add a url for SimplePress.
//      Gdn::router()->setRoute('/?([^/]+)/([^/]+)/?(?:page-(\d+))?', '/discussion/slug/$2?category=$1&page=$3', 'Test');
   }

   public function gdn_Router_AfterLoadRoutes_Handler($sender, $args) {
//      $Px = self::prefix();
//      $PxEsc = preg_quote($Px);
      $pxEsc = '';

      // Add all of the category routes.
      $categories = CategoryModel::categories();
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

   public function discussionController_slug_create($sender, $slug, $page = FALSE, $category = FALSE) {
      if (!$slug)
         throw notFoundException('Discussion');

      // Grab the discussion.
      $discussion = Gdn::sql()->getWhere(
         'Discussion',
         ['Slug' => $slug])->firstRow(DATASET_TYPE_ARRAY);

      if (!$discussion)
         throw notFoundException('Discussion');

      $url = discussionUrl($discussion, $page);
      redirectTo($url, 301);
   }
}
