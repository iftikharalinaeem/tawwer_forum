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
$Tasks->Clients();
$Tasks->Perform(TaskList::ACTION_CACHE);

$RunForAll = $Tasks->GetConsoleOption('all', FALSE);
if ($RunForAll) {
   $RunMode = TaskList::MODE_CHUNKED;
} else {
   $RunMode = TaskList::MODE_TARGET;
   $Tasks->Perform(TaskList::ACTION_TARGET);
}

$Tasks->Run($RunMode, array(
   'global/backup',
   'global/offline',
   'global/uncache',
   'maintain/filesystem',
   'maintain/plugins',
   'maintain/utilityupdate',
   'global/online'
));