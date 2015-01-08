<?php if (!defined('APPLICATION')) exit();

$PluginInfo['flare'] = array(
   'Name' => 'Flare',
   'Description' => 'Tie into Badges application.',
   'Version' => '1.1.1',
   'SettingsUrl' => '/dashboard/settings/flare',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Dane MacMillan",
   'AuthorEmail' => 'dane@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.org/profile/dane',
   'RequiredApplications' => array('Reputation' => '>=1.3.1')
);

class FlarePlugin extends Gdn_Plugin {

   /**
    * @param AssetModel $Sender
    */
   public function AssetModel_StyleCss_Handler($Sender, $Args) {
      $Sender->AddCssFile('flare.css', 'plugins/flare');
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
       $this->writeFlare($UserID);
     }
   }

   /**
    * @param $user_id
    * @param int $limit
    */
   public function writeFlare($user_id, $limit = 4) {
      $flare_array = FlareModel::instance()->getId($user_id);

      if (empty($flare_array)) {
         return;
      }

      $html_flare = '<div class="flare">';

      $count = 0;
      foreach ($flare_array as $flare) {
         $html_flare .= '
         <span class="flare-item flare-'. $flare['slug'] .'" title="'. $flare['title'] .'">
            <img src="'. $flare['url'] .'" alt="'. $flare['title'] .'" />
         </span>';

         $count++;
         if ($count >= $limit) {
            break;
         }
      }

      $html_flare .= '</div>';

      echo $html_flare;
   }

   /**
   *
   * @param UserBadgeModel $Sender
   * @param type $Args
   */
   public function UserBadgeModel_AfterGive_Handler($Sender, $Args) {
     // I'm not sure how to test if the event is getting fired.
     $user_id = GetValueR('UserBagde.UserID', $Args);
     FlareModel::instance()->clearCache($user_id);
   }

   public function DiscussionController_Render_Before($Sender, $Args) {
     if (class_exists('FlareModel')) {
       // Pre-fetch the flare for the comments.
       FlareModel::instance()->fetchUsers($Sender->Data('Comments'), 'InsertUserID');
     }
   }

   public function PostController_EditComment_Render($Sender, $Args) {
     if (class_exists('FlareModel')) {
       // Pre-fetch the flare for the comments.
       FlareModel::instance()->fetchUsers($Sender->Data('Comments'), 'InsertUserID');
     }
   }
 }
