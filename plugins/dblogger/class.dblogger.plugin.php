<?php
/**
 * @copyright 2014 Vanilla Forums Inc.
 * @license Proprietary
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
}
