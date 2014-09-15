#!/usr/bin/env php
<?php
/**
 * This file is part of Infrastructure.
 *
 * PullDown is forked. It transfers the contents of entire folders to CloudFiles
 * within several forked processes in parallel.
 *
 * Arguments:
 *
 * --folder
 *
 *   The source folder for the files to send.
 *
 * --workers
 *
 *   How many threads to run at a time. Default '10'
 *
 * --jobs
 *
 *   How many jobs should a worker do before restarting. Default '30'
 *
 * --dsn
 *
 *   DSN Driver,.   mysql or stdin
 *
 * --host
 *
 *   DSN Host
 *
 * --username
 *
 *   DSN Username
 *
 * --password
 *
 *   DSN Username
 *
 * --db
 *
 *   DSN database name
 *
 * --table
 *
 *   DSN Table name
 *
 *
 * Table Structure:
 *
 *  CREATE TABLE `GDN_Pulldown` (
 *      `jobid` int(11) unsigned NOT NULL AUTO_INCREMENT,
 *      `downloaded` int(11) DEFAULT '0',
 *      `allocated` varchar(20) DEFAULT NULL,
 *      `url` varchar(255) DEFAULT NULL,
 *      `responsecode` int(11) DEFAULT NULL,
 *      PRIMARY KEY (`jobid`)
 *  ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
 *
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2012, Tim Gunter
 */
define('APPLICATION', 'PullDown');
define('APPLICATION_ROOT', getcwd());
define('LOCKFILE', APPLICATION_ROOT . '/' . strtolower(APPLICATION) . '.pid');

// First, load basic functions and classes
require_once('library/functions.php');
require_once('library/class.worker.php');
require_once('library/class.workers.php');
require_once('library/class.pulldown.php');

// Include Rackspace API
require_once('library/class.proxyrequest.php');
require_once('library/class.rackspaceapi.php');
require_once('library/class.rackspacecloudfiles.php');

$locked = Lock(LOCKFILE);
if (!$locked)
    exit(0);

$exitCode = 0;
$workers = null;

// Get options
$options = Workers::options();

$startTime = microtime(true);
Workers::log(Workers::LOG_L_NOTICE, "PullDown started", Workers::LOG_O_SHOWTIME);
try {

    // Create Scraper object
    $workers = new Workers('PullDown', $options);

    // Run forked execution
    $workers->execute();
} catch (Exception $ex) {

    if ($ex->getCode() != '200') {
        $exitCode = 1;
        echo $ex->getMessage() . "\n";
    }
}
$elapsed = microtime(true) - $startTime;
$elapsedSeconds = round($elapsed, 2);
Workers::log(Workers::LOG_L_NOTICE, "PullDown finished, took {$elapsedSeconds} seconds", Workers::LOG_O_SHOWTIME);

Unlock(LOCKFILE);
exit($exitCode);
