<?php if (!defined('APPLICATION')) exit();

$PluginInfo['flare'] = array(
   'Name' => 'Flare',
   'Description' => 'Tie into Badges application.',
   'Version' => '1.0.0',
   'SettingsUrl' => '/dashboard/settings/flare',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Dane MacMillan",
   'AuthorEmail' => 'dane@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.org/profile/dane'
);

class FlarePlugin extends Gdn_Plugin {
}

if (!function_exists('writeFlare')) {
   /**
    * Write out the flare HTML.
    */
   function writeFlare($user_id, $limit = 4) {
      $flare_array = FlareModel::instance()->getId($user_id);
      
      if (empty($flare_array)) {
         return;
      }
      
      $html_flare = '<div class="flare">';

      $count = 0;
      foreach ($flare_array as $flare) {
         $html_flare .= '
         <span class="flare-item flare-'. $flare['slug'] .'" title="'. $flare['title'] .'">
            <img src="'. $flare['url'] .'" alt="'. $flare['slug'] .'" width="25" height="25" />
         </span>';
         
         $count++;
         if ($count >= $limit) {
            break;
         }
      }

      $html_flare .= '</div>';
      
      echo $html_flare;
   }
}