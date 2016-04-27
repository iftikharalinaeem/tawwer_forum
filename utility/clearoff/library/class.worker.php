<?php

if (!defined('APPLICATION'))
    exit();

/**
 * Worker
 *
 * Utility class that sets up worker job providers and makes sure they provide
 * the correct methods.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010, Vanilla Forums, Inc
 * @package discussionstats
 */
abstract class Worker {

    /**
     *
     * @var Workers
     */
    protected $Workers;

    /**
     *
     * @var integer
     */
    protected $WorkerID;

    /**
     *
     * @var array
     */
    protected $Jobs;

    /**
     *
     * @var integer
     */
    protected $JobsPerWorker;

    /**
     * Build worker
     */
    public function __construct($WorkerID) {
        $this->WorkerID = $WorkerID;
    }

    /**
     * Prepare for in-worker execution
     *
     * @param Workers $Workers
     */
    public function Prepare($Workers) {
        $this->Workers = $Workers;
        $this->JobsPerWorker = $this->Workers->JobsPerWorker;
    }

    /**
     * Just before forking, do preparation
     *
     * Disconnect from the database
     */
    public static function Prefork() {

        // NOOP
    }

    /**
     * Get a list of jobs for the worker helper to execute
     *
     * @return boolean
     * @throws Exception
     */
    abstract public function GetJobs();

    /**
     * Run delegated jobs
     */
    public function Run() {

        $NumJobs = sizeof($this->Jobs);
        //Workers::Log(Workers::LOG_L_INFO, "    Running jobs in worker {$this->WorkerID}, pid {$this->Workers->MyPid}, jobs {$NumJobs}");

        foreach ($this->Jobs as $Job)
            $this->RunJob($Job);
    }

    /**
     * Execute a job
     *
     * @param array $Job
     */
    abstract public function RunJob($Job);

    /**
     * After all jobs are completed
     *
     * Cleanup
     */
    abstract public static function Complete();

    /**
     * Tell parent to stop forking
     */
    protected function Finished() {
        $ParentPid = Workers::$ParentPid;
        $Sent = posix_kill(Workers::$ParentPid, SIGUSR1);

        $Status = $Sent ? "success" : "failed";
        if (!$Sent) {
            $Error = posix_get_last_error();
            $Status = posix_strerror($Error);
        }
        Workers::Log(Workers::LOG_L_THREAD, " Sent shutdown command to parent (pid: {$ParentPid}): {$Status}", Workers::LOG_O_SHOWPID);
    }

}
