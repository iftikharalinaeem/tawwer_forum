<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['FAQ'] = array(
   'Name' => 'FAQ',
   'Description' => "Take a category of discussions & it's subcategories, organizing & displaying them as FAQs",
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca'
);

class FAQPlugin extends Gdn_Plugin {
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      include dirname(__FILE__).'/structure.php';
   }
   
   public function DiscussionController_FAQ_Create($Sender) {
      // What is the FAQ category?
      $FAQCategoryID = C('Plugins.FAQ.CategoryID', 0);
      // Load all of the discussions
      $CategoryModel = new CategoryModel();
      $CategoryData = $CategoryModel->GetSubTree($FAQCategoryID);
      $Sender->SetData('CategoryData', $CategoryData);
      $CategoryIDs = array();
      foreach ($CategoryData as $Category) {
         $CategoryIDs[] = GetValue('CategoryID', $Category);
      }
      // Get all of the discussions in these categories
      $Limit = C('Plugins.FAQ.Limit', 200);
      if (!is_numeric($Limit)) $Limit = 200;
      $Sender->SetData('DiscussionData', Gdn::SQL()
         ->Select()
         ->From('Discussion')
         ->WhereIn('CategoryID', $CategoryIDs)
         ->Limit($Limit, 0) // Don't load too many discussions (this could be a helluva lot of data)
         ->Get());
      
      // Render
      $Sender->Render('faq', '', 'plugins/FAQ');
   }
}