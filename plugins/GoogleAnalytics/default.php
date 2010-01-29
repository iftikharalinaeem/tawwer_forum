<?php if (!defined('APPLICATION')) exit();

$PluginInfo['GoogleAnalytics'] = array(
   'Name' => 'Google Analytics',
   'Description' => 'Adds google analytics script to pages if related configuration options are set.',
   'Version' => '1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class GoogleAnalyticsPlugin implements Gdn_IPlugin {

   /**
    * Includes Google Analytics on all pages if the conf file contains
    * Plugins.GoogleAnalytics.TrackerCode and
    * Plugins.GoogleAnalytics.TrackerDomain.
    */
   public function Base_Render_Before(&$Sender) {
      $Blacklist = Gdn::Config('Plugins.GoogleAnalytics.ControllerBlacklist', array());
      if (is_array($Blacklist) && InArrayI($Sender->ControllerName, $Blacklist))
         return;
      
      $TrackerCode = Gdn::Config('Plugins.GoogleAnalytics.TrackerCode');
      $TrackerDomain = Gdn::Config('Plugins.GoogleAnalytics.TrackerDomain');
      if ($TrackerCode && $TrackerCode != '' && $Sender->DeliveryType() == DELIVERY_TYPE_ALL) {
         $Script = "<script type=\"text/javascript\">
var gaJsHost = ((\"https:\" == document.location.protocol) ? \"https://ssl.\" : \"http://www.\");
document.write(unescape(\"%3Cscript src='\" + gaJsHost + \"google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E\"));
</script>
<script type=\"text/javascript\">
try {
var pageTracker = _gat._getTracker(\"".$TrackerCode."\");";
         if ($TrackerDomain)
            $Script .= '
pageTracker._setDomainName("'.$TrackerDomain.'");';
         
         $Script .= "
pageTracker._trackPageview();
} catch(err) {}</script>";

         $Sender->AddAsset('Content', $Script);
      }
   }
   
   public function Setup() {
      // No setup required.
   }
}