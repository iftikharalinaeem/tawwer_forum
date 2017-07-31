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

      Gdn::PermissionModel()->Define([
         'Vanilla.Replies.Add' => 'Garden.Profiles.Edit'
      ]);
   }

   /// Event Handlers.

   /**
    * @param AssetModel $sender
    */
   public function AssetModel_StyleCss_Handler($sender, $args) {
      $sender->AddCssFile('replies.css', 'plugins/Replies');
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


   public function Base_CommentOptions_Handler($sender, $args) {
      $options =& $args['CommentOptions'];

      if (isset($options['EditComment'])) {
         $iD = GetValueR('Comment.CommentID', $args);
         $options['CommentToReply'] = ['Label' => T('Make Reply...'), 'Url' => "/discussion/commenttoreply?commentid=$iD", 'Class' => 'Popup'];
      }
   }

   /**
    *
    * @param PostController $sender
    * @param type $args
    */
   public function PostController_Render_Before($sender, $args) {
      if ($sender->Request->IsPostBack() && isset($sender->Data['Comments']) && strcasecmp($sender->RequestMethod, 'editcomment') == 0) {
         $model = new ReplyModel();
         $discussion = NULL;
         $model->JoinReplies($discussion, $sender->Data['Comments']);
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

   public function DiscussionController_Render_Before($sender) {
      if (isset($sender->Data['Comments']) && is_a($sender->Data['Comments'], 'Gdn_DataSet')) {
         $model = new ReplyModel();
         $model->JoinReplies($sender->Data['Discussion'], $sender->Data['Comments']->Result());
      }
   }

   /**
    *
    * @param Gdn_Controller $sender
    * @param int $replyID
    */
   public function DiscussionController_EditReply_Create($sender, $replyID) {
      $model = new ReplyModel();
      $reply = $model->GetID($replyID, DATASET_TYPE_ARRAY);
      $discussion = $model->GetRecord($reply, TRUE);

      $category = CategoryModel::Categories($discussion['CategoryID']);
      $sender->SetData('Category', $category);
      $sender->Permission('Vanilla.Comments.Edit', TRUE, 'Category', $category['PermissionCategoryID']);

      $form = new Gdn_Form();
      $form->SetModel($model);
      $sender->ReplyForm = $form;

      if ($form->AuthenticatedPostBack()) {
         // Save the reply.
         $form->SetFormValue('ReplyID', $replyID);

         if ($form->GetFormValue('Cancel')) {
            $view = 'Reply';
         } elseif ($form->Save()) {
            $reply = $model->GetID($replyID, DATASET_TYPE_ARRAY);
            $view = 'Reply';
         } else {
            $view = 'EditReply';
         }
      } else {
         $form->SetData($reply);
         $view = 'EditReply';
      }
      $sender->SetData('Reply', $reply);

      $sender->Title(sprintf(T('Delete %s'), T('Reply')));
      $sender->Render($view, '', 'plugins/Replies');

   }

   /**
    *
    * @param Gdn_Controller $sender
    * @param type $RepyID
    * @throws type
    */
   public function DiscussionController_DeleteReply_Create($sender, $replyID) {
      $model = new ReplyModel();
      $discussion = $model->GetRecord($replyID, TRUE);

      $category = CategoryModel::Categories($discussion['CategoryID']);
      $sender->Permission('Vanilla.Comments.Delete', TRUE, 'Category', $category['PermissionCategoryID']);

      $form = new Gdn_Form();
      if ($form->AuthenticatedPostBack()) {
         // Delete the reply.
         $deleted = $model->Delete(['ReplyID' => $replyID]);
         if ($deleted) {
            $sender->JsonTarget('#'.ReplyElementID($replyID), '', 'SlideUp');
         }
      }

      $sender->Title(sprintf(T('Delete %s'), T('Reply')));
      $sender->Render('DeleteReply', '', 'plugins/Replies');
   }

   /**
    *
    * @param Gdn_Controller $sender
    * @param array $args
    * @return type
    */
   public function DiscussionController_Replies_Handler($sender, $args) {
      $sender->ReplyForm = new Gdn_Form();
      $this->ClearForm($sender->ReplyForm, ['reply', 'editreply']);

      if (isset($args['Comment'])) {
         WriteReplies($args['Comment']);
      } elseif (isset($args['Discussion'])) {
         WriteReplies($args['Discussion']);
      }
   }

    /**
     * @param DiscussionController $sender
     * @param int $commentID
     */
   public function DiscussionController_CommentToReply_Create($sender, $commentID) {
      $replyModel = new ReplyModel();
      $commentModel = new CommentModel();
      $discussionModel = new DiscussionModel();

      $comment = $commentModel->GetID($commentID, DATASET_TYPE_ARRAY);
      if (!$comment)
         throw NotFoundException('Comment');

      $discussion = (array)$discussionModel->GetID($comment['DiscussionID']);
      if (!$discussion)
         throw NotFoundException('Discussion');

      $category = CategoryModel::Categories($discussion['CategoryID']);
      $sender->Permission('Vanilla.Comments.Edit', 'CategoryID', $category['PermissionCategoryID']);

      if ($sender->Form->AuthenticatedPostBack()) {
         $replyToCommentID = $sender->Form->GetFormValue('CommentID');
         if (!$replyToCommentID) {
//            $Form = new Gdn_Form();
            $sender->Form->AddError('ValidateRequred', 'Target');
         } else {
            $replyID = $replyModel->MoveFromComment($comment, $replyToCommentID);

            if ($replyID) {
               // Redirect to the comment or the discussion to show the new reply.
               $row = $replyModel->GetRecord($replyID);
               if ($replyToCommentID < 0)
                  $sender->setRedirectTo(DiscussionUrl($row));
               else
                  $sender->setRedirectTo(CommentUrl($row));
               $sender->Render('Blank', 'Utility', 'Dashboard');
            } else {
               $sender->Form->SetValidationResults($replyModel->ValidationResults());
            }
         }
      }

      // We need to get a list of comments so that the user can select which to move to.
      // We'll select a window of comments around when the comment is.
      $date = $comment['DateInserted'];
      $commentModel = new CommentModel();
      $commentsBefore = $commentModel->GetWhere(['DiscussionID' => $discussion['DiscussionID'], 'DateInserted <' => $date], 'DateInserted', 'desc', 10)->ResultArray();
      $commentsBefore = array_reverse($commentsBefore);
      $commentsAfter = $commentModel->GetWhere(['DiscussionID' => $discussion['DiscussionID'], 'DateInserted >' => $date], 'DateInserted', 'asc', 10)->ResultArray();

      $comments = array_merge($commentsBefore, $commentsAfter);

      // Add a summary.
      foreach ($comments as $index => &$row) {
         $summary = SliceParagraph(Gdn_Format::PlainText($row['Body'], $row['Format']), 160);
         $row['Summary'] = $summary;
         if ($row['CommentID'] == $comment['CommentID'])
            $myIndex = $index;
      }
      if (isset($myIndex))
         unset($comments[$myIndex]);

      $discussion['Summary'] = $discussion['Name'];
      array_unshift($comments, $discussion);

      Gdn::UserModel()->JoinUsers($comments, ['InsertUserID']);
      $sender->SetData('Comments', $comments);


      switch (strtolower($discussion['Type'])) {
         case 'question':
            $code = 'Answer';
            $sender->SetData('MoveMessage', T('You are about to make this answer a reply.'));
            break;
         default:
            $code = 'Comment';
            $sender->SetData('MoveMessage', T('You are about to make this comment a reply.'));
            break;
      }

      $sender->Title(sprintf(T('Move %s'), T($code)));
      $sender->Render('CommentToReply', '', 'plugins/Replies');
   }

   /**
    *
    * @param Gdn_Form $form
    * @param type $allowedMethods
    */
   protected function ClearForm($form, $allowedMethods) {
     $allowedMethods = (array)$allowedMethods;
     if (!in_array(Gdn::Controller()->RequestMethod, $allowedMethods)) {
        $form->SetData([]);
        $form->FormValues([]);
     }
   }

    /**
     * @param DiscussionController $sender
     * @param int $replyID
     */
   public function DiscussionController_ReplyToComment_Create($sender, $replyID) {
      $model = new ReplyModel();
      $reply = $model->GetID($replyID, DATASET_TYPE_ARRAY);
      $discussion = $model->GetRecord($reply, TRUE);

      $category = CategoryModel::Categories($discussion['CategoryID']);
      $sender->Permission('Vanilla.Comments.Edit', TRUE, 'Category', $category['PermissionCategoryID']);

      if ($sender->Form->AuthenticatedPostBack()) {
         $commentID = $model->MoveToComment($reply, $discussion);
         if ($commentID) {
            $commentModel = new CommentModel();
            $comment = $commentModel->GetID($commentID);
            $sender->setRedirectTo(CommentUrl($comment));
         } else {
            $sender->Form->SetValidationResults($model->ValidationResults());
         }
      }

      switch (strtolower($discussion['Type'])) {
         case 'question':
            $sender->SetData('MoveMessage', T('You are about to make this reply an answer.'));
            break;
         default:
            $sender->SetData('MoveMessage', T('You are about to make this reply a comment.'));
            break;
      }
      $sender->Title(sprintf(T('Move %s'), T('Reply')));
      $sender->Render('ReplyToComment', '', 'plugins/Replies');
   }

   /**
    * Add 'Quote' option to Discussion.
    */
   public function Base_AfterFlag_Handler($sender, $args) {
      if (!Gdn::Session()->CheckPermission('Vanilla.Replies.Add'))
         return;

      if (isset($args['Comment'])) {
         echo Gdn_Theme::BulletItem('Flags');
         WriteReplyButton($args['Comment']);
      } elseif (isset($args['Discussion'])) {
         echo Gdn_Theme::BulletItem('Flags');
         WriteReplyButton($args['Discussion']);
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
         WriteReplyForm(['CommentID' => $CommentID]);
         $FormHtml = ob_get_clean();
         $Sender->JsonTarget("$Container .Item-ReplyForm", $FormHtml, 'ReplaceWith');
      } else {
         throw ForbiddenException('GET');
      }

      $Sender->Render('Blank', 'Utility', 'Dashboard');
   }
}

function ReplyRecordID($row) {
   $iD = GetValue('CommentID', $row);
   if (!$iD)
      $iD = -GetValue('DiscussionID', $row);
   return $iD;
}

function ReplyElementID($iD) {
   return 'Reply_'.$iD;
}

function RepliesElementID($iD) {
   if (is_array($iD) || is_object($iD))
      $iD = ReplyRecordID($iD);

   return 'Replies_'.str_replace('-', 'd', $iD);
}

function GetReplyOptions($reply) {
   static $permissions = null;
   if (!isset($permissions)) {
      $category = Gdn::Controller()->Data('Category');
      if ($category) {
         $permissions = [
               'Delete' => Gdn::Session()->CheckPermission('Vanilla.Comments.Delete', TRUE, 'Category', $category['PermissionCategoryID']),
               'Edit' => Gdn::Session()->CheckPermission('Vanilla.Comments.Edit', TRUE, 'Category', $category['PermissionCategoryID'])
            ];
      } else {
         $permissions = [
            'Delete' => FALSE,
            'Edit' => FALSE
            ];
      }
   }

   $iD = GetValue('ReplyID', $reply);

   $result = [];

   if ($permissions['Edit']) {
      $result['EditReply'] = ['Label' => T('Edit'), 'Url' => "/discussion/editreply?replyid=$iD"];
   }
   if ($permissions['Delete'])
      $result['DeleteReply'] = ['Label' => T('Delete'), 'Url' => "/discussion/deletereply?replyid=$iD", 'Class' => 'Popup'];
   if ($permissions['Edit']) {
      $result['ReplyToComment'] = ['Label' => T('Make Comment'), 'Url' => "/discussion/replytocomment?replyid=$iD", 'Class' => 'Popup'];
   }


   return $result;
}
