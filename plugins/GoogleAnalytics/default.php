<?php if (!defined('APPLICATION')) exit();

$PluginInfo['GoogleAnalytics'] = array(
   'Name' => 'Google Analytics',
   'Description' => 'Adds google analytics script to pages if related configuration options are set.',
   'Version' => '1.2.1',
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
      $Blacklist = C('Plugins.GoogleAnalytics.ControllerBlacklist', FALSE);
      if (!$Blacklist && strtolower($Sender->MasterView) == 'admin') {
         return;
      }
      if (is_array($Blacklist) && InArrayI($Sender->ControllerName, $Blacklist))
         return;
      
      $TrackerCode = C('Plugins.GoogleAnalytics.TrackerCode');
      $TrackerDomain = C('Plugins.GoogleAnalytics.TrackerDomain');
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
         
         $Extra = C('Plugins.GoogleAnalytics.Extra', NULL);
         if (!is_null($Extra)) {
            $Username = (Gdn::Session()->UserID) ? Gdn::Session()->User->Name : 'unknown';
            $TrackerParams = array(
                'Username'       => $Username,
                'IP'             => Gdn::Request()->GetValue('REMOTE_ADDR'),
                'UserID'         => Gdn::Session()->UserID
            );
            
            if (Gdn::Session()->UserID) {
               $TrackerParams = array_merge($TrackerParams,array(
                   'Email'       => Gdn::Session()->User->Email
               ));
            }
            $Extra = FormatString($Extra, $TrackerParams);
            
            $Script .= '
pageTracker._setVar("'.$Extra.'");';
         }
         
         $Script .= "
pageTracker._trackPageview();
} catch(err) {}</script>";

         $Sender->AddAsset('Foot', $Script);
      }
   }
   
   public function Setup() {
      // No setup required.
   }
}