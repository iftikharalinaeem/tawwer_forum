#!/usr/bin/php
<?php

require_once("includes/runner.php");

/**
 * Set up tasklist object
 *  - Open configuration files
 *  - Connect to database
 *
 */
$Tasks = new TaskList();
$Tasks->Clients('/srv/www/vhosts');
$Tasks->Perform(TaskList::ACTION_CACHE);

if ($RunForAll) {
   $RunMode = TaskList::MODE_TARGET;
} else {
   $RunMode = TaskList::MODE_TARGET;
   $Tasks->Perform(TaskList::ACTION_TARGET);
}

$Tasks->Run($RunMode, array(
   'global/backup',
   'global/offline',
   'global/uncache',
   'maintain/filesystem',
   'maintain/utilityupdate',
   'global/online'
));