<?php

/**
 * @copyright 2010-2014 Vanilla Forums Inc
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Mustache'] = array(
    'Name' => 'Mustache view renderer',
    'Description' => "This plugin adds Mustache rendering ability to Vanilla.",
    'Version' => '1.0a',
    'MobileFriendly' => true,
    'RequiredApplications' => false,
    'RequiredTheme' => false,
    'RequiredPlugins' => false,
    'HasLocale' => false,
    'RegisterPermissions' => false,
    'Author' => "Tim Gunter",
    'AuthorEmail' => 'tim@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
);

/**
 * Mustache Renderer
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package internal
 * @subpackage Mustache
 * @since 1.0
 */
class MustachePlugin extends Gdn_Plugin {

    public function gdn_dispatcher_appStartup_handler($sender) {
        // Mustache Templating Engine and Handler
        Gdn::factoryInstall('Mustache_Engine', $this->getResource('vendors/mustache/class.mustache_engine.php'));
        Gdn::factoryInstall('ViewHandler.mustache', 'MustacheHandler');
    }

}
