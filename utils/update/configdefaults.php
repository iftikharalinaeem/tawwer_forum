#!/usr/bin/php
<?php

/**
 * This file is part of Runner.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010, Tim Gunter 
 */

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
   'maintain/config'
));