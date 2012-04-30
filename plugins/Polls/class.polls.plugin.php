<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Polls'] = array(
   'Name' => 'Polls',
   'Description' => "Allow users to create and vote on polls.",
   'Version' => '1.0a',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca'
);

class PollsPlugin extends Gdn_Plugin {
   /// Methods ///
   
//   private function AddJs($Sender) {
//      $Sender->AddJsFile('jquery-ui-1.8.17.custom.min.js');
//      $Sender->AddJsFile('reactions.js', 'plugins/Reactions');
//   }
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      include dirname(__FILE__).'/structure.php';
   }
   
   /** 
    * Add the poll form to vanilla's post page.
    */
   public function PostController_AfterForms_Handler($Sender) {
      $Forms = $Sender->Data('Forms');
      $Forms[] = array('Name' => 'Poll', 'Label' => Sprite('SpNewPoll').T('New Poll'), 'Url' => 'post/poll');
		$Sender->SetData('Forms', $Forms);
   }
   
   /** 
    * Create the new poll method on post controller.
    */
   public function PostController_Poll_Create($Sender) {
      $Sender->Render('add', '', 'plugins/Polls');
   }
   
}