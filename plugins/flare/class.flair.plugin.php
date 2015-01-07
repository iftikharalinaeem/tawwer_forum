<?php if (!defined('APPLICATION')) exit();

$PluginInfo['flair'] = array(
   'Name' => 'Flair',
   'Description' => 'Tie into Badges application.',
   'Version' => '1.1.0',
   'SettingsUrl' => '/dashboard/settings/flair',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Dane MacMillan",
   'AuthorEmail' => 'dane@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.org/profile/dane'
);

class FlairPlugin extends Gdn_Plugin {

   function __construct(){
      if (!class_exists('UserBadgeModel')){
         trigger_error('Flair plugin depends on the badges application to work. Please enable badges plugin.');
      }
   }


   /**
    * @param AssetModel $Sender
    */
   public function AssetModel_StyleCss_Handler($Sender, $Args) {
      $Sender->AddCssFile('flair.css', 'plugins/flare');
   }

   /**
    *
    * @param DiscussionController $Sender
    * @param type $Args
    */
   public function Base_AuthorInfo_Handler($Sender, $Args) {
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
}
