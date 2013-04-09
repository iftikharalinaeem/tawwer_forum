<?php if (!defined('APPLICATION')) exit();

/**
 * 
 * Changes:
 *  1.0     Release
 * 
 * 
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

$PluginInfo['Replies'] = array(
   'Name' => 'Inline Replies',
   'Description' => "Adds one level of inline replies to comments.",
   'Version' => '1.0b',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'MobileFriendly' => TRUE
);

class RepliesPlugin extends Gdn_Plugin {
   /// Methods
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      Gdn::Structure()
         ->Table('Reply')
         ->PrimaryKey('ReplyID')
         ->Column('CommentID', 'int', FALSE, 'key')
         ->Column('Body', 'text') // Only textex
         ->Column('DateInserted', 'datetime')
         ->Column('InsertUserID', 'int')
         ->Column('DateUpdated', 'datetime', TRUE)
         ->Column('UpdateUserID', 'int', TRUE)
         ->Set();
   }
   
   /// Event Handlers.
   
   /**
    * @param AssetModel $Sender
    */
   public function AssetModel_StyleCss_Handler($Sender, $Args) {
      $Sender->AddCssFile('replies.css', 'plugins/Replies');
   }
   
   /**
    * 
    * @param Gdn_Controller $Sender
    * @return type
    */
   public function Base_Render_Before($Sender) {
      if (InSection('Dashboard'))
         return;
      
      if (!function_exists('WriteReplies')) {
         require_once $Sender->FetchViewLocation('reply_functions', '', 'plugins/Replies');
      }
      
      $Sender->AddJsFile('replies.js', 'plugins/Replies');
   }
   
   public function DiscussionController_Render_Before($Sender) {
      if (isset($Sender->Data['Comments'])) {
         $Model = new ReplyModel();
         $Model->JoinReplies($Sender->Data['Comments']->Result());
      }
   }
   
   /**
    * 
    * @param Gdn_Controller $Sender
    * @param array $Args
    * @return type
    */
   public function DiscussionController_Replies_Handler($Sender, $Args) {
      if (!isset($Args['Comment']))
         return;
      
      $Sender->ReplyForm = new Gdn_Form();
      WriteReplies($Args['Comment']);
   }
   
   /**
    * Add 'Quote' option to Discussion.
    */
   public function DiscussionController_AfterFlag_Handler($Sender, $Args) {
      if (!isset($Args['Comment']))
         return;
      
      echo Gdn_Theme::BulletItem('Flags');
      WriteReplyButton($Args['Comment']);
   }
   
   /**
    * 
    * @param PostController $Sender
    * @param type $CommentID
    */
   public function PostController_Reply_Create($Sender, $CommentID) {
      $Model = new ReplyModel();
      
      $Form = new Gdn_Form();
      $Sender->ReplyForm = $Form;
      $Form->SetModel($Model);
      
      require_once $Sender->FetchViewLocation('reply_functions', '', 'plugins/Replies');
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);
      
      if ($Form->AuthenticatedPostBack()) {
         $Container = '#Replies_'.$CommentID;
         
         $Form->SetFormValue('CommentID', $CommentID);
         if ($ReplyID = $Form->Save()) {
            $Reply = $Model->GetID($ReplyID, DATASET_TYPE_ARRAY);
            
            ob_start();
            WriteReply($Reply);
            $ReplyHtml = ob_get_clean();
            
            $Sender->JsonTarget("$Container .Item-ReplyForm", $ReplyHtml, 'Before');
            
            $Form->SetFormValue('Body', '');
         }
         
         ob_start();
         WriteReplyForm(array('CommentID' => $CommentID));
         $FormHtml = ob_get_clean();
         $Sender->JsonTarget("$Container .Item-ReplyForm", $FormHtml, 'ReplaceWith');
      } else {
         throw ForbiddenException('GET');
      }
      
      $Sender->Render('Blank', 'Utility', 'Dashboard');
   }
}