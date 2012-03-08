<?php
class SuperHeroineForumsThemeHooks extends Gdn_Plugin {
    public function EntryController_Disclaimer_Create($Sender, $Args = array()) {
       if (GetValue('disclaimed', $_COOKIE))
          Redirect('/discussions');
       $Sender->Render(dirname(__FILE__).'/views/disclaimer.tpl');
    }

    public function DiscussionsController_Render_Before($Sender, $Args) {
       if (isset($_GET['disclaimed'])) {
          setcookie('disclaimed', $_GET['disclaimed']);
       }
    }

    public function Base_Render_Before($Sender, $Args) {
       $Names = array('banner1.jpg', 'banner2.jpg', 'banner3.jpg'); //, 'banner4.jpg', 'banner5.gif', 'banner6.gif');

       $Sender->SetData('BannerName', $Names[array_rand($Names)]);
    }
}
?>
