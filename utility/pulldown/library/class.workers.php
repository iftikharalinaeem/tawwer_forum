<?php

/**
 * Workers manager
 *
 * Handles running jobs in a concurrent but abstracted way.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010, Vanilla Forums, Inc
 */
class Workers {
    /*
     * Scraper identification
     */

    public static $parentPid;
    public $server;

    /*
     * Thread/job control
     */
    public $worker;
    public $jobsPerWorker;
    public $numVentilators;
    public $numSinks;
    public $numWorkers;
    public $spawnedVentilators;
    public $activeVentilators;
    public $activeSinks;
    public $activeWorkers;
    public static $workers;
    public static $spawn;
    protected static $semkey = null;
    protected static $sem = null;
    protected static $locked = false;

    /*
     * Logging levels
     */

    const LOG_L_FATAL = 1;
    const LOG_L_WARN = 2;
    const LOG_L_NOTICE = 4;
    const LOG_L_INFO = 8;
    const LOG_L_APP = 16;
    const LOG_L_QUEUE = 32;
    const LOG_L_THREAD = 64;

    /*
     * Logging output modifiers
     */
    const LOG_O_NONEWLINE = 1;
    const LOG_O_SHOWTIME = 2;
    const LOG_O_SHOWPID = 4;

    public static $logLevel = -1;

    public function __construct($worker, $options) {
        self::$parentPid = getmypid();
        $this->worker = $worker;

        foreach ($options as $option => $optionVal) {
            if (property_exists($this, $option))
                $this->$option = $optionVal;
        }
        if (array_key_exists('logLevel', $options))
            self::$logLevel = $options['logLevel'];

        // Build LockKey
        $hostname = gethostname();
        $hostparts = explode('.', $hostname);
        $this->server = array_shift($hostparts);

        self::$semkey = ftok(__FILE__, 's');
    }

    /**
     * Parse commandline options
     *
     * @return type
     */
    public static function options() {

        $numVentilators = 1;
        $numSinks = 1;
        $numWorkers = 10;
        $jobsPerWorker = 0;
        $logLevel = 7;

        $options = getopt("", array(
            'workers:', 'jobs:', 'log:'
        ));

        if (array_key_exists('workers', $options))
            $numWorkers = GetValue('workers', $options);

        if (array_key_exists('jobs', $options))
            $jobsPerWorker = GetValue('jobs', $options);

        if (array_key_exists('log', $options))
            $logLevel = GetValue('log', $options);

        if (!is_numeric($numWorkers)) {
            Workers::log(Workers::LOG_L_FATAL, "--workers must be a number");
            exit(1);
        }

        if (!is_numeric($jobsPerWorker)) {
            Workers::log(Workers::LOG_L_FATAL, "--jobs must be a number");
            exit(1);
        }

        if (!is_numeric($logLevel)) {
            Workers::log(Workers::LOG_L_FATAL, "--log must be a number");
            exit(1);
        }

        return array(
            'numVentilators' => $numVentilators,
            'numSinks' => $numSinks,
            'numWorkers' => $numWorkers,
            'jobsPerWorker' => $jobsPerWorker,
            'logLevel' => $logLevel
        );
    }

    /**
     * Synchronize after forking
     *
     */
    protected function sync() {
        self::$sem = sem_get(self::$semkey, 1);
        if (self::$locked && self::$locked != getmypid())
            self::$locked = false;
    }

    /**
     * Fork N times and run each job bundle
     *
     * Forks into NumThreads threads and runs the job bundle in each, then waits
     * in the parent while they all gradually complete and terminate.
     *
     * @return void
     */
    public function execute() {

        // Install signal handlers
        pcntl_signal(SIGCHLD, array($this, 'signal'));
        pcntl_signal(SIGUSR1, array($this, 'signal'));

        // Prepare to fork
        self::$spawn = true;
        self::$workers = array();
        call_user_func(array($this->worker, 'prefork'));

        $this->activeVentilators = 0;
        $this->activeSinks = 0;
        $this->activeWorkers = 0;

        $maxWorkerID = 0;
        while (self::$spawn) {

            // Trigger signals
            pcntl_signal_dispatch();

            // Do we need more workers?
            $worker = $this->worker($maxWorkerID);
            if ($worker === false) {
                sleep(1);
                continue;
            }

            // Track active worker types
            $this->activate($worker->type);

            // Fork
            Workers::log(Workers::LOG_L_THREAD, " forking for {$worker->type} [{$worker->id}]", Workers::LOG_O_SHOWPID);
            $pid = pcntl_fork();
            Workers::sync();

            // Parent
            if ($pid > 0) {

                // Record our PID
                $myPid = getmypid();
                $isChild = false;

                // Record which worker each pid represents
                self::$workers[$pid] = array(
                    'id' => $worker->id,
                    'type' => $worker->type
                );

                // Keep creating workers
                unset($worker);
                usleep(500);
                continue;

                // Child
            } else if ($pid == 0) {

                $isChild = true;
                break;

                // Failed
            } else {

                Workers::log(Workers::LOG_L_FATAL, " failed to fork");

                // Track active worker types
                $this->deactivate($worker->type, false);

                exit(1);
            }
        }

        if ($isChild) {

            // Record our PID
            $myPid = getmypid();

            // Clean up scope
            self::$spawn = null;
            self::$workers = null;

            Workers::log(Workers::LOG_L_THREAD, " forked {$worker->type}", Workers::LOG_O_SHOWPID);

            // Perform work
            $worker->work();
        } else {

            $this->wait();
        }

        exit(0);
    }

