<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
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
        Gdn::Structure()->Table('EventLog');
        if (Gdn::Structure()->ColumnExists('EventLogID')) {
            Gdn::Structure()->Drop();
        }

        Gdn::Structure()->Table('EventLog')
            ->Column('ID', 'varchar(16)', false, 'primary')
            ->Column('Timestamp', 'uint', true, 'index')
            ->Column('Event', 'varchar(50)', true, 'index')
            ->Column('Level', 'tinyint', true, 'index')
            ->Column('Message', 'text')
            ->Column('Method', 'varchar(10)', true)
            ->Column('Domain', 'varchar(255)', true)
            ->Column('Path', 'varchar(255)', true)
            ->Column('UserID', 'int', true)
            ->Column('Username', 'varchar(50)', true)
            ->Column('IP', 'ipaddress', true)
            ->Column('Attributes', 'text', true)
            ->Set();
    }

    /**
     * @param DashboardNavModule $sender
     */
    public function dashboardNavModule_init_handler($sender) {
        $sender->addLinkIf('GardenSettingsManage', t('Event Log'), '/settings/eventlog', 'site-settings.event-log');
    }

    /**
     * Install the database logger as early as possible.
     */
    public function gdn_dispatcher_appStartup_handler() {
        $logger = new DbLogger();

        try {
            $logger->setPruneAfter(c('Plugins.dblogger.PruneAfter', '-90 days'));
        } catch (InvalidArgumentException $e) {
            // Do nothing on an invalid date. Just don't set it.
        }

        Logger::addLogger($logger, $this->getLevel());
    }

    /**
     * @param SettingsController $sender
     * @param string $page
     */
    public function SettingsController_EventLog_Create($sender, $page = '') {
        $sender->Permission('Garden.Settings.Manage');

        $sender->Form = new Gdn_Form();
        $pageSize = 30;
        list($offset, $limit) = OffsetLimit($page, $pageSize);
        SaveToConfig('Api.Clean', false, false);
        $sql = Gdn::SQL();

        $sql->From('EventLog')
            ->Limit($limit + 1, $offset);

        $get = array_change_key_case($sender->Request->Get());

        // Look for query parameters to filter the data.
        // todo: use timezone? on dateto and datefrom
        if ($v = val('datefrom', $get)) {
            $v = strtotime($v);
            if (!$v) {
                $sender->Form->AddError('Invalid Date format for From Date.');
            }
            $sql->Where('Timestamp >=', $v);
            $sender->Form->SetFormValue('datefrom', $get['datefrom']);
        }

        if ($v = val('dateto', $get)) {
            $v = strtotime($v);
            if (!$v) {
                $sender->Form->AddError('Invalid Date format for To Date.');
            }
            $sql->Where('Timestamp <=', $v);
            $sender->Form->SetFormValue('dateto', $get['dateto']);
        }

        $sender->Form->SetFormValue('severity', 'all');
        if (($v = val('severity', $get)) && $v != 'all') {
            $validLevelString = implode(', ', array_keys(array_slice($this->severityOptions, 0, -1)));
            $validLevelString .= ' and ' . end(array_keys($this->severityOptions));

            if (!isset($this->severityOptions[$v])) {
                $sender->Form->AddError('Invalid severity.  Valid options are: ' . $validLevelString);
            }
            $sql->Where('Level =', $v);
            $sender->Form->SetFormValue('severity', $v);

        }

        if ($v = val('event', $get)) {
            $sql->Where('event =', $v);
            $sender->Form->SetFormValue('event', $v);
        }

        $sortOrder = 'desc';
        if ($v = val('sortorder', $get)) {
            if ($v == 'asc') {
                $sortOrder = 'asc';
            } else {
                $sortOrder = 'desc';
            }
        }
        $sql->OrderBy('Timestamp', $sortOrder);
        $sender->Form->SetFormValue('sortorder', $sortOrder);

        $events = $sql->Get()->ResultArray();
        $events = array_splice($events, 0, $pageSize);


        // Application calculation.
        foreach ($events as &$event) {
            $event['Url'] = $event['Domain'] . ltrim($event['Path'], '/');
//            $event['InsertProfileUrl'] = UserUrl(Gdn::UserModel()->GetID($event['InsertUserID']));

            unset($event['Domain'], $event['Path']);
        }

        $sender->SetData('_CurrentRecords', count($events));

        $sender->AddSideMenu();
        $severityOptions = self::getArrayWithKeysAsValues($this->severityOptions);
        $severityOptions['all'] = 'All';

        $filter = Gdn::Request()->Get();
        unset($filter['TransientKey']);
        unset($filter['hpt']);
        unset($filter['Filter']);
        $currentFilter = http_build_query($filter);

        $sender->SetData(
            [
                'Events' => $events,
                'SeverityOptions' => $severityOptions,
                'SortOrder' => $sortOrder,
                'CurrentFilter' => $currentFilter
            ]
        );

        $sender->Render('eventlog', '', 'plugins/dblogger');
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
