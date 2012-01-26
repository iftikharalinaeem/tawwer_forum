<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Reactions'] = array(
   'Name' => 'Reactions',
   'Description' => "Adds reaction options to discussions & comments.",
   'Version' => '1.0b',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class ReactionsPlugin extends Gdn_Plugin {
   /// Methods ///
   
   private function AddJs($Sender) {
      $Sender->AddJsFile('reactions.js', 'plugins/Reactions');
   }

   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      $St = Gdn::Structure();
      $Sql = Gdn::SQL();
      
      $St->Table('ReactionType')
         ->Column('UrlCode', 'varchar(20)', FALSE, 'primary')
         ->Column('Name', 'varchar(20)')
         ->Column('Description', 'text', TRUE)
         ->Column('TagID', 'int')
         ->Column('Attributes', 'text', TRUE)
         ->Set();
      
      $St->Table('UserTag')
         ->Column('RecordType', array('Discussion', 'Comment', 'User', 'Activity', 'ActivityComment'), FALSE, 'primary')
         ->Column('RecordID', 'int', FALSE, 'primary')
         ->Column('TagID', 'int', FALSE, 'primary')
         ->Column('UserID', 'int', FALSE, array('primary', 'key'))
         ->Column('DateInserted', 'datetime')
         ->Column('Total', 'int', 0)
         ->Set();
      
      $Rm = new ReactionModel();
      
      // Insert some default tags.
      $Rm->DefineReactionType(array('UrlCode' => 'Spam', 'Name' => 'Spam', 'Log' => 'Spam', 'LogThreshold' => 5, 'RemoveThreshold' => 5, 'ModeratorInc' => 5));
      $Rm->DefineReactionType(array('UrlCode' => 'Abuse', 'Name' => 'Abuse', 'Log' => 'Moderation', 'LogThreshold' => 5, 'RemoveThreshold' => 10, 'ModeratorInc' => 5));
      $Rm->DefineReactionType(array('UrlCode' => 'Troll', 'Name' => 'Troll', 'Log' => 'Moderation', 'LogThreshold' => 5, 'ModeratorInc' => 5));
      
      $Rm->DefineReactionType(array('UrlCode' => 'Agree', 'Name' => 'Agree', 'IncrementColumn' => 'Score'));
      $Rm->DefineReactionType(array('UrlCode' => 'Disagree', 'Name' => 'Disagree'));
      $Rm->DefineReactionType(array('UrlCode' => 'Awesome', 'Name' => 'Awesome', 'IncrementColumn' => 'Score'));
      $Rm->DefineReactionType(array('UrlCode' => 'OffTopic', 'Name' => 'Off Topic'));
   }
   
   /// Event Handlers ///
   
   /**
    * 
    * @param Gdn_Controller $Sender 
    */
   public function DiscussionController_Render_Before($Sender) {
      $Sender->ReactionsVersion = 2;
      
      if ($Sender->ReactionsVersion == 1) {
         $Sender->AddCssFile('reactions-1.css', 'plugins/Reactions');
      } else {
         $Sender->AddCssFile('reactions.css', 'plugins/Reactions');
         $this->AddJs($Sender);
      }
      
      include_once $Sender->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');
   }
   
   public function DiscussionController_AfterDiscussionBody_Handler($Sender, $Args) {
      WriteReactionBar($Args['Discussion']);
   }
   
   public function DiscussionController_AfterCommentBody_Handler($Sender, $Args) {
      WriteReactionBar($Args['Comment']);
      return;
      
      if ($Sender->ReactionsVersion == 2) {
      // Here's version 2
      ?>
      <div class="Reactions">
         <div class="Flag">
            <div class="Handle">
               <a href="#"><span class="ReactSprite ReactFlag"></span> <label>Flag</label></a>
            </div>
            <div class="Options">
               <strong>Flag &raquo;</strong>
               <a href="#"><span class="ReactSprite ReactFlag"></span> <label>Abuse</label></a>
               <a href="#"><span class="ReactSprite ReactSpam HasCount"></span> <label>Spam</label> <span class="Count">1</span></a>
               <a href="#"><span class="ReactSprite ReactTroll HasCount"></span> <label>Troll</label> <span class="Count">2</span></a>
            </div>
         </div>
         <div class="React">
            <div class="Handle">
               <a href="#"><span class="ReactSprite ReactAgree"></span> <label>React</label></a>
            </div>
            <div class="Options">
               <a href="#"><span class="ReactSprite ReactOffTopic"></span> <label>Off Topic</label></a>
               <a href="#"><span class="ReactSprite ReactDisagree"></span> <label>Disagree</label></a>
               <a href="#"><span class="ReactSprite ReactAgree"></span> <label>Agree</label></a>
               <a href="#"><span class="ReactSprite ReactAwesome HasCount"></span> <label>Awesome</label> <span class="Count">6</span></a>
               <strong>&laquo; React</strong>
            </div>
         </div>
      </div>
      <?php
      } else {
      // Here's version 1
      ?>
      <div class="Reactions">
         <div class="Flag">
            <div class="Closed">
               <a href="#"><span class="ReactSprite ReactFlag"></span> <label>Flag</label></a>
            </div>
            <div class="Open">
               <a href="#"><span class="ReactSprite ReactFlag"></span> <label>Abuse</label></a>
               <a href="#"><span class="ReactSprite ReactSpam HasCount"></span> <label>Spam</label> <span class="Count">1</span></a>
               <a href="#"><span class="ReactSprite ReactTroll HasCount"></span> <label>Troll</label> <span class="Count">2</span></a>
            </div>
         </div>
         <div class="React">
            <div class="Closed">
               <a href="#"><span class="ReactSprite ReactAgree"></span> <label>React</label></a>
            </div>
            <div class="Open">
               <a href="#"><span class="ReactSprite ReactOffTopic"></span> <label>Off Topic</label></a>
               <a href="#"><span class="ReactSprite ReactDisagree"></span> <label>Disagree</label></a>
               <a href="#"><span class="ReactSprite ReactAgree"></span> <label>Agree</label></a>
               <a href="#"><span class="ReactSprite ReactAwesome HasCount"></span> <label>Awesome</label> <span class="Count">6</span></a>
            </div>
         </div>
      </div>
      <?php
      }
   }
   
   /**
    *
    * @param Gdn_Controller $Sender
    * @param string $RecordType
    * @param string $ReactionType
    * @param int $ID
    * @param bool $Undo 
    */
   public function RootController_React_Create($Sender, $RecordType, $Reaction, $ID) {
      if (!Gdn::Session()->IsValid()) {
         throw new Gdn_UserException(T('You need to sign in before you can do this.'), 403);
      }
      
      include_once $Sender->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');
      
      if (count($Sender->Request->Post()) == 0)
         die('Requires Javascript');
      
      $ReactionType = ReactionModel::ReactionTypes($Reaction);
      
      $ReactionModel = new ReactionModel();
      $ReactionModel->React($RecordType, $ID, $Reaction);
      
      $Sender->Render('Blank', 'Utility', 'Dashboard');
   }
}