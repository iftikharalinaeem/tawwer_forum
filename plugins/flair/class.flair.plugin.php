<?php if (!defined('APPLICATION')) exit();

class FlairPlugin extends Gdn_Plugin {

   /**
    * @param AssetModel $sender
    */
   public function AssetModel_StyleCss_Handler($sender, $args) {
      $sender->AddCssFile('flair.css', 'plugins/flair');
   }

   /**
    *
    * @param DiscussionController $sender
    * @param type $args
    */
   public function Base_AuthorInfo_Handler($sender, $args) {
     if (!class_exists('UserBadgeModel')) {
       return;
     }

     $userID = GetValue('UserID', $args['Author']);
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
   public function UserBadgeModel_AfterGive_Handler($sender, $args) {
     // I'm not sure how to test if the event is getting fired.
     $user_id = GetValueR('UserBagde.UserID', $args);
     FlairModel::instance()->clearCache($user_id);
   }

   public function DiscussionController_Render_Before($sender, $args) {
     if (class_exists('FlairModel')) {
       // Pre-fetch the flair for the comments.
       FlairModel::instance()->fetchUsers($sender->Data('Comments'), 'InsertUserID');
     }
   }

   public function PostController_EditComment_Render($sender, $args) {
     if (class_exists('FlairModel')) {
       // Pre-fetch the flair for the comments.
       FlairModel::instance()->fetchUsers($sender->Data('Comments'), 'InsertUserID');
     }
   }
 }
