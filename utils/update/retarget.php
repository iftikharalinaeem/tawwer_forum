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
$Tasks->Perform(TaskList::ACTION_TARGET);

$Tasks->Run(TaskList::MODE_TARGET, array(
   'global/backup',
   'global/offline',
   'global/uncache',
   'maintain/filesystem',
   'maintain/plugins',
   'maintain/utilityupdate',
   'maintain/structure',
   'global/online'
));