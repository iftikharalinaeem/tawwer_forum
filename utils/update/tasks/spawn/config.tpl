<?php if (!defined('APPLICATION')) exit();

// Conversations
$Configuration['Conversations']['Version'] = '1.0';

// Database
$Configuration['Database']['Name'] = '%1$s';
$Configuration['Database']['Host'] = '%12$s';
$Configuration['Database']['User'] = '%13$s';
$Configuration['Database']['Password'] = '%2$s';

// EnabledApplications
$Configuration['EnabledApplications']['Vanilla'] = 'vanilla';
$Configuration['EnabledApplications']['Conversations'] = 'conversations';

// EnabledPlugins
$Configuration['EnabledPlugins']['HtmLawed'] = 'HtmLawed';
$Configuration['EnabledPlugins']['GettingStartedHosting'] = 'GettingStartedHosting';
$Configuration['EnabledPlugins']['CustomTheme'] = 'CustomTheme';
$Configuration['EnabledPlugins']['Gravatar'] = 'Gravatar';
$Configuration['EnabledPlugins']['embedvanilla'] = 'embedvanilla';
$Configuration['EnabledPlugins']['vfoptions'] = 'vfoptions';

// Garden
$Configuration['Garden']['Errors']['LogEnabled'] = TRUE;
$Configuration['Garden']['Title'] = '%3$s';
$Configuration['Garden']['Cookie']['Salt'] = '%4$s';
$Configuration['Garden']['Cookie']['Name'] = '%5$s';
$Configuration['Garden']['Cookie']['Domain'] = '%6$s';
$Configuration['Garden']['Version'] = '1.0';
$Configuration['Garden']['WebRoot'] = '';
$Configuration['Garden']['RewriteUrls'] = TRUE;
$Configuration['Garden']['Domain'] = '%7$s';
$Configuration['Garden']['CanProcessImages'] = TRUE;
$Configuration['Garden']['Installed'] = TRUE;
$Configuration['Garden']['Registration']['Method'] = 'Captcha';
$Configuration['Garden']['Registration']['DefaultRoles'] = 'arr:["8"]';
$Configuration['Garden']['Registration']['CaptchaPublicKey'] = '6LeepgcAAAAAAENrbwFboDCnuWqO9YCVcV-i0hfc';
$Configuration['Garden']['Registration']['CaptchaPrivateKey'] = '6LeepgcAAAAAAAgImitJlLrfVWAOEgQHf5tgaumz';
$Configuration['Garden']['Registration']['InviteExpiration'] = '-1 week';
$Configuration['Garden']['Registration']['InviteRoles'] = 'arr:{"8":"0","16":"0"}';
$Configuration['Garden']['UpdateCheckUrl'] = 'http://vanillaforums.org/addons/update';
$Configuration['Garden']['Email']['SupportAddress'] = '%8$s';
$Configuration['Garden']['Email']['UseSmtp'] = TRUE;
$Configuration['Garden']['Email']['SmtpHost'] = 'smtp.sendgrid.net';
$Configuration['Garden']['Email']['SmtpUser'] = 'mark@vanillaforums.com';
$Configuration['Garden']['Email']['SmtpPassword'] = 'navwantsin';
$Configuration['Garden']['Email']['SmtpPort'] = '25';
$Configuration['Garden']['Theme'] = 'default';
$Configuration['Garden']['Roles']['Manage'] = FALSE;
$Configuration['Garden']['Registration']['Manage'] = FALSE;
$Configuration['Garden']['VanillaUrl'] = 'http://vanillaforums.com';
$Configuration['Garden']['Errors']['MasterView'] = 'error.master.php';

// Routes
$Configuration['Routes']['DefaultController'] = 'discussions';

// Vanilla
$Configuration['Vanilla']['Version'] = '2.0';

// VanillaForums.com
$Configuration['VanillaForums']['UserID'] = '%9$s';
$Configuration['VanillaForums']['SiteID'] = '%10$s';
$Configuration['VanillaForums']['AccountID'] = '%11$s';

// Plugins
$Configuration['Plugins']['GoogleAnalytics']['TrackerCode'] = 'UA-12713112-1';
$Configuration['Plugins']['GoogleAnalytics']['TrackerDomain'] = '.vanillaforums.com';
$Configuration['EnabledPlugins']['GoogleAdSense'] = 'googleadsense';
$Configuration['Plugins']['GoogleAdSense']['Content']['Controllers'] = array('discussionscontroller', 'discussioncontroller', 'activitycontroller', 'messagescontroller', 'profilecontroller', 'draftscontroller');
$Configuration['Plugins']['GoogleAdSense']['Content']['Html'] = '<script type="text/javascript"><!--
google_ad_client = "pub-9700666457266907";
/* 468x60, created 8/3/09 */
google_ad_slot = "1646632888";
google_ad_width = 468;
google_ad_height = 60;
//-->
</script>
<script type="text/javascript"
src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>';
$Configuration['Plugins']['GoogleAdSense']['Panel']['Controllers'] = array('discussionscontroller', 'discussioncontroller', 'activitycontroller', 'messagescontroller', 'draftscontroller');
$Configuration['Plugins']['GoogleAdSense']['Panel']['Html'] = '<script type="text/javascript"><!--
google_ad_client = "pub-9700666457266907";
/* 250x250, created 8/3/09 */
google_ad_slot = "6655417889";
google_ad_width = 250;
google_ad_height = 250;
//-->
</script>
<script type="text/javascript"
src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>';
