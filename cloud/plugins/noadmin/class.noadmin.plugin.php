<?php if (!defined('APPLICATION')) exit;

/**
 * noadmin Plugin
 *
 * @author    Todd Burry <todd@vanillaforums.com>
 * @copyright 2015 (c) Todd Burry
 * @license   Proprietary
 * @package   noadmin
 * @since     1.0.0
 */
class NoadminPlugin extends Gdn_Plugin {
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

    /**
     * Disable all admin level permissions.
     *
     * @param Gdn_Dispatcher $sender
     */
    public function gdn_dispatcher_appStartup_handler($sender) {
        if (!Gdn::session()->User) {
            return;
        }

        $session = Gdn::session();
        setValue('Admin', $session->User, 0);
        $session->setPermission('Garden.Settings.Manage', false);
        $session->setPermission('Garden.Community.Manage', false);
        $session->setPermission('Garden.Moderation.Manage', false);
        $session->setPermission('Plugins.Pockets.Manage', false);
        $session->setPermission('Garden.Users.Add', false);
        $session->setPermission('Garden.Users.Edit', false);
        $session->setPermission('Garden.Users.Delete', false);
    }
}