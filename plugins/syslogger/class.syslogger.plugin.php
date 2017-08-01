<?php if (!defined('APPLICATION')) exit;

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
    public function gdn_Dispatcher_AppStartup_Handler($sender) {
        if (class_exists('Infrastructure')) {
            $extra = [
                'accountid' => Infrastructure::site('accountid'),
                'siteid' => Infrastructure::siteID()
                ];
            $ident = Infrastructure::siteID();
        } else {
            $ident = Gdn::request()->host();
            $extra = [];
        }

        $logger = new Syslogger(c('Plugins.Syslogger.MessageFormat', 'json'), $ident);
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
