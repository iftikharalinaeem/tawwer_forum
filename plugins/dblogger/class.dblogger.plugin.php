<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
// Define the plugin:
$PluginInfo['dblogger'] = array(
    'Name' => 'Database Logger',
    'Description' => 'Enabled database logging.',
    'Version' => '1.0-alpha',
    'Author' => "John Ashton",
    'AuthorEmail' => 'john@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.com',
//    'Hidden' => true
);

class DbLoggerPlugin extends Gdn_Plugin {

    public function setup() {
        $this->structure();
    }

    public function structure() {
        Gdn::Structure()->Table('EventLog')
            ->Column('EventLogID', 'varchar(13)', false, 'primary')
            ->Column('TimeInserted', 'uint', true, 'index')
            ->Column('Event', 'varchar(50)', true, 'index')
            ->Column('LogLevel', 'varchar(50)', true, 'index')
            ->Column('Message', 'text')
            ->Column('InsertUserID', 'int', true)
            ->Column('InsertName', 'varchar(50)', true)
            ->Column('InsertIPAddress', 'varchar(15)', true)
            ->Column('Attributes', 'text', true)
            ->Column('Domain', 'varchar(255)', true)
            ->Column('Path', 'varchar(255)', true)
            ->Set();

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

        list($offset, $limit) = OffsetLimit($Page, 30);
        SaveToConfig('Api.Clean', false, false);
        $sql = Gdn::SQL();

        $sql->From('EventLog')
            ->Limit($limit, $offset);

        $get = array_change_key_case($Sender->Request->Get());

        // Look for query parameters to filter the data.
        // todo: use timezone? on dateto and datefrom
        if ($v = val('datefrom', $get)) {
            $v = strtotime($v);
            if (!$v) {
                throw new Gdn_UserException('Invalid date time format.');
            }
            $sql->Where('TimeInserted >=', $v);
        }

        if ($v = val('dateto', $get)) {
            $v = strtotime($v);
            if (!$v) {
                throw new Gdn_UserException('Invalid date time format.');
            }
            $sql->Where('TimeInserted <=', $v);
        }

        if ($v = val('severity', $get)) {
            $validLevels = array(
                'info' => true,
                'notice' => true,
                'warning' => true,
                'error' => true,
            );
            $validLevelString = implode(', ', array_keys(array_slice($validLevels, 0, -1)));
            $validLevelString .= ' and ' . end(array_keys($validLevels));

            if (!isset($validLevels[$v])) {
                throw new Gdn_UserException('Invalid severity.  Valid options are: ' . $validLevelString);
            }
            $sql->Where('LogLevel =', $v);
        }

        if ($v = val('event', $get)) {
            $sql->Where('event =', $v);
        }

        if ($v = val('sortorder', $get)) {
            if ($v == 'asc') {
                $sortOrder = 'asc';
            } else {
                $sortOrder = 'desc';
            }
        } else {
            $sortOrder = 'desc';
        }
        $sql->OrderBy('TimeInserted', $sortOrder);


        $events = $sql->Get();

        // Application calculation.
        foreach ($events as &$event) {
            $event->FullPath = $event->Domain . ltrim($event->Path, '/');
            $event->DateTimeInserted = Gdn_Format::DateFull($event->TimeInserted);
            unset($event->Domain);
            unset($event->Path);
            unset($event->TimeInserted);
        }

        $Sender->AddSideMenu();
        $Sender->SetData('Events', $events);
        $Sender->Render('eventlog', '', 'plugins/dblogger');
    }

}
