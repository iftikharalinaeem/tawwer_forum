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
$Tasks->Clients('/www/vanilla/vhosts');
//$Tasks->Perform(TaskList::ACTION_CACHE);
$Tasks->Perform(TaskList::ACTION_CREATE);

$Tasks->Run(TaskList::MODE_TARGET, array(
   'spawn/newforum',
   'maintain/filesystem',
   'maintain/plugins'
));
