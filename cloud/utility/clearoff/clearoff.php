#!/usr/bin/env php
<?php
/**
 * This file is part of Infrastructure.
 *
 * ClearOff is threaded. It transfers the contents of entire folders to CloudFiles
 * in a multi-threaded and efficient way.
 *
 * Environment Variables:
 *
 *   required:
 *    RACKSPACE_USERNAME
 *    RACKSPACE_APIKEY
 *
 *   optional:
 *    RACKSPACE_FACING (default 'public')
 *    RACKSPACE_REGION (default 'DFW')
 *
 * Arguments:
 *
 * --container
 *
 *   The target cloudfiles container. Default 'cdn'.
 *
 * --prefix
 *
 *   The prefix used to filter objects in the container.
 *   Example: something.vanillacommunities.com/subfolder/
 *
 * --workers
 *
 *   How many threads to run at a time. Default '10'
 *
 * --jobs
 *
 *   How many jobs should a worker do before restarting. Default '30'
 *
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @license Proprietary
 * @copyright 2016, lol
 */
define('APPLICATION', 'ClearOff');
define('APPLICATION_ROOT', getcwd());
define('LOCKFILE', APPLICATION_ROOT . '/' . strtolower(APPLICATION) . '.pid');

// Include Rackspace API
require_once(__DIR__.'/vendor/autoload.php');

// First, load basic functions and classes
require_once(__DIR__.'/library/functions.php');
require_once(__DIR__.'/library/class.worker.php');
require_once(__DIR__.'/library/class.workers.php');
require_once(__DIR__.'/library/class.clearoff.php');

$locked = lock(LOCKFILE);
if (!$locked) {
    exit(0);
}

$exitCode = 0;

$workers = null;

// Get options
$options = Workers::options();
extract($options);

$startTime = microtime(true);
Workers::log(Workers::LOG_L_NOTICE, "ClearOff started", Workers::LOG_O_SHOWTIME);
try {
    // Create Scraper object
    $workers = new Workers('ClearOff', $NumWorkers, $JobsPerWorker, $LogLevel);

    // Run forked execution
    $workers->execute();
} catch (Exception $ex) {

    if ($ex->getCode() != '200') {
        $exitCode = 1;
        echo $ex->getMessage() . "\n";
    }
}
$elapsed = microtime(true) - $startTime;
$elapsedTime = timeFormat($elapsed);
Workers::log(Workers::LOG_L_NOTICE, "ClearOff finished, took $elapsedTime", Workers::LOG_O_SHOWTIME);

unlock(LOCKFILE);
exit($exitCode);
