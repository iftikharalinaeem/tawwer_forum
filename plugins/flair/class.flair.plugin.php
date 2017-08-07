<?php if (!defined('APPLICATION')) exit();

class FlairPlugin extends Gdn_Plugin {

   /**
    * @param AssetModel $sender
    */
   public function assetModel_styleCss_handler($sender, $args) {
      $sender->addCssFile('flair.css', 'plugins/flair');
   }

   /**
    *
    * @param DiscussionController $sender
    * @param type $args
    */
   public function base_authorInfo_handler($sender, $args) {
     if (!class_exists('UserBadgeModel')) {
       return;
     }

     $userID = getValue('UserID', $args['Author']);
     if ($userID) {
         $this->writeFlair($userID);
     }
   }

   /**
    * @param $user_id
    * @param int $limit
    */
   public function writeFlair($user_id, $limit = 4) {
      $flair_array = FlairModel::instance()->getId($user_id);

      if (empty($flair_array)) {
         return;
      }

      $html_flair = '<div class="flair">';

      $count = 0;
      foreach ($flair_array as $flair) {
         $html_flair .= '
         <span class="flair-item flair-'. $flair['slug'] .'" title="'. $flair['title'] .'">
            <img src="'. $flair['url'] .'" alt="'. $flair['title'] .'" />
         </span>';

         $count++;
         if ($count >= $limit) {
            break;
         }
      }

      $html_flair .= '</div>';

      echo $html_flair;
   }

   /**
   *
   * @param UserBadgeModel $sender
   * @param type $args
   */
   public function userBadgeModel_afterGive_handler($sender, $args) {
     // I'm not sure how to test if the event is getting fired.
     $user_id = getValueR('UserBagde.UserID', $args);
     FlairModel::instance()->clearCache($user_id);
   }

   public function discussionController_render_before($sender, $args) {
     if (class_exists('FlairModel')) {
       // Pre-fetch the flair for the comments.
       FlairModel::instance()->fetchUsers($sender->data('Comments'), 'InsertUserID');
     }
   }

   public function postController_editComment_render($sender, $args) {
     if (class_exists('FlairModel')) {
       // Pre-fetch the flair for the comments.
       FlairModel::instance()->fetchUsers($sender->data('Comments'), 'InsertUserID');
     }
   }
 }
