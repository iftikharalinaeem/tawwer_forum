<?php if (!defined('APPLICATION')) exit();

$PluginInfo['FoodNetwork'] = array(
   'Name' => 'Food Network',
   'Description' => 'A custom plugin to add tracking code to a foodnetwork site.',
   'Version' => '1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class FoodNetworkPlugin implements Gdn_IPlugin {

   public function Base_Render_Before(&$Sender) {
      $Blacklist = Gdn::Config('Plugins.GoogleAnalytics.ControllerBlacklist', array());
      if (is_array($Blacklist) && InArrayI($Sender->ControllerName, $Blacklist))
         return;
      
      if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL) {
         $Script = "<!-- SiteCatalyst code version: H.20.3.
Copyright 1996-2009 Adobe, Inc. All Rights Reserved
More info available at http://www.omniture.com -->
<script language=\"JavaScript\" type=\"text/javascript\" src=\"/plugins/FoodNetwork/s_code_ums.js\"></script>
<script language=\"JavaScript\" type=\"text/javascript\"><!--
/* You may give each page an identifying name, server, and channel on
the next lines. */
s.pageName=window.location.href;
s.channel=\"Forums\";
s.prop6=\"\";
s.prop6=document.title;
/* Conversion Variables */
s.events=\"\"
/************* DO NOT ALTER ANYTHING BELOW THIS LINE ! **************/
var s_code=s.t();if(s_code)document.write(s_code)//--></script>
<script language=\"JavaScript\" type=\"text/javascript\"><!--
if(navigator.appVersion.indexOf('MSIE')>=0)document.write(unescape('%3C')+'\!-'+'-')
//--></script><noscript><a href=\"http://www.omniture.com\" title=\"Web Analytics\"><img
src=\"http://ewscripps.112.2o7.net/b/ss/scrippsupmystreet/1/H.20.3--NS/0\"
height=\"1\" width=\"1\" border=\"0\" alt=\"\" /></a></noscript><!--/DO NOT REMOVE/-->
<!-- End SiteCatalyst code version: H.20.3. -->";

         $Sender->AddAsset('Content', $Script);
      }
   }
   
   public function Setup() {
      // No setup required.
   }
}