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
$Tasks->Perform(TaskList::ACTION_TARGET);

$Tasks->Run(TaskList::MODE_TARGET, array(
   'global/backup',
   'global/offline',
   'global/uncache',
   'maintain/filesystem',
   'maintain/structure',
   'global/online'
));