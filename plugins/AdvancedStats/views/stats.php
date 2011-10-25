<?php if (!defined('APPLICATION')) exit();
$InstallationID = Gdn::InstallationID();
if (!$InstallationID)
	$InstallationID = '3950-C089DA20-C9D34048';
?>
<div id="vanilla-comments" class="Loading" style="padding: 0;"></div>
<script type="text/javascript">
var vanilla_forum_url = '<?php echo C('Garden.Analytics.Remote','http://analytics.vanillaforums.com')?>';
var vanilla_path = 'stats/remote/<?php echo $InstallationID; ?>';
(function() {
   var vanilla = document.createElement('script');
   vanilla.type = 'text/javascript';
   var timestamp = new Date().getTime();
   vanilla.src = vanilla_forum_url + '/js/embed.js';
   (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(vanilla);
})();
</script>
<noscript>Please enable JavaScript to view the <a href="http://vanillaforums.com/?ref_noscript">statistics powered by Vanilla.</a></noscript>
