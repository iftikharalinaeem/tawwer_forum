<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class FAQPlugin extends Gdn_Plugin {
   public function setup() {
      $this->structure();
   }
   
   public function structure() {
      include dirname(__FILE__).'/structure.php';
   }
   
   public function infoController_fAQs_create($sender) {
      forceNoSSL();
      // What is the FAQ category?
      $fAQCategoryID = c('Plugins.FAQ.CategoryID', 0);
      // Load all of the discussions
      $categoryModel = new CategoryModel();
      $categoryData = $categoryModel->getSubTree($fAQCategoryID);
      $sender->setData('CategoryData', $categoryData);
      $categoryIDs = [];
      foreach ($categoryData as $category) {
         $categoryIDs[] = getValue('CategoryID', $category);
      }
      // Get all of the discussions in these categories
      $limit = c('Plugins.FAQ.Limit', 200);
      if (!is_numeric($limit)) $limit = 200;
      $sender->setData('DiscussionData', Gdn::sql()
         ->select()
         ->from('Discussion')
         ->whereIn('CategoryID', $categoryIDs)
         ->limit($limit, 0) // Don't load too many discussions (this could be a helluva lot of data)
         ->get());
      
      // Render
      $sender->title('Frequently Asked Questions');
      $sender->addCssFile('style.css');      
      $sender->addCssFile('vfcom.css', 'vfcom');
      $sender->addAsset('Panel', $sender->fetchView('sidemenu', 'info', 'vfcom'));
      $sender->render('faq', '', 'plugins/FAQ');
   }
   
   public function base_afterVFComSideMenu_handler($sender) {
      vFComWriteMenuItem('faqs', 'Frequently Asked Questions', 'info/faqs');
   }
}
