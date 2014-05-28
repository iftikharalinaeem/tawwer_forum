<?php if (!defined('APPLICATION')) exit;

$PluginInfo['syslogger'] = array(
    'Name'        => "Syslogger",
    'Description' => "Logs events from the Logger object to the syslog.",
    'Version'     => '1.0.0-beta',
    'Author'      => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl'   => 'http://vanillaforums.com',
    'License'     => 'Proprietary'
);

/**
 * Syslogger Plugin
 *
 * @author    Todd Burry <todd@vanillaforums.com>
 * @copyright 2014 (c) Todd Burry
 * @license   Proprietary
 * @package   Syslogger
 * @since     1.0.0
 */
class SysloggerPlugin extends Gdn_Plugin {
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
     * Install the syslogger as early as possible.
     *
     * @param Gdn_Dispatcher $sender
     */
    public function Gdn_Dispatcher_AppStartup_Handler($sender) {
        if (class_exists('Infrastructure')) {
            $extra = [
                'accountid' => Infrastructure::site('accountid'),
                'siteid' => Infrastructure::siteID()
                ];
            $ident = Infrastructure::siteID();
        } else {
            $ident = Gdn::Request()->Host();
            $extra = [];
        }

        $logger = new Syslogger(C('Plugins.Syslogger.MessageFormat', 'json'), $ident);
        $logger->extra = $extra;

        Logger::setLogger($logger);

    }
}
