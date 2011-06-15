<?php if (!defined('APPLICATION')) exit();

// Cache
$Configuration['Cache']['Enabled'] = TRUE;
$Configuration['Cache']['Method'] = 'memcached';
$Configuration['Cache']['Memcached']['Store'] = array('cache-web-01:11211');
$Configuration['Cache']['Memcached']['Option'][Memcached::OPT_COMPRESSION] = FALSE;
$Configuration['Cache']['Memcached']['Option'][Memcached::OPT_DISTRIBUTION] = Memcached::DISTRIBUTION_CONSISTENT;
$Configuration['Cache']['Memcached']['Option'][Memcached::OPT_LIBKETAMA_COMPATIBLE] = TRUE;

// Conversations
$Configuration['Conversations']['Version'] = '1.0';

// Database
$Configuration['Database']['ConnectionOptions']['12'] = FALSE;
$Configuration['Database']['ConnectionOptions']['1000'] = TRUE;
$Configuration['Database']['ConnectionOptions']['1002'] = "set names 'utf8'";

// EnabledApplications
$Configuration['EnabledApplications']['Vanilla'] = 'vanilla';
$Configuration['EnabledApplications']['Conversations'] = 'conversations';

// EnabledPlugins
$Configuration['EnabledPlugins']['HtmLawed'] = TRUE;
$Configuration['EnabledPlugins']['Memcache'] = TRUE;
$Configuration['EnabledPlugins']['vfoptions'] = TRUE;
$Configuration['EnabledPlugins']['vfcom'] = TRUE;
$Configuration['EnabledPlugins']['vfspoof'] = TRUE;

// Garden
$Configuration['Garden']['Installed'] = TRUE;
$Configuration['Garden']['RewriteUrls'] = TRUE;
$Configuration['Garden']['Email']['SmtpHost'] = 'smtp.sendgrid.net';
$Configuration['Garden']['Email']['SmtpUser'] = 'mark@vanillaforums.com';
$Configuration['Garden']['Email']['SmtpPassword'] = 'navwantsin';
$Configuration['Garden']['Email']['SmtpPort'] = '25';
$Configuration['Garden']['Analytics']['AllowLocal'] = TRUE;

// Routes
$Configuration['Routes']['DefaultController'] = 'discussions';

// Vanilla
$Configuration['Vanilla']['Version'] = '2.0';
$Configuration['Vanilla']['Views']['Denormalize'] = TRUE;

// VanillaForums
$Configuration['VanillaForums']['Hostname'] = 'vanillaforums.com';
$Configuration['VanillaForums']['Database']['Host'] = 'vfdb1.vanillaforums.com';
$Configuration['VanillaForums']['Database']['User'] = 'vfcom';
$Configuration['VanillaForums']['Database']['Password'] = 'DQeYO4RohD';
$Configuration['VanillaForums']['Database']['Name'] = 'vfcom';
$Configuration['VanillaForums']['Frontend'] = '{frontend server}';

// Last edited by Tim

