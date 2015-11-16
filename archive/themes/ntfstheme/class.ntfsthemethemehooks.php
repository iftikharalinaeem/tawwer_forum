<?php if (!defined('APPLICATION')) exit();

class ntfsthemeThemeHooks implements Gdn_IPlugin {

   /**
    *
    * @param Gdn_Controller $Sender
    */
   public function Base_Render_Before($Sender) {
      $Html = <<<EOT
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-202618-5']);
  _gaq.push(['_setDomainName', '.ntfs.com']);
  _gaq.push(['_trackPageview']);
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
EOT;
      $Sender->AddAsset('Content', $Html, 'GoogleAnalytics');
   }

   public function OnDisable() {
   }

   public function Setup() {
   }
}