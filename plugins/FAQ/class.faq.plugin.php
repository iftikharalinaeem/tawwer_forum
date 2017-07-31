<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class FAQPlugin extends Gdn_Plugin {
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      include dirname(__FILE__).'/structure.php';
   }
   
   public function InfoController_FAQs_Create($sender) {
      ForceNoSSL();
      // What is the FAQ category?
      $fAQCategoryID = C('Plugins.FAQ.CategoryID', 0);
      // Load all of the discussions
      $categoryModel = new CategoryModel();
      $categoryData = $categoryModel->GetSubTree($fAQCategoryID);
      $sender->SetData('CategoryData', $categoryData);
      $categoryIDs = [];
      foreach ($categoryData as $category) {
         $categoryIDs[] = GetValue('CategoryID', $category);
      }
      // Get all of the discussions in these categories
      $limit = C('Plugins.FAQ.Limit', 200);
      if (!is_numeric($limit)) $limit = 200;
      $sender->SetData('DiscussionData', Gdn::SQL()
         ->Select()
         ->From('Discussion')
         ->WhereIn('CategoryID', $categoryIDs)
         ->Limit($limit, 0) // Don't load too many discussions (this could be a helluva lot of data)
         ->Get());
      
      // Render
      $sender->Title('Frequently Asked Questions');
      $sender->AddCssFile('style.css');      
      $sender->AddCssFile('vfcom.css', 'vfcom');
      $sender->AddAsset('Panel', $sender->FetchView('sidemenu', 'info', 'vfcom'));
      $sender->Render('faq', '', 'plugins/FAQ');
   }
   
   public function Base_AfterVFComSideMenu_Handler($sender) {
      VFComWriteMenuItem('faqs', 'Frequently Asked Questions', 'info/faqs');
   }
}
