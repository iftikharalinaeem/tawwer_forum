<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
// Define the plugin:
$PluginInfo['dblogger'] = array(
    'Name' => 'Db Logger',
    'Description' => 'Enable database logging.',
    'Version' => '1.0-beta',
    'Author' => "John Ashton",
    'AuthorEmail' => 'john@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.com',
    'Hidden' => false
);

class DbLoggerPlugin extends Gdn_Plugin {

    public $severityOptions = array(
        'info' => true,
        'notice' => true,
        'warning' => true,
        'error' => true,
    );

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

    public function Base_GetAppSettingsMenuItems_Handler($Sender) {
        $Menu = &$Sender->EventArguments['SideMenu'];
        $Menu->AddLink('Dashboard', T('Event Log'), '/settings/eventlog', 'Garden.Settings.Manage', array('class' => 'nav-event-log'));
    }

    /**
     * Install the database logger as early as possible.
     *
     * @param Gdn_Dispatcher $Sender
     */
    public function Gdn_Dispatcher_AppStartup_Handler($Sender) {
        Logger::setLogger(new DbLogger());

    }

    /**
     * @param SettingsController $Sender
     * @param string $Page
     */
    public function SettingsController_EventLog_Create($Sender, $Page = '') {
        $Sender->Permission('Garden.Settings.Manage');

        $Sender->Form = new Gdn_Form();
        $pageSize = 30;
        list($offset, $limit) = OffsetLimit($Page, $pageSize);
        SaveToConfig('Api.Clean', false, false);
        $sql = Gdn::SQL();

        $sql->From('EventLog')
            ->Limit($limit + 1, $offset);

        $get = array_change_key_case($Sender->Request->Get());

        // Look for query parameters to filter the data.
        // todo: use timezone? on dateto and datefrom
        if ($v = val('datefrom', $get)) {
            $v = strtotime($v);
            if (!$v) {
                $Sender->Form->AddError('Invalid Date format for From Date.');
            }
            $sql->Where('Timestamp >=', $v);
            $Sender->Form->SetFormValue('datefrom', $get['datefrom']);
        }

        if ($v = val('dateto', $get)) {
            $v = strtotime($v);
            if (!$v) {
                $Sender->Form->AddError('Invalid Date format for To Date.');
            }
            $sql->Where('Timestamp <=', $v);
            $Sender->Form->SetFormValue('dateto', $get['dateto']);
        }

        $Sender->Form->SetFormValue('severity', 'all');
        if (($v = val('severity', $get)) && $v != 'all') {
            $validLevelString = implode(', ', array_keys(array_slice($this->severityOptions, 0, -1)));
            $validLevelString .= ' and ' . end(array_keys($this->severityOptions));

            if (!isset($this->severityOptions[$v])) {
                $Sender->Form->AddError('Invalid severity.  Valid options are: ' . $validLevelString);
            }
            $sql->Where('Level =', $v);
            $Sender->Form->SetFormValue('severity', $v);

        }

        if ($v = val('event', $get)) {
            $sql->Where('event =', $v);
            $Sender->Form->SetFormValue('event', $v);
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
        $Sender->Form->SetFormValue('sortorder', $sortOrder);

        $events = $sql->Get()->ResultArray();
        $events = array_splice($events, 0, $pageSize);


        // Application calculation.
        foreach ($events as &$event) {
            $event['Url'] = $event['Domain'] . ltrim($event['Path'], '/');
//            $event['InsertProfileUrl'] = UserUrl(Gdn::UserModel()->GetID($event['InsertUserID']));

            unset($event['Domain'], $event['Path']);
        }

        $Sender->SetData('_CurrentRecords', count($events));

        $Sender->AddSideMenu();
        $SeverityOptions = self::getArrayWithKeysAsValues($this->severityOptions);
        $SeverityOptions['all'] = 'All';

        $filter = Gdn::Request()->Get();
        unset($filter['TransientKey']);
        unset($filter['hpt']);
        unset($filter['Filter']);
        $CurrentFilter = http_build_query($filter);

        $Sender->SetData(
            array(
                'Events' => $events,
                'SeverityOptions' => $SeverityOptions,
                'SortOrder' => $sortOrder,
                'CurrentFilter' => $CurrentFilter
            )
        );

        $Sender->Render('eventlog', '', 'plugins/dblogger');
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

}
