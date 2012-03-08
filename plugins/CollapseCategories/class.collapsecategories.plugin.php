<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['CollapseCategories'] = array(
   'Name' => 'Collapse Categories',
   'Description' => 'Adds +/- icons beside category names so that they can be collapsed/expanded.',
   'Version' => '1.0.1b',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'MobileFriendly' => FALSE,
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class CollapseCategoriesPlugin extends Gdn_Plugin {
   
   /// Event handlers.
   
   public function CategoriesController_Render_Before($Sender) {
      Gdn::Controller()->AddJsFile('collapsecategories.js', 'plugins/CollapseCategories');
      Gdn::Controller()->AddCssFile('collapsecategories.css', 'plugins/CollapseCategories');
   }
}