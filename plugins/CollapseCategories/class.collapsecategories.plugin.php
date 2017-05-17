<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class CollapseCategoriesPlugin extends Gdn_Plugin {
   
   /// Event handlers.
   
   public function CategoriesController_Render_Before($Sender) {
      Gdn::Controller()->AddJsFile('collapsecategories.js', 'plugins/CollapseCategories');
      Gdn::Controller()->AddCssFile('collapsecategories.css', 'plugins/CollapseCategories');
   }
}
