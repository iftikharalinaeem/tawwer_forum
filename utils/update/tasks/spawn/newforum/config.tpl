<?php if (!defined('APPLICATION')) exit();

// Conversations
$Configuration['Conversations']['Version'] = '1.0';

// Database
$Configuration['Database']['Host'] = '{database host}';
$Configuration['Database']['Name'] = '{database name}';
$Configuration['Database']['User'] = '{database user}';
$Configuration['Database']['Password'] = '{database password}';

// EnabledApplications
$Configuration['EnabledApplications']['Vanilla'] = 'vanilla';
$Configuration['EnabledApplications']['Conversations'] = 'conversations';

// Garden
$Configuration['Garden']['Title'] = '{site title}';
$Configuration['Garden']['Domain'] = '{site domain}';
$Configuration['Garden']['Cookie']['Salt'] = '{cookie salt}';
$Configuration['Garden']['Cookie']['Name'] = '{cookie name}';
$Configuration['Garden']['Cookie']['Domain'] = '{cookie domain}';
$Configuration['Garden']['Version'] = '1.0';
$Configuration['Garden']['RewriteUrls'] = TRUE;
$Configuration['Garden']['CanProcessImages'] = TRUE;
$Configuration['Garden']['Installed'] = NULL;
$Configuration['Garden']['Registration']['Method'] = 'Captcha';
$Configuration['Garden']['Registration']['DefaultRoles'] = 'arr:["8"]';
$Configuration['Garden']['Registration']['CaptchaPublicKey'] = '6LeepgcAAAAAAENrbwFboDCnuWqO9YCVcV-i0hfc';
$Configuration['Garden']['Registration']['CaptchaPrivateKey'] = '6LeepgcAAAAAAAgImitJlLrfVWAOEgQHf5tgaumz';
$Configuration['Garden']['Registration']['InviteExpiration'] = '-1 week';
$Configuration['Garden']['Registration']['InviteRoles'] = 'arr:{"8":"0","16":"0"}';
$Configuration['Garden']['UpdateCheckUrl'] = 'http://vanillaforums.org/addons/update';
$Configuration['Garden']['Email']['SupportAddress'] = 'support@vanillaforums.com';
$Configuration['Garden']['Email']['SupportName'] = 'Vanilla Support';
$Configuration['Garden']['Email']['UseSmtp'] = TRUE;
$Configuration['Garden']['Email']['SmtpHost'] = 'smtp.sendgrid.net';
$Configuration['Garden']['Email']['SmtpUser'] = 'mark@vanillaforums.com';
$Configuration['Garden']['Email']['SmtpPassword'] = 'navwantsin';
$Configuration['Garden']['Email']['SmtpPort'] = '25';
$Configuration['Garden']['Theme'] = 'default';
$Configuration['Garden']['Roles']['Manage'] = FALSE;
$Configuration['Garden']['Registration']['Manage'] = FALSE;
$Configuration['Garden']['VanillaUrl'] = 'http://vanillaforums.com';
$Configuration['Garden']['Errors']['LogEnabled'] = TRUE;
$Configuration['Garden']['Analytics']['AllowLocal'] = TRUE;

// Routes
$Configuration['Routes']['DefaultController'] = 'discussions';

// Vanilla
$Configuration['Vanilla']['Version'] = '2.0';

// VanillaForums.com
$Configuration['VanillaForums']['UserID'] = '{vanilla userid}';
$Configuration['VanillaForums']['SiteID'] = '{vanilla siteid}';
$Configuration['VanillaForums']['AccountID'] = '{vanilla accountid}';

$Configuration['VanillaForums']['Database']['Host'] = 'vfdb1.vanillaforums.com';
$Configuration['VanillaForums']['Database']['User'] = 'frontend';
$Configuration['VanillaForums']['Database']['Password'] = 'Va2aWu5A';
$Configuration['VanillaForums']['Database']['Name'] = 'vfcom';

// Plugins
$Configuration['Plugins']['GoogleAnalytics']['TrackerCode'] = 'UA-12713112-1';
$Configuration['Plugins']['GoogleAnalytics']['TrackerDomain'] = '.vanillaforums.com';