    /**
     * Get the next worker
     *
     * @return Worker|bool false if already at max workers
     */
    protected function worker(&$maxWorkerID) {
        $currentWorkers = $this->outstanding();
        if ($currentWorkers >= ($this->numWorkers + $this->numSinks + $this->numVentilators))
            return false;

        // Create worker object
        $workerID = ++$maxWorkerID;
        $workerClass = $this->worker;
        if ($this->spawnedVentilators < $this->numVentilators)
            $workerType = 'ventilator';
        else if ($this->activeSinks < $this->numSinks)
            $workerType = 'sink';
        else {
            if ($this->activeWorkers >= $this->numWorkers)
                return false;
            $workerType = 'worker';
        }

        $worker = new $workerClass($this);
        $worker->id = $workerID;
        $worker->type = $workerType;

        return $worker;
    }

    /**
     * Catch signals
     *
     * @param integer $signal
     */
    public function signal($signal) {
        Workers::Log(Workers::LOG_L_THREAD, " signal '{$signal}'", Workers::LOG_O_SHOWPID);
        switch ($signal) {

            // Reap zombie children
            case SIGCHLD:
                do {
                    $status = NULL;
                    $pid = pcntl_waitpid(0, $status, WNOHANG);
                    if ($pid > 0)
                        $this->reap($pid);
                } while ($pid > 0);
                break;

            // Stop spawning things
            case SIGUSR1:
                $this->finished();
                break;
        }
    }

    /**
     * No more spawning!
     */
    public function finished() {
        Workers::log(Workers::LOG_L_THREAD, " received shutdown", Workers::LOG_O_SHOWPID);
        self::$spawn = false;
    }

    /**
     * Check if any workers are still running
     *
     * @return integer
     */
    public function outstanding() {
        return sizeof(self::$workers);
    }

    /**
     * Reap a worker
     * @param type $pid
     */
    protected function reap($pid) {
        // One of ours?
        if (array_key_exists($pid, self::$workers)) {
            $worker = GetValue($pid, self::$workers);
            $workerID = GetValue('id', $worker);
            $workerType = GetValue('type', $worker);

            // Track active worker types
            $this->deactivate($workerType);

            Workers::log(Workers::LOG_L_THREAD, " reap {$workerType}, wid:{$workerID} pid:{$pid}", Workers::LOG_O_SHOWPID);
            unset(self::$workers[$pid]);
        }
    }

    protected function activate($type) {
        switch ($type) {
            case 'ventilator':
                $this->spawnedVentilators++;
                $this->activeVentilators++;
                break;
            case 'sink':
                $this->activeSinks++;
                break;
            case 'worker':
                $this->activeWorkers++;
                break;
        }
    }

    protected function deactivate($type, $naturalCauses = true) {
        switch ($type) {
            case 'ventilator':
                $this->activeVentilators--;

                if (!$naturalCauses)
                    $this->spawnedVentilators--;
                break;
            case 'sink':
                $this->activeSinks--;
                break;
            case 'worker':
                $this->activeWorkers--;
                break;
        }
    }

    /**
     * Wait for all workers to die, and just have sinks left
     *
     */
    public function wait() {
        Workers::Log(Workers::LOG_L_THREAD, " entering reap cycle", Workers::LOG_O_SHOWPID);

        do {
            // Wait a little (dont tightloop)
            sleep(1);

            // Trigger signals
            pcntl_signal_dispatch();

            // Clean up all exited children
            do {
                $status = NULL;
                $pid = pcntl_waitpid(0, $status, WNOHANG);
                if ($pid > 0) {
                    $this->reap($pid);
                    continue;
                }

                // Check status
                if (!$this->activeWorkers) {
                    // Workers all dead, kill sinks
                    foreach (self::$workers as $sinkPID => $sink) {
                        $sent = posix_kill($sinkPID, SIGUSR2);
                        $status = $sent ? "success" : "failed";
                        if (!$sent) {
                            $error = posix_get_last_error();
                            $status = posix_strerror($error);
                        }
                    }
                }

                if (!$this->activeSinks) {
                    exit(0);
                }
            } while ($pid > 0);

            $outstanding = $this->outstanding();
        } while ($outstanding);
    }

    /**
     *
     * @param type $time
     * @param type $format
     * @return DateTime
     */
    public static function time($time = 'now', $format = null) {
        $timezone = new DateTimeZone('utc');

        if (is_null($format))
            $date = new DateTime($time, $timezone);
        else
            $date = DateTime::createFromFormat($format, $time, $timezone);

        return $date;
    }

    public static function semaphore($lock) {
        if (!is_null(self::$sem)) {
            if (self::$locked) {
                if (!$lock) {
                    sem_release(self::$sem);
                    self::$locked = false;
                }
                return true;
            } else if (!self::$locked) {
                if ($lock) {
                    sem_acquire(self::$sem);
                    self::$locked = getmypid();
                }
                return true;
            }
        } else
            return true;
    }

    public static function log($level, $message, $options = 0) {

        // Acquire semaphore
        self::semaphore(true);

        if (Workers::$logLevel & $level || Workers::$logLevel == -1) {
            if ($options & Workers::LOG_O_SHOWPID)
                echo "[" . getmypid() . "]";

            if ($options & Workers::LOG_O_SHOWTIME) {
                $time = Workers::time('now');
                echo "[" . $time->format('Y-m-d H:i:s') . "] ";
            }

            echo $message;
            if (!($options & Workers::LOG_O_NONEWLINE))
                echo "\n";
        }

        // Release semaphore
        self::semaphore(false);
    }

}
