<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class CollapseCategoriesPlugin extends Gdn_Plugin {
   
   /// Event handlers.
   
   public function categoriesController_render_before($sender) {
      Gdn::controller()->addJsFile('collapsecategories.js', 'plugins/CollapseCategories');
      Gdn::controller()->addCssFile('collapsecategories.css', 'plugins/CollapseCategories');
   }
}
