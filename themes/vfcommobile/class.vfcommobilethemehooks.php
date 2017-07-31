<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class VFComMobileThemeHooks implements Gdn_IPlugin {
   
   public function Setup() {
      return TRUE;
   }

   public function OnDisable() {
      return TRUE;
   }
   
   /**
    * Remove plugins that are not mobile friendly!
    */
   public function Gdn_Dispatcher_AfterAnalyzeRequest_Handler($sender) {
      // Remove plugins so they don't mess up layout or functionality.
      if (in_array($sender->Application(), ['vanilla', 'conversations']) || ($sender->Application() == 'dashboard' && in_array($sender->Controller(), ['Activity', 'Profile', 'Search']))) {
         Gdn::PluginManager()->RemoveMobileUnfriendlyPlugins();
      }
      SaveToConfig('Garden.Format.EmbedSize', '240x135', FALSE);
   }
   
   /**
    * Add mobile meta info. Add script to hide iphone browser bar on pageload.
    */
   public function Base_Render_Before($sender) {
      if (IsMobile() && is_object($sender->Head)) {
         $sender->Head->AddTag('meta', ['name' => 'viewport', 'content' => "width=device-width,minimum-scale=1.0,maximum-scale=1.0"]);
         
         $sender->Head->AddString('<script type="text/javascript">
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