<?php if (!defined('APPLICATION')) exit;

$PluginInfo['sitenode'] = array(
    'Name'        => "Multisite Node",
    'Version'     => '1.0.0-alpha',
    'Author'      => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl'   => 'http://vanillaforums.com',
    'License'     => 'Proprietary'
);

/**
 * Multisite Node Plugin
 *
 * @author    Todd Burry <todd@vanillaforums.com>
 * @copyright 2014 (c) Todd Burry
 * @license   Proprietary
 * @since     1.0.0
 */
class SiteNodePlugin extends Gdn_Plugin {
    /// CONSTANTS ///

    /// PROPERTIES ///

    /**
     * This will run when you "Enable" the plugin
     *
     * @since  1.0.0
     * @return bool
     */
    public function setup() {
        $this->structure();
    }

    public function structure() {
    }

    /// METHODS ///

    /// EVENT HANDLERS ///
}
