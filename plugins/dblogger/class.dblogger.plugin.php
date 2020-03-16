<?php

use Garden\Container\Container;
use Vanilla\Logger;
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
class DbLoggerPlugin extends Gdn_Plugin {
    private $level;

    /**
     * Initialize a new instance of the {@link DbLoggerPlugin} class.
     */
    public function __construct() {
        parent::__construct();

        $this->setLevel(c('Plugins.dblogger.Level', Logger::INFO));
    }

    public $severityOptions = [
        'info' => true,
        'notice' => true,
        'warning' => true,
        'error' => true,
    ];

    public function setup() {
        $this->structure();
    }

    public function structure() {
        Gdn::structure()->table('EventLog');
        if (Gdn::structure()->columnExists('EventLogID')) {
            Gdn::structure()->drop();
        }

        Gdn::structure()->table('EventLog')
            ->column('ID', 'varchar(16)', false, 'primary')
            ->column('Timestamp', 'uint', true, 'index')
            ->column('Event', 'varchar(50)', true, 'index')
            ->column('Level', 'tinyint', true, 'index')
            ->column('Message', 'text')
            ->column('Method', 'varchar(10)', true)
            ->column('Domain', 'varchar(255)', true)
            ->column('Path', 'varchar(255)', true)
            ->column('UserID', 'int', true)
            ->column('Username', 'varchar(50)', true)
            ->column('IP', 'ipaddress', true)
            ->column('Attributes', 'text', true)
            ->set();
    }

    /**
     * @param DashboardNavModule $sender
     */
    public function dashboardNavModule_init_handler($sender) {
        $sender->addLinkIf('Garden.Settings.Manage', t('Event Log'), '/settings/eventlog', 'site-settings.event-log');
    }

    /**
     * Install the database logger as early as possible.
     *
     * @param Container $dic
     */
    public function container_init(Container $dic) {
        $dbLogger = new DbLogger();

        try {
            $dbLogger->setPruneAfter(c('Plugins.dblogger.PruneAfter', '-90 days'));
        } catch (InvalidArgumentException $e) {
            // Do nothing on an invalid date. Just don't set it.
        }

        /** @var Logger */
        $vanillaLogger = $dic->get(Logger::class);
        $vanillaLogger->addLogger($dbLogger, $this->getLevel());
    }

    /**
     * @param SettingsController $sender
     * @param string $page
     */
    public function settingsController_eventLog_create($sender, $page = '') {
        $sender->permission('Garden.Settings.Manage');

        $sender->Form = new Gdn_Form();
        $pageSize = 30;
        list($offset, $limit) = offsetLimit($page, $pageSize);
        saveToConfig('Api.Clean', false, false);
        $sql = Gdn::sql();

        $sql->from('EventLog')
            ->limit($limit + 1, $offset);

        $get = array_change_key_case($sender->Request->get());

        // Look for query parameters to filter the data.
        // todo: use timezone? on dateto and datefrom
        if ($v = val('datefrom', $get)) {
            $v = strtotime($v);
            if (!$v) {
                $sender->Form->addError('Invalid Date format for From Date.');
            }
            $sql->where('Timestamp >=', $v);
            $sender->Form->setFormValue('datefrom', $get['datefrom']);
        }

        if ($v = val('dateto', $get)) {
            $v = strtotime($v);
            if (!$v) {
                $sender->Form->addError('Invalid Date format for To Date.');
            }
            $sql->where('Timestamp <=', $v);
            $sender->Form->setFormValue('dateto', $get['dateto']);
        }

        $sender->Form->setFormValue('severity', 'all');
        if (($v = val('severity', $get)) && $v != 'all') {
            $validLevelString = implode(', ', array_keys(array_slice($this->severityOptions, 0, -1)));
            $validLevelString .= ' and ' . end(array_keys($this->severityOptions));

            if (!isset($this->severityOptions[$v])) {
                $sender->Form->addError('Invalid severity.  Valid options are: ' . $validLevelString);
            }
            $sql->where('Level =', Logger::levelPriority($v));
            $sender->Form->setFormValue('severity', $v);

        }

        if ($v = val('event', $get)) {
            $sql->where('event =', $v);
            $sender->Form->setFormValue('event', $v);
        }

        $sortOrder = 'desc';
        if ($v = val('sortorder', $get)) {
            if ($v == 'asc') {
                $sortOrder = 'asc';
            } else {
                $sortOrder = 'desc';
            }
        }
        $sql->orderBy('Timestamp', $sortOrder);
        $sender->Form->setFormValue('sortorder', $sortOrder);

        $events = $sql->get()->resultArray();
        $events = array_splice($events, 0, $pageSize);


        // Application calculation.
        foreach ($events as &$event) {
            $event['Url'] = $event['Domain'] . ltrim($event['Path'], '/');
//            $event['InsertProfileUrl'] = userUrl(Gdn::userModel()->getID($event['InsertUserID']));

            unset($event['Domain'], $event['Path']);
        }

        $sender->setData('_CurrentRecords', count($events));

        $sender->addSideMenu();
        $severityOptions = self::getArrayWithKeysAsValues($this->severityOptions);
        $severityOptions['all'] = 'All';

        $filter = Gdn::request()->get();
        unset($filter['TransientKey']);
        unset($filter['hpt']);
        unset($filter['Filter']);
        $currentFilter = http_build_query($filter);

        $sender->setData(
            [
                'Events' => $events,
                'SeverityOptions' => $severityOptions,
                'SortOrder' => $sortOrder,
                'CurrentFilter' => $currentFilter
            ]
        );

        $sender->render('eventlog', '', 'plugins/dblogger');
    }

    /**
     * Takes all of the vales and sets them to that of the key.
     *
     * @param array $array Input array.
     *
     * @return array Output array.
     */
    public static function getArrayWithKeysAsValues($array) {
        $keys = array_keys($array);
        return array_combine($keys, $keys);
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
     * @return DbLoggerPlugin Returns `$this` for fluent calls.
     */
    public function setLevel($level) {
        $this->level = $level;
        return $this;
    }

}
