<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 tim Gunter.
Vanilla BugTracker is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Vanilla BugTracker is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Tim Gunter. at gunter.tim [at] gmail [dot] com
*/

// Define the plugin:
$PluginInfo['BugTracker'] = array(
   'Description' => 'This plugin turns Vanilla into a BugTracking tool.',
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.0.17.4a'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => array('Tagging' => '1.0.1'),
   'HasLocale' => TRUE,
   'SettingsUrl' => FALSE,
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'gunter.tim@gmail.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class BugTrackerPlugin extends Gdn_Plugin {
   
   public function __construct() {
      
   }
   
   /**
   * Modify the default behaviour of the categories feature
   * 
   * We hide the normal category chooser since we'll be drawing it ourselves later
   * and naming it 'Component' instead. Also add the CSS rules for rendering the bug
   * options table.
   */
   public function PostController_BeforeDiscussionRender_Handler($Sender) {
      // Include our own CSS
      $Sender->AddCssFile($this->GetWebResource('design/postbug.css'));
      
      // We want to draw our own selector for the component
      $Sender->ShowCategorySelector = FALSE;
   }
   
   /**
   * Hide the default 'Announce' and 'Close' options since they don't apply to bugs
   */
   public function PostController_DiscussionFormOptions_Handler($Sender) {
      $Sender->EventArguments['Options'] = '';
   }
   
   /**
   * Render the BugTracker-specific options on the Report a Bug screen
   */
   public function PostController_BeforeBodyInput_Handler($Sender) {
      echo $Sender->FetchView($this->GetView('post/bugoptions.php'));
      echo $Sender->Form->Label('Bug Description', 'Body');
   }
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      $Structure = Gdn::Structure();
      $SQL = Gdn::SQL();
      
      $Structure
         ->Table('Tag')
         ->Column('TagType', array('bug','version','component'), 'bug')
         ->Set(FALSE, FALSE);
      
      if ($SQL->GetWhere('Tag', array('TagType' => 'version', 'Name' => 'all'))->NumRows() == 0)
         $SQL->Insert('Tag', array('Name' => 'all', 'InsertUserID' => 1, 'DateInserted' => date('Y-m-d H:i:s'), 'TagType' => 'version'));
         
      $Structure
         ->Table('Discussion')
         ->Column('Status', array('new','assigned','wontfix','notabug','closed','fixed'), 'new')
         ->Column('Type', array('bug','feature','ui','typo'), FALSE)
         ->Column('Priority', array('blocker','critical','normal','low','hold'), TRUE)
         ->Column('Version', 'int(11)', FALSE)
         ->Column('AssignedUserID', 'int(11)', TRUE)
         ->Column('DateAssigned', 'datetime', TRUE)
         ->Set(FALSE, FALSE);
   }
   
}