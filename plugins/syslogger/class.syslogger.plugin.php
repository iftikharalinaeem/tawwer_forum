<?php if (!defined('APPLICATION')) exit;

$PluginInfo['syslogger'] = array(
    'Name'        => "Syslogger",
    'Description' => "Logs events from the Logger object to the syslog.",
    'Version'     => '1.1.0',
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
    private $level;

    /**
     * Initialize a new instance of the {@link SysloggerPlugin} class.
     */
    public function __construct() {
        parent::__construct();
        $this->setLevel(c('Plugins.Syslogger.Level', Logger::INFO));
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

        Logger::addLogger($logger, $this->getLevel());

    }

    /**
     * Get the level.
     *
     * @return mixed Returns the level.
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * Set the level.
     *
     * @param mixed $level
     * @return SysloggerPlugin Returns `$this` for fluent calls.
     */
    public function setLevel($level) {
        $this->level = $level;
        return $this;
    }
}
