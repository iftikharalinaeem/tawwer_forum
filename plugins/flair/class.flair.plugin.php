<?php if (!defined('APPLICATION')) exit();

$PluginInfo['flair'] = array(
   'Name' => 'Flair',
   'Description' => 'Tie into Badges application.',
   'Version' => '1.1.1',
   'SettingsUrl' => '/dashboard/settings/flair',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Dane MacMillan",
   'AuthorEmail' => 'dane@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.org/profile/dane',
   'MobileFriendly' => true,
   'RequiredApplications' => array('badges' => '>=1.3.1')
);

class FlairPlugin extends Gdn_Plugin {

   /**
    * @param AssetModel $Sender
    */
   public function AssetModel_StyleCss_Handler($Sender, $Args) {
      $Sender->AddCssFile('flair.css', 'plugins/flair');
   }

   /**
    *
    * @param DiscussionController $Sender
    * @param type $Args
    */
   public function Base_AuthorInfo_Handler($Sender, $Args) {
     if (!class_exists('UserBadgeModel')) {
       return;
     }

     $UserID = GetValue('UserID', $Args['Author']);
     if ($UserID) {
         $this->writeFlair($UserID);
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
   * @param UserBadgeModel $Sender
   * @param type $Args
   */
   public function UserBadgeModel_AfterGive_Handler($Sender, $Args) {
     // I'm not sure how to test if the event is getting fired.
     $user_id = GetValueR('UserBagde.UserID', $Args);
     FlairModel::instance()->clearCache($user_id);
   }

   public function DiscussionController_Render_Before($Sender, $Args) {
     if (class_exists('FlairModel')) {
       // Pre-fetch the flair for the comments.
       FlairModel::instance()->fetchUsers($Sender->Data('Comments'), 'InsertUserID');
     }
   }

   public function PostController_EditComment_Render($Sender, $Args) {
     if (class_exists('FlairModel')) {
       // Pre-fetch the flair for the comments.
       FlairModel::instance()->fetchUsers($Sender->Data('Comments'), 'InsertUserID');
     }
   }
 }
