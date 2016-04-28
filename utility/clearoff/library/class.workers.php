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

    public static $ParentPid;
    public $MyPid;
    public $IsChild;
    public $Server;

    /*
     * Thread/job control
     */
    public $Worker;
    public $Workers;
    public $NumWorkers;
    public $JobsPerWorker;
    public $MaxWorkerID;
    public static $Working;

    /*
     * Logging levels
     */

    const LOG_L_FATAL = 1;
    const LOG_L_WARN = 2;
    const LOG_L_NOTICE = 4;
    const LOG_L_INFO = 8;
    const LOG_L_THREAD = 16;

    /*
     * Logging output modifiers
     */
    const LOG_O_NONEWLINE = 1;
    const LOG_O_SHOWTIME = 2;
    const LOG_O_SHOWPID = 4;

    public static $LogLevel = -1;

    public function __construct($Worker, $NumWorkers, $JobsPerWorker, $LogLevel = 7) {
        Workers::$ParentPid = getmypid();
        $this->MyPid = getmypid();
        $this->Worker = $Worker;
        $this->NumWorkers = $NumWorkers;
        $this->JobsPerWorker = $JobsPerWorker;
        self::$LogLevel = $LogLevel;

        // Build LockKey
        $Hostname = gethostname();
        $Hostparts = explode('.', $Hostname);
        $this->Server = array_shift($Hostparts);
    }

    public static function Options() {

        $NumWorkers = 10;
        $JobsPerWorker = 30;
        $LogLevel = 7;

        $Options = getopt("", array(
            'workers:', 'jobs:', 'log:'
        ));

        if (array_key_exists('workers', $Options))
            $NumWorkers = GetValue('workers', $Options);

        if (array_key_exists('jobs', $Options))
            $JobsPerWorker = GetValue('jobs', $Options);

        if (array_key_exists('log', $Options))
            $LogLevel = GetValue('log', $Options);

        if (!is_numeric($NumWorkers)) {
            Workers::Log(Workers::LOG_L_FATAL, "--workers must be a number");
            exit(1);
        }

        if (!is_numeric($JobsPerWorker)) {
            Workers::Log(Workers::LOG_L_FATAL, "--jobs must be a number");
            exit(1);
        }

        if (!is_numeric($LogLevel)) {
            Workers::Log(Workers::LOG_L_FATAL, "--log must be a number");
            exit(1);
        }

        return array(
            'NumWorkers' => $NumWorkers,
            'JobsPerWorker' => $JobsPerWorker,
            'LogLevel' => $LogLevel
        );
    }

    /**
     * Execute worker
     *
     * @return void
     */
    public function Work($WorkerID) {

        // Prepare worker
        $WorkerClass = $this->Worker;
        $Worker = new $WorkerClass($WorkerID);
        $Worker->Prepare($this);
        $Worker->GetJobs();

        // Run jobs
        $Worker->Run();
    }

    /**
     * Fork N times and run each job bundle
     *
     * Forks into NumThreads threads and runs the job bundle in each, then waits
     * in the parent while they all gradually complete and terminate.
     *
     * @return void
     */
    public function Execute() {

        // Declare ticks
        declare(ticks = 1);

        // Install signal handlers
        pcntl_signal(SIGCHLD, array($this, 'CatchSignal'));
        pcntl_signal(SIGUSR1, array($this, 'CatchSignal'));

        // Prepare to fork
        self::$Working = TRUE;
        $this->Workers = array();
        $this->MaxWorkerID = 0;
        call_user_func(array($this->Worker, 'Prefork'));

        while (self::$Working) {
            // Do we need more workers?
            $WorkerID = $this->NextWorker();
            if ($WorkerID !== FALSE) {
                // Fork
                Workers::Log(Workers::LOG_L_THREAD, " Forking for worker [{$WorkerID}]", Workers::LOG_O_SHOWPID);
                $Pid = pcntl_fork();

                if ($Pid > 0) {

                    // Parent
                    $this->IsChild = FALSE;

                    // Record which worker each pid represents
                    $this->Workers[$Pid] = $WorkerID;
                } else if ($Pid == 0) {

                    // Thread
                    $this->IsChild = TRUE;
                    Workers::Log(Workers::LOG_L_THREAD, "  Forked worker", Workers::LOG_O_SHOWPID);

                    // Record our PID
                    $this->MyPid = getmypid();

                    // Create worker
                    $this->Work($WorkerID);

                    // Exit
                    exit(0);
                } else {

                    // Failed
                    Workers::Log(Workers::LOG_L_FATAL, "  Failed to fork new worker");
                    exit(1);
                }
            }

            // If a worker gets here, get rid of it
            if ($this->IsChild)
                exit(0);

            sleep(1);
        }

        // If a worker gets here, get rid of it
        if ($this->IsChild)
            exit(0);

        // Wait for all workers to die
        $this->Reap();

        call_user_func(array($this->Worker, 'Complete'));
    }

    /**
     * Get the next worker ID
     *
     * @return int|bool FALSE if already at max workers
     */
    protected function NextWorker() {
        $CurrentWorkers = $this->Outstanding();
        if ($CurrentWorkers >= $this->NumWorkers) {
            return FALSE;
        }


        return ++$this->MaxWorkerID;
    }

    public function CatchSignal($Signal) {
        $Pid = pcntl_waitpid(-1, $Status, WNOHANG);
        if ($Pid > 0)
            Workers::Log(Workers::LOG_L_THREAD, " Caught signal '{$Signal}' for pid '{$Pid}'", Workers::LOG_O_SHOWPID);

        switch ($Signal) {
            case SIGCHLD:

                // Reap this worker
                $this->ReapWorker($Pid);

                break;

            case SIGUSR1:
                Workers::Log(Workers::LOG_L_NOTICE, " All jobs delegated, waiting...", Workers::LOG_O_SHOWPID);
                self::$Working = FALSE;
                break;
        }
    }

    /**
     * Check if any workers are still running
     *
     * @return integer
     */
    public function Outstanding() {
        return sizeof($this->Workers);
    }

    /**
     * Reap completed workers
     *
     * @return void
     */
    public function Reap() {
        Workers::Log(Workers::LOG_L_THREAD, " Entering Reap cycle for workers", Workers::LOG_O_SHOWPID);

        do {
            // Wait a little (dont tightloop)
            sleep(1);

            // Clean up all exited children
            do {
                $WorkerStatus = NULL;
                $Pid = pcntl_wait($WorkerStatus, WNOHANG);
                if ($Pid > 0)
                    $this->ReapWorker($Pid);
            } while ($Pid > 0);

            $Outstanding = $this->Outstanding();
        } while ($Outstanding);
    }

    /**
     * Reap a worker
     * @param type $Pid
     */
    protected function ReapWorker($Pid) {
        // One of ours?
        if (array_key_exists($Pid, $this->Workers)) {
            $WorkerID = GetValue($Pid, $this->Workers);
            Workers::Log(Workers::LOG_L_THREAD, " Reaping worker {$WorkerID} with PID {$Pid}", Workers::LOG_O_SHOWPID);
            unset($this->Workers[$Pid]);
        }
    }

    /**
     *
     * @param type $Time
     * @param type $Format
     * @return DateTime
     */
    public static function Time($Time = 'now', $Format = NULL) {
        $Timezone = new DateTimeZone('utc');

        if (is_null($Format))
            $Date = new DateTime($Time, $Timezone);
        else
            $Date = DateTime::createFromFormat($Format, $Time, $Timezone);

        return $Date;
    }

    public static function Log($Level, $Message, $Options = 0) {
        if (Workers::$LogLevel & $Level || Workers::$LogLevel == -1) {
            if ($Options & Workers::LOG_O_SHOWPID)
                echo "[" . getmypid() . "]";

            if ($Options & Workers::LOG_O_SHOWTIME) {
                $Time = Workers::Time('now');
                echo "[" . $Time->format('Y-m-d H:i:s') . "] ";
            }

            echo $Message;
            if (!($Options & Workers::LOG_O_NONEWLINE))
                echo "\n";
        }
    }

}
