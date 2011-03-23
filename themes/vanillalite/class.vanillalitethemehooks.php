<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

class VanillaLiteThemeHooks implements Gdn_IPlugin {
   
   public function Setup() {
      return TRUE;
   }

   public function OnDisable() {
      return TRUE;
   }
   
   /**
    * Add mobile meta info. Add script to hide iphone browser bar on pageload.
    */
   public function Base_Render_Before($Sender) {
      if (IsMobile() && is_object($Sender->Head)) {
         $Sender->Head->AddTag('meta', array('name' => 'viewport', 'content' => "width=device-width,minimum-scale=1.0,maximum-scale=1.0"));
         
         $Sender->Head->AddString('<script type="text/javascript">
// If not looking for a specific comment, hide the address bar in iphone
var hash = window.location.href.split("#")[1];
if (typeof(hash) == "undefined") {
   setTimeout(function () {
     window.scrollTo(0, 1);
   }, 1000);
}
</script>');
      }
   }
}