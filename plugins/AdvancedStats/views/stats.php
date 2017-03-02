<?php
$InstallationID = Gdn::InstallationID();
if (!$InstallationID) {
    $InstallationID = '3950-C089DA20-C9D34048';
}
?>
<div class="alert alert-danger padded">
    <?php echo t('The Advanced Stats addon is deprecated and will be removed on June 1, 2017.'
        .' Contact your customer success manager or <a href="mailto:support@vanillaforums.com">support@vanillaforums.com</a> to discuss possible replacements.') ?>
</div>
<div id="vanilla-comments" style="padding: 0;"></div>
<script type="text/javascript">
    var vanilla_forum_url = '<?php echo C('Garden.Analytics.Remote','//analytics.vanillaforums.com')?>';
    var vanilla_path = '/stats/remote/<?php echo $InstallationID; ?>';
    var vanilla_lazy_load = false;
    (function () {
        var vanilla = document.createElement('script');
        vanilla.type = 'text/javascript';
        var timestamp = new Date().getTime();
        vanilla.src = vanilla_forum_url + '/js/embed.js';
        (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(vanilla);
    })();
</script>
<noscript>Please enable JavaScript to view the <a href="http://vanillaforums.com/?ref_noscript">statistics powered by
        Vanilla.</a></noscript>
