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

class RepliesPlugin extends Gdn_Plugin {
   /// Methods
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      Gdn::Structure()
         ->Table('Reply')
         ->PrimaryKey('ReplyID')
         ->Column('CommentID', 'int', FALSE, 'key') // negative are on discussions
         ->Column('Body', 'text') // Only textex
         ->Column('DateInserted', 'datetime')
         ->Column('InsertUserID', 'int')
         ->Column('DateUpdated', 'datetime', TRUE)
         ->Column('UpdateUserID', 'int', TRUE)
         ->Column('OldCommentID', 'int', TRUE)
         ->Set();
      
      Gdn::PermissionModel()->Define(array(
         'Vanilla.Replies.Add' => 'Garden.Profiles.Edit'
      ));
   }
   
   /// Event Handlers.
   
   /**
    * @param AssetModel $Sender
    */
   public function AssetModel_StyleCss_Handler($Sender, $Args) {
      $Sender->AddCssFile('replies.css', 'plugins/Replies');
   }
   
//   public function Base_BeforeCommentRender_Handler($Sender, $Args) {
//      if (!isset($Args['Comment']))
//         return;
//      
//      $Data = array($Args['Comment']);
//      $Model = new ReplyModel();
//      $D = NULL;
//      $Model->JoinReplies($D, $Data);
//   }
   
   
   public function Base_CommentOptions_Handler($Sender, $Args) {
      $Options =& $Args['CommentOptions'];
      
      if (isset($Options['EditComment'])) {
         $ID = GetValueR('Comment.CommentID', $Args);
         $Options['CommentToReply'] = array('Label' => T('Make Reply...'), 'Url' => "/discussion/commenttoreply?commentid=$ID", 'Class' => 'Popup');
      }
   }
   
   /**
    * 
    * @param PostController $Sender
    * @param type $Args
    */
   public function PostController_Render_Before($Sender, $Args) {
      if ($Sender->Request->IsPostBack() && isset($Sender->Data['Comments']) && strcasecmp($Sender->RequestMethod, 'editcomment') == 0) {
         $Model = new ReplyModel();
         $Discussion = NULL;
         $Model->JoinReplies($Discussion, $Sender->Data['Comments']);
      }
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
      if (isset($Sender->Data['Comments']) && is_a($Sender->Data['Comments'], 'Gdn_DataSet')) {
         $Model = new ReplyModel();
         $Model->JoinReplies($Sender->Data['Discussion'], $Sender->Data['Comments']->Result());
      }
   }
   
   /**
    * 
    * @param Gdn_Controller $Sender
    * @param int $ReplyID
    */
   public function DiscussionController_EditReply_Create($Sender, $ReplyID) {
      $Model = new ReplyModel();
      $Reply = $Model->GetID($ReplyID, DATASET_TYPE_ARRAY);
      $Discussion = $Model->GetRecord($Reply, TRUE);
      
      $Category = CategoryModel::Categories($Discussion['CategoryID']);
      $Sender->SetData('Category', $Category);
      $Sender->Permission('Vanilla.Comments.Edit', TRUE, 'Category', $Category['PermissionCategoryID']);
      
      $Form = new Gdn_Form();
      $Form->SetModel($Model);
      $Sender->ReplyForm = $Form;
      
      if ($Form->AuthenticatedPostBack()) {
         // Save the reply.
         $Form->SetFormValue('ReplyID', $ReplyID);
         
         if ($Form->GetFormValue('Cancel')) {
            $View = 'Reply';
         } elseif ($Form->Save()) {
            $Reply = $Model->GetID($ReplyID, DATASET_TYPE_ARRAY);
            $View = 'Reply';
         } else {
            $View = 'EditReply';
         }
      } else {
         $Form->SetData($Reply);
         $View = 'EditReply';
      }
      $Sender->SetData('Reply', $Reply);
      
      $Sender->Title(sprintf(T('Delete %s'), T('Reply')));
      $Sender->Render($View, '', 'plugins/Replies');
      
   }
   
   /**
    * 
    * @param Gdn_Controller $Sender
    * @param type $RepyID
    * @throws type
    */
   public function DiscussionController_DeleteReply_Create($Sender, $ReplyID) {
      $Model = new ReplyModel();
      $Discussion = $Model->GetRecord($ReplyID, TRUE);
      
      $Category = CategoryModel::Categories($Discussion['CategoryID']);
      $Sender->Permission('Vanilla.Comments.Delete', TRUE, 'Category', $Category['PermissionCategoryID']);
      
      $Form = new Gdn_Form();
      if ($Form->AuthenticatedPostBack()) {
         // Delete the reply.
         $Deleted = $Model->Delete(array('ReplyID' => $ReplyID));
         if ($Deleted) {
            $Sender->JsonTarget('#'.ReplyElementID($ReplyID), '', 'SlideUp');
         }
      }
      
      $Sender->Title(sprintf(T('Delete %s'), T('Reply')));
      $Sender->Render('DeleteReply', '', 'plugins/Replies');
   }
   
   /**
    * 
    * @param Gdn_Controller $Sender
    * @param array $Args
    * @return type
    */
   public function DiscussionController_Replies_Handler($Sender, $Args) {
      $Sender->ReplyForm = new Gdn_Form();
      $this->ClearForm($Sender->ReplyForm, array('reply', 'editreply'));
      
      if (isset($Args['Comment'])) {
         WriteReplies($Args['Comment']);
      } elseif (isset($Args['Discussion'])) {
         WriteReplies($Args['Discussion']);
      }
   }
   
   public function DiscussionController_CommentToReply_Create($Sender, $CommentID) {
      $ReplyModel = new ReplyModel();
      $CommentModel = new CommentModel();
      $DiscussionModel = new DiscussionModel();
      
      $Comment = $CommentModel->GetID($CommentID, DATASET_TYPE_ARRAY);
      if (!$Comment)
         throw NotFoundException('Comment');
      
      $Discussion = (array)$DiscussionModel->GetID($Comment['DiscussionID']);
      if (!$Discussion)
         throw NotFoundException('Discussion');
      
      $Category = CategoryModel::Categories($Discussion['CategoryID']);
      $Sender->Permission('Vanilla.Comments.Edit', 'CategoryID', $Category['PermissionCategoryID']);
      
      if ($Sender->Form->AuthenticatedPostBack()) {
         $ReplyToCommentID = $Sender->Form->GetFormValue('CommentID');
         if (!$ReplyToCommentID) {
//            $Form = new Gdn_Form();
            $Sender->Form->AddError('ValidateRequred', 'Target');
         } else {
            $ReplyID = $ReplyModel->MoveFromComment($Comment, $ReplyToCommentID);
            
            if ($ReplyID) {
               // Redirect to the comment or the discussion to show the new reply.
               $Row = $ReplyModel->GetRecord($ReplyID);
               if ($ReplyToCommentID < 0)
                  $Sender->RedirectUrl = DiscussionUrl($Row);
               else
                  $Sender->RedirectUrl = CommentUrl($Row);
               $Sender->Render('Blank', 'Utility', 'Dashboard');
            } else {
               $Sender->Form->SetValidationResults($ReplyModel->ValidationResults());
            }
         }
      }
      
      // We need to get a list of comments so that the user can select which to move to.
      // We'll select a window of comments around when the comment is.
      $Date = $Comment['DateInserted'];
      $CommentModel = new CommentModel();
      $CommentsBefore = $CommentModel->GetWhere(array('DiscussionID' => $Discussion['DiscussionID'], 'DateInserted <' => $Date), 'DateInserted', 'desc', 10)->ResultArray();
      $CommentsBefore = array_reverse($CommentsBefore);
      $CommentsAfter = $CommentModel->GetWhere(array('DiscussionID' => $Discussion['DiscussionID'], 'DateInserted >' => $Date), 'DateInserted', 'asc', 10)->ResultArray();
      
      $Comments = array_merge($CommentsBefore, $CommentsAfter);
      
      // Add a summary.
      foreach ($Comments as $Index => &$Row) {
         $Summary = SliceParagraph(Gdn_Format::PlainText($Row['Body'], $Row['Format']), 160);
         $Row['Summary'] = $Summary;
         if ($Row['CommentID'] == $Comment['CommentID'])
            $MyIndex = $Index;
      }
      if (isset($MyIndex))
         unset($Comments[$MyIndex]);
      
      $Discussion['Summary'] = $Discussion['Name'];
      array_unshift($Comments, $Discussion);
      
      Gdn::UserModel()->JoinUsers($Comments, array('InsertUserID'));
      $Sender->SetData('Comments', $Comments);
      
      
      switch (strtolower($Discussion['Type'])) {
         case 'question':
            $Code = 'Answer';
            $Sender->SetData('MoveMessage', T('You are about to make this answer a reply.'));
            break;
         default:
            $Code = 'Comment';
            $Sender->SetData('MoveMessage', T('You are about to make this comment a reply.'));
            break;
      }
      
      $Sender->Title(sprintf(T('Move %s'), T($Code)));
      $Sender->Render('CommentToReply', '', 'plugins/Replies');
   }
   
   /**
    * 
    * @param Gdn_Form $Form
    * @param type $AllowedMethods
    */
   protected function ClearForm($Form, $AllowedMethods) {
     $AllowedMethods = (array)$AllowedMethods;
     if (!in_array(Gdn::Controller()->RequestMethod, $AllowedMethods)) {
        $Form->SetData(array());
        $Form->FormValues(array());
     }
   }
   
   public function DiscussionController_ReplyToComment_Create($Sender, $ReplyID) {
      $Model = new ReplyModel();
      $Reply = $Model->GetID($ReplyID, DATASET_TYPE_ARRAY);
      $Discussion = $Model->GetRecord($Reply, TRUE);
      
      $Category = CategoryModel::Categories($Discussion['CategoryID']);
      $Sender->Permission('Vanilla.Comments.Edit', TRUE, 'Category', $Category['PermissionCategoryID']);
      
      if ($Sender->Form->AuthenticatedPostBack()) {
         $CommentID = $Model->MoveToComment($Reply, $Discussion);
         if ($CommentID) {
            $CommentModel = new CommentModel();
            $Comment = $CommentModel->GetID($CommentID);
            $Sender->RedirectUrl = CommentUrl($Comment);
         } else {
            $Sender->Form->SetValidationResults($Model->ValidationResults());
         }
      }
      
      switch (strtolower($Discussion['Type'])) {
         case 'question':
            $Sender->SetData('MoveMessage', T('You are about to make this reply an answer.'));
            break;
         default:
            $Sender->SetData('MoveMessage', T('You are about to make this reply a comment.'));
            break;
      }
      $Sender->Title(sprintf(T('Move %s'), T('Reply')));
      $Sender->Render('ReplyToComment', '', 'plugins/Replies');
   }
   
   /**
    * Add 'Quote' option to Discussion.
    */
   public function Base_AfterFlag_Handler($Sender, $Args) {
      if (!Gdn::Session()->CheckPermission('Vanilla.Replies.Add'))
         return;
      
      if (isset($Args['Comment'])) {
         echo Gdn_Theme::BulletItem('Flags');
         WriteReplyButton($Args['Comment']);
      } elseif (isset($Args['Discussion'])) {
         echo Gdn_Theme::BulletItem('Flags');
         WriteReplyButton($Args['Discussion']);
      }
   }
   
   /**
    * 
    * @param PostController $Sender
    * @param type $CommentID
    */
   public function PostController_Reply_Create($Sender, $CommentID) {
      $Sender->Permission('Vanilla.Replies.Add');
      
      $Model = new ReplyModel();
      
      // Make sure we have permission.
      $DiscussionModel = new DiscussionModel();
      if ($CommentID < 0) {
         $Discussion = $DiscussionModel->GetID(-$CommentID);
      } else {
         $CommentModel = new CommentModel();
         $Comment = $CommentModel->GetID($CommentID);
         $Discussion = $DiscussionModel->GetID(GetValue('DiscussionID', $Comment));
      }
      if (!$Discussion)
         throw NotFoundException('Discussion');
      
      $Category = CategoryModel::Categories(GetValue('CategoryID', $Discussion));
      $Sender->Permission('Vanilla.Comments.Add', TRUE, 'Category', $Category['PermissionCategoryID']);
      $Sender->SetData('Category', $Category);
      
      $Form = new Gdn_Form();
      $Sender->ReplyForm = $Form;
      $Form->SetModel($Model);
      
      require_once $Sender->FetchViewLocation('reply_functions', '', 'plugins/Replies');
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);
      
      if ($Form->AuthenticatedPostBack()) {
         $Container = '#'.RepliesElementID($CommentID);
         
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

function ReplyRecordID($Row) {
   $ID = GetValue('CommentID', $Row);
   if (!$ID)
      $ID = -GetValue('DiscussionID', $Row);
   return $ID;
}

function ReplyElementID($ID) {
   return 'Reply_'.$ID;
}

function RepliesElementID($ID) {
   if (is_array($ID) || is_object($ID))
      $ID = ReplyRecordID($ID);
   
   return 'Replies_'.str_replace('-', 'd', $ID);
}

function GetReplyOptions($Reply) {
   static $Permissions = null;
   if (!isset($Permissions)) {
      $Category = Gdn::Controller()->Data('Category');
      if ($Category) {
         $Permissions = array(
               'Delete' => Gdn::Session()->CheckPermission('Vanilla.Comments.Delete', TRUE, 'Category', $Category['PermissionCategoryID']),
               'Edit' => Gdn::Session()->CheckPermission('Vanilla.Comments.Edit', TRUE, 'Category', $Category['PermissionCategoryID'])
            );
      } else {
         $Permissions = array(
            'Delete' => FALSE,
            'Edit' => FALSE
            );
      }
   }
   
   $ID = GetValue('ReplyID', $Reply);
   
   $Result = array();
   
   if ($Permissions['Edit']) {
      $Result['EditReply'] = array('Label' => T('Edit'), 'Url' => "/discussion/editreply?replyid=$ID");
   }
   if ($Permissions['Delete'])
      $Result['DeleteReply'] = array('Label' => T('Delete'), 'Url' => "/discussion/deletereply?replyid=$ID", 'Class' => 'Popup');
   if ($Permissions['Edit']) {
      $Result['ReplyToComment'] = array('Label' => T('Make Comment'), 'Url' => "/discussion/replytocomment?replyid=$ID", 'Class' => 'Popup');
   }
   
   
   return $Result;
}
