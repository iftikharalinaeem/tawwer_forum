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

   public function setup() {
      $this->structure();
   }

   public function structure() {
      Gdn::structure()
         ->table('Reply')
         ->primaryKey('ReplyID')
         ->column('CommentID', 'int', FALSE, 'key') // negative are on discussions
         ->column('Body', 'text') // Only textex
         ->column('DateInserted', 'datetime')
         ->column('InsertUserID', 'int')
         ->column('DateUpdated', 'datetime', TRUE)
         ->column('UpdateUserID', 'int', TRUE)
         ->column('OldCommentID', 'int', TRUE)
         ->set();

      Gdn::permissionModel()->define([
         'Vanilla.Replies.Add' => 'Garden.Profiles.Edit'
      ]);
   }

   /// Event Handlers.

   /**
    * @param AssetModel $sender
    */
   public function assetModel_styleCss_handler($sender, $args) {
      $sender->addCssFile('replies.css', 'plugins/Replies');
   }

//   public function base_BeforeCommentRender_Handler($Sender, $Args) {
//      if (!isset($Args['Comment']))
//         return;
//
//      $Data = array($Args['Comment']);
//      $Model = new replyModel();
//      $D = NULL;
//      $Model->joinReplies($D, $Data);
//   }


   public function base_commentOptions_handler($sender, $args) {
      $options =& $args['CommentOptions'];

      if (isset($options['EditComment'])) {
         $iD = getValueR('Comment.CommentID', $args);
         $options['CommentToReply'] = ['Label' => t('Make Reply...'), 'Url' => "/discussion/commenttoreply?commentid=$iD", 'Class' => 'Popup'];
      }
   }

   /**
    *
    * @param PostController $sender
    * @param type $args
    */
   public function postController_render_before($sender, $args) {
      if ($sender->Request->isPostBack() && isset($sender->Data['Comments']) && strcasecmp($sender->RequestMethod, 'editcomment') == 0) {
         $model = new ReplyModel();
         $discussion = NULL;
         $model->joinReplies($discussion, $sender->Data['Comments']);
      }
   }

   /**
    *
    * @param Gdn_Controller $Sender
    * @return type
    */
   public function base_render_before($Sender) {
      if (inSection('Dashboard'))
         return;

      if (!function_exists('WriteReplies')) {
         require_once $Sender->fetchViewLocation('reply_functions', '', 'plugins/Replies');
      }

      $Sender->addJsFile('replies.js', 'plugins/Replies');
   }

   public function discussionController_render_before($sender) {
      if (isset($sender->Data['Comments']) && is_a($sender->Data['Comments'], 'Gdn_DataSet')) {
         $model = new ReplyModel();
         $model->joinReplies($sender->Data['Discussion'], $sender->Data['Comments']->result());
      }
   }

   /**
    *
    * @param Gdn_Controller $sender
    * @param int $replyID
    */
   public function discussionController_editReply_create($sender, $replyID) {
      $model = new ReplyModel();
      $reply = $model->getID($replyID, DATASET_TYPE_ARRAY);
      $discussion = $model->getRecord($reply, TRUE);

      $category = CategoryModel::categories($discussion['CategoryID']);
      $sender->setData('Category', $category);
      $sender->permission('Vanilla.Comments.Edit', TRUE, 'Category', $category['PermissionCategoryID']);

      $form = new Gdn_Form();
      $form->setModel($model);
      $sender->ReplyForm = $form;

      if ($form->authenticatedPostBack()) {
         // Save the reply.
         $form->setFormValue('ReplyID', $replyID);

         if ($form->getFormValue('Cancel')) {
            $view = 'Reply';
         } elseif ($form->save()) {
            $reply = $model->getID($replyID, DATASET_TYPE_ARRAY);
            $view = 'Reply';
         } else {
            $view = 'EditReply';
         }
      } else {
         $form->setData($reply);
         $view = 'EditReply';
      }
      $sender->setData('Reply', $reply);

      $sender->title(sprintf(t('Delete %s'), t('Reply')));
      $sender->render($view, '', 'plugins/Replies');

   }

   /**
    *
    * @param Gdn_Controller $sender
    * @param type $RepyID
    * @throws type
    */
   public function discussionController_deleteReply_create($sender, $replyID) {
      $model = new ReplyModel();
      $discussion = $model->getRecord($replyID, TRUE);

      $category = CategoryModel::categories($discussion['CategoryID']);
      $sender->permission('Vanilla.Comments.Delete', TRUE, 'Category', $category['PermissionCategoryID']);

      $form = new Gdn_Form();
      if ($form->authenticatedPostBack()) {
         // Delete the reply.
         $deleted = $model->delete(['ReplyID' => $replyID]);
         if ($deleted) {
            $sender->jsonTarget('#'.replyElementID($replyID), '', 'SlideUp');
         }
      }

      $sender->title(sprintf(t('Delete %s'), t('Reply')));
      $sender->render('DeleteReply', '', 'plugins/Replies');
   }

   /**
    *
    * @param Gdn_Controller $sender
    * @param array $args
    * @return type
    */
   public function discussionController_replies_handler($sender, $args) {
      $sender->ReplyForm = new Gdn_Form();
      $this->clearForm($sender->ReplyForm, ['reply', 'editreply']);

      if (isset($args['Comment'])) {
         writeReplies($args['Comment']);
      } elseif (isset($args['Discussion'])) {
         writeReplies($args['Discussion']);
      }
   }

    /**
     * @param DiscussionController $sender
     * @param int $commentID
     */
   public function discussionController_commentToReply_create($sender, $commentID) {
      $replyModel = new ReplyModel();
      $commentModel = new CommentModel();
      $discussionModel = new DiscussionModel();

      $comment = $commentModel->getID($commentID, DATASET_TYPE_ARRAY);
      if (!$comment)
         throw notFoundException('Comment');

      $discussion = (array)$discussionModel->getID($comment['DiscussionID']);
      if (!$discussion)
         throw notFoundException('Discussion');

      $category = CategoryModel::categories($discussion['CategoryID']);
      $sender->permission('Vanilla.Comments.Edit', 'CategoryID', $category['PermissionCategoryID']);

      if ($sender->Form->authenticatedPostBack()) {
         $replyToCommentID = $sender->Form->getFormValue('CommentID');
         if (!$replyToCommentID) {
//            $Form = new gdn_Form();
            $sender->Form->addError('ValidateRequred', 'Target');
         } else {
            $replyID = $replyModel->moveFromComment($comment, $replyToCommentID);

            if ($replyID) {
               // Redirect to the comment or the discussion to show the new reply.
               $row = $replyModel->getRecord($replyID);
               if ($replyToCommentID < 0)
                  $sender->setRedirectTo(discussionUrl($row));
               else
                  $sender->setRedirectTo(commentUrl($row));
               $sender->render('Blank', 'Utility', 'Dashboard');
            } else {
               $sender->Form->setValidationResults($replyModel->validationResults());
            }
         }
      }

      // We need to get a list of comments so that the user can select which to move to.
      // We'll select a window of comments around when the comment is.
      $date = $comment['DateInserted'];
      $commentModel = new CommentModel();
      $commentsBefore = $commentModel->getWhere(['DiscussionID' => $discussion['DiscussionID'], 'DateInserted <' => $date], 'DateInserted', 'desc', 10)->resultArray();
      $commentsBefore = array_reverse($commentsBefore);
      $commentsAfter = $commentModel->getWhere(['DiscussionID' => $discussion['DiscussionID'], 'DateInserted >' => $date], 'DateInserted', 'asc', 10)->resultArray();

      $comments = array_merge($commentsBefore, $commentsAfter);

      // Add a summary.
      foreach ($comments as $index => &$row) {
         $summary = sliceParagraph(Gdn_Format::plainText($row['Body'], $row['Format']), 160);
         $row['Summary'] = $summary;
         if ($row['CommentID'] == $comment['CommentID'])
            $myIndex = $index;
      }
      if (isset($myIndex))
         unset($comments[$myIndex]);

      $discussion['Summary'] = $discussion['Name'];
      array_unshift($comments, $discussion);

      Gdn::userModel()->joinUsers($comments, ['InsertUserID']);
      $sender->setData('Comments', $comments);


      switch (strtolower($discussion['Type'])) {
         case 'question':
            $code = 'Answer';
            $sender->setData('MoveMessage', t('You are about to make this answer a reply.'));
            break;
         default:
            $code = 'Comment';
            $sender->setData('MoveMessage', t('You are about to make this comment a reply.'));
            break;
      }

      $sender->title(sprintf(t('Move %s'), t($code)));
      $sender->render('CommentToReply', '', 'plugins/Replies');
   }

   /**
    *
    * @param Gdn_Form $form
    * @param type $allowedMethods
    */
   protected function clearForm($form, $allowedMethods) {
     $allowedMethods = (array)$allowedMethods;
     if (!in_array(Gdn::controller()->RequestMethod, $allowedMethods)) {
        $form->setData([]);
        $form->formValues([]);
     }
   }

    /**
     * @param DiscussionController $sender
     * @param int $replyID
     */
   public function discussionController_replyToComment_create($sender, $replyID) {
      $model = new ReplyModel();
      $reply = $model->getID($replyID, DATASET_TYPE_ARRAY);
      $discussion = $model->getRecord($reply, TRUE);

      $category = CategoryModel::categories($discussion['CategoryID']);
      $sender->permission('Vanilla.Comments.Edit', TRUE, 'Category', $category['PermissionCategoryID']);

      if ($sender->Form->authenticatedPostBack()) {
         $commentID = $model->moveToComment($reply, $discussion);
         if ($commentID) {
            $commentModel = new CommentModel();
            $comment = $commentModel->getID($commentID);
            $sender->setRedirectTo(commentUrl($comment));
         } else {
            $sender->Form->setValidationResults($model->validationResults());
         }
      }

      switch (strtolower($discussion['Type'])) {
         case 'question':
            $sender->setData('MoveMessage', t('You are about to make this reply an answer.'));
            break;
         default:
            $sender->setData('MoveMessage', t('You are about to make this reply a comment.'));
            break;
      }
      $sender->title(sprintf(t('Move %s'), t('Reply')));
      $sender->render('ReplyToComment', '', 'plugins/Replies');
   }

   /**
    * Add 'Quote' option to Discussion.
    */
   public function base_afterFlag_handler($sender, $args) {
      if (!Gdn::session()->checkPermission('Vanilla.Replies.Add'))
         return;

      if (isset($args['Comment'])) {
         echo Gdn_Theme::bulletItem('Flags');
         writeReplyButton($args['Comment']);
      } elseif (isset($args['Discussion'])) {
         echo Gdn_Theme::bulletItem('Flags');
         writeReplyButton($args['Discussion']);
      }
   }

   /**
    *
    * @param PostController $Sender
    * @param type $CommentID
    */
   public function postController_reply_create($Sender, $CommentID) {
      $Sender->permission('Vanilla.Replies.Add');

      $Model = new ReplyModel();

      // Make sure we have permission.
      $DiscussionModel = new DiscussionModel();
      if ($CommentID < 0) {
         $Discussion = $DiscussionModel->getID(-$CommentID);
      } else {
         $CommentModel = new CommentModel();
         $Comment = $CommentModel->getID($CommentID);
         $Discussion = $DiscussionModel->getID(getValue('DiscussionID', $Comment));
      }
      if (!$Discussion)
         throw notFoundException('Discussion');

      $Category = CategoryModel::categories(getValue('CategoryID', $Discussion));
      $Sender->permission('Vanilla.Comments.Add', TRUE, 'Category', $Category['PermissionCategoryID']);
      $Sender->setData('Category', $Category);

      $Form = new Gdn_Form();
      $Sender->ReplyForm = $Form;
      $Form->setModel($Model);

      require_once $Sender->fetchViewLocation('reply_functions', '', 'plugins/Replies');
      $Sender->deliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->deliveryType(DELIVERY_TYPE_VIEW);

      if ($Form->authenticatedPostBack()) {
         $Container = '#'.repliesElementID($CommentID);

         $Form->setFormValue('CommentID', $CommentID);
         if ($ReplyID = $Form->save()) {
            $Reply = $Model->getID($ReplyID, DATASET_TYPE_ARRAY);

            ob_start();
            writeReply($Reply);
            $ReplyHtml = ob_get_clean();

            $Sender->jsonTarget("$Container .Item-ReplyForm", $ReplyHtml, 'Before');

            $Form->setFormValue('Body', '');
         }

         ob_start();
         writeReplyForm(['CommentID' => $CommentID]);
         $FormHtml = ob_get_clean();
         $Sender->jsonTarget("$Container .Item-ReplyForm", $FormHtml, 'ReplaceWith');
      } else {
         throw forbiddenException('GET');
      }

      $Sender->render('Blank', 'Utility', 'Dashboard');
   }
}

