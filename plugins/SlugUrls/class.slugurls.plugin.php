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

   public function Gdn_Router_AfterLoadRoutes_Handler($Sender, $Args) {
//      $Px = self::Prefix();
//      $PxEsc = preg_quote($Px);
      $PxEsc = '';

      // Add all of the category routes.
      $Categories = CategoryModel::Categories();
      foreach ($Categories as $Category) {
         if (!$Category['UrlCode'])
            continue;

         $Route = '/?'.$PxEsc.'(' . preg_quote($Category['UrlCode']) . ')/([^/.]+)/?(?:page-(\d+)/?)?$';
         $Sender->Routes[$Route] = [
             'Route' => $Route,
             'Key' => base64_encode($Route),
             'Destination' => '/discussion/slug/$2?page=$3&category=$1',
             'Reserved' => FALSE,
             'Type' => 'Internal'
         ];
      }
   }

   /// Event Handlers ///

   public function DiscussionController_Slug_Create($Sender, $Slug, $Page = FALSE, $Category = FALSE) {
      if (!$Slug)
         throw NotFoundException('Discussion');

      // Grab the discussion.
      $Discussion = Gdn::SQL()->GetWhere(
         'Discussion',
         ['Slug' => $Slug])->FirstRow(DATASET_TYPE_ARRAY);

      if (!$Discussion)
         throw NotFoundException('Discussion');

      $Url = DiscussionUrl($Discussion, $Page);
      redirectTo($Url, 301);
   }
}
