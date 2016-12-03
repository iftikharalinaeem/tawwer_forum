<?php if (!defined('APPLICATION')) exit;

$PluginInfo['infstub'] = array(
    'Name'        => "Infrastructure Stub",
    'Description' => "Provides some basic mock infrastructure functionality to aid in localhost development.",
    'Version'     => '1.0.0-alpha',
    'Icon'        => 'internal-plugin.png',
    'Author'      => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'License'     => 'Proprietary'
);

/**
 * Infrastructure Stub Plugin
 *
 * @author    Todd Burry <todd@vanillaforums.com>
 * @copyright 2014 (c) Todd Burry
 * @license   Proprietary
 * @since     1.0.0
 */
class InfrastructureStubPlugin extends Gdn_Plugin {
    /**
     * This will run when you "Enable" the plugin
     *
     * @since  1.0.0
     * @access public
     * @return bool
     */
    public function setup() {
        return true;
    }
}