function replyRecordID($row) {
   $iD = getValue('CommentID', $row);
   if (!$iD)
      $iD = -getValue('DiscussionID', $row);
   return $iD;
}

function replyElementID($iD) {
   return 'Reply_'.$iD;
}

function repliesElementID($iD) {
   if (is_array($iD) || is_object($iD))
      $iD = replyRecordID($iD);

   return 'Replies_'.str_replace('-', 'd', $iD);
}

function getReplyOptions($reply) {
   static $permissions = null;
   if (!isset($permissions)) {
      $category = Gdn::controller()->data('Category');
      if ($category) {
         $permissions = [
               'Delete' => Gdn::session()->checkPermission('Vanilla.Comments.Delete', TRUE, 'Category', $category['PermissionCategoryID']),
               'Edit' => Gdn::session()->checkPermission('Vanilla.Comments.Edit', TRUE, 'Category', $category['PermissionCategoryID'])
            ];
      } else {
         $permissions = [
            'Delete' => FALSE,
            'Edit' => FALSE
            ];
      }
   }

   $iD = getValue('ReplyID', $reply);

   $result = [];

   if ($permissions['Edit']) {
      $result['EditReply'] = ['Label' => t('Edit'), 'Url' => "/discussion/editreply?replyid=$iD"];
   }
   if ($permissions['Delete'])
      $result['DeleteReply'] = ['Label' => t('Delete'), 'Url' => "/discussion/deletereply?replyid=$iD", 'Class' => 'Popup'];
   if ($permissions['Edit']) {
      $result['ReplyToComment'] = ['Label' => t('Make Comment'), 'Url' => "/discussion/replytocomment?replyid=$iD", 'Class' => 'Popup'];
   }


   return $result;
}
