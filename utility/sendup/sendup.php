#!/usr/bin/env php
<?php
/**
 * This file is part of Infrastructure.
 *
 * SendUp is threaded. It transfers the contents of entire folders to CloudFiles
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
 * --folder
 *
 *   The source folder for the files to send.
 *
 * --container
 *
 *   The target cloudfiles container. Default 'cdn'.
 *
 * --prefix
 *
 *   Add this to the front of each uploaded file. Ordinarily this should just be
 *   the site name of the customer, e.g. 'customer.vanillaforums.com'.
 *
 * --move
 *
 *   Move completed files to the side, into a folder called <folder>-completed.
 *
 * --lower
 *
 *   Lowercase filenames when moving them.
 *
 * --workers
 *
 *   How many threads to run at a time. Default '10'
 *
 * --jobs
 *
 *   How many jobs should a worker do before restarting. Default '30'
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2012, Tim Gunter
 */
define('APPLICATION', 'SendUp');
define('APPLICATION_ROOT', getcwd());
define('LOCKFILE', APPLICATION_ROOT . '/' . strtolower(APPLICATION) . '.pid');

// First, load basic functions and classes
require_once('library/functions.php');
require_once('library/class.worker.php');
require_once('library/class.workers.php');
require_once('library/class.sendup.php');

// Include Rackspace API
require_once('library/class.cloudproxyrequest.php');
require_once('library/class.rackspace.php');
require_once('library/class.cloudfiles.php');
require_once('library/class.cloudfilescdn.php');

$Locked = Lock(LOCKFILE);
if (!$Locked) {
    exit(0);
}

$ExitCode = 0;
$Workers = NULL;

// Get options
$Options = Workers::Options();
extract($Options);

$StartTime = microtime(true);
Workers::Log(Workers::LOG_L_NOTICE, "SendUp started", Workers::LOG_O_SHOWTIME);
try {

    // Create Scraper object
    $Workers = new Workers('SendUp', $NumWorkers, $JobsPerWorker, $LogLevel);

    // Run forked execution
    $Workers->Execute();
} catch (Exception $Ex) {

    if ($Ex->getCode() != '200') {
        $ExitCode = 1;
        echo $Ex->getMessage() . "\n";
    }
}
$Elapsed = microtime(true) - $StartTime;
$ElapsedSeconds = round($Elapsed, 2);
Workers::Log(Workers::LOG_L_NOTICE, "SendUp finished, took {$ElapsedSeconds} seconds", Workers::LOG_O_SHOWTIME);

Unlock(LOCKFILE);
exit($ExitCode);
