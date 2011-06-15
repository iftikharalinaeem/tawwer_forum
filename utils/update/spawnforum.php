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
$Tasks->Perform(TaskList::ACTION_CREATE);

$Tasks->Run(TaskList::MODE_TARGET, array(
   'spawn/newforum',
   'global/offline',
   'maintain/filesystem',
   'maintain/plugins',
   'maintain/utilityupdate',
   'global/online',
   'spawn/installed'
));
