<?php
use OpenCloud\Rackspace;

class ClearOff extends Worker {

    protected static $batchDelete = 101; // Delete 101 objects per jobs

    protected static $container;
    protected static $prefix;

    protected static $objectsPathSource;

    protected static $rackspaceUser;
    protected static $rackspaceApiKey;
    protected static $rackspaceFacing;
    protected static $rackspaceRegion;

    protected $rackspaceClient;
    protected $rackspaceService;
    protected $rackspaceContainer;

    /**
     * Prepare for forking and execution
     */
    public static function prefork() {
        $args = getopt('', array('prefix:', 'container:'));

        $container = val('container', $args, false);
        if (!$container) {
            throw new Exception("Required argument --container not provided");
        }

        $prefix = val('prefix', $args, false);
        if (!$prefix) {
            throw new Exception("Required argument --prefix not provided");
        }

        // Prepare the cloudfiles container
        self::$container = $container;
        self::$prefix = $prefix;

        // Prepare cloudfiles
        self::$rackspaceUser = getenv('RACKSPACE_USERNAME');
        if (!self::$rackspaceUser) {
            throw new Exception("Could not import RACKSPACE_USERNAME");
        }
        self::$rackspaceApiKey = getenv('RACKSPACE_APIKEY');
        if (!self::$rackspaceApiKey) {
            throw new Exception("Could not import RACKSPACE_APIKEY");
        }

        self::$rackspaceFacing = getenv('RACKSPACE_FACING');
        if (!self::$rackspaceFacing) {
            self::$rackspaceFacing = 'publicURL';
        }
        self::$rackspaceRegion = getenv('RACKSPACE_REGION');
        if (!self::$rackspaceRegion) {
            self::$rackspaceRegion = 'DFW';
        }
        self::$rackspaceRegion = strtoupper(self::$rackspaceRegion);

        // Prepare the job source
        $parentPid = Workers::$ParentPid;
        $objectsPathSource = "/tmp/" . APPLICATION . ".{$parentPid}.jobs";
        if (file_exists($objectsPathSource)) {
            unlink($objectsPathSource);
        }

        self::$objectsPathSource = $objectsPathSource;
        self::collectObjectListToDelete();
    }

    private static function collectObjectListToDelete() {
        Workers::Log(Workers::LOG_L_WARN, 'Querying rackspace to get objects paths. Might take a while (~10sec/10k objects)!', Workers::LOG_O_SHOWTIME);

        // Doing it externally because the Rackspace library is $#%@#$%
        // and breaks in the child processes when trying to create new instances.
        $command = 'php '.realpath(__DIR__.'/../collector.php')
                .' --container='.self::$container
                .' --prefix='.self::$prefix
                .' --rackspaceUser='.self::$rackspaceUser
                .' --rackspaceApiKey='.self::$rackspaceApiKey
                .' --rackspaceRegion='.self::$rackspaceRegion
                .' --rackspaceFacing='.self::$rackspaceFacing
                .' --objectsPathSource='.self::$objectsPathSource
            ;
        Workers::Log(Workers::LOG_L_INFO, 'Executing command:', Workers::LOG_O_SHOWTIME);
        Workers::Log(Workers::LOG_L_INFO, $command);

        exec($command, $output, $statusCode);

        if ($statusCode === 0 && empty($output)) {
            Workers::Log(Workers::LOG_L_WARN, 'Done getting object list!', Workers::LOG_O_SHOWTIME);
        } else {
            Workers::Log(Workers::LOG_L_FATAL, 'Something went wrong!', Workers::LOG_O_SHOWTIME);
            Workers::Log(Workers::LOG_L_FATAL, print_r($output, true), Workers::LOG_O_SHOWTIME);
            die();
        }
    }

    /**
     * Cleanup
     */
    public static function complete() {
        Workers::Log(Workers::LOG_L_NOTICE, " Cleaning up", Workers::LOG_O_SHOWPID);
        unlink(self::$objectsPathSource);
    }

    /**
     * Prepare to execute our jobs
     *
     * @param Workers $Workers
     */
    public function prepare($workers = null) {
        if ($workers) {
            parent::prepare($workers);
        }

        try {
            $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, [
                'username' => self::$rackspaceUser,
                'apiKey' => self::$rackspaceApiKey,
            ]);
            $this->rackspaceService = $client->objectStoreService(null, self::$rackspaceRegion, self::$rackspaceFacing);
            $this->rackspaceContainer = $this->rackspaceService->getContainer(self::$container);
        } catch(Exception $e) {
            Workers::Log(Workers::LOG_L_FATAL, 'Fu**: '.$e->getMessage());
        }
    }

    /**
     * Get a list of jobs for the worker to execute
     */
    public function getJobs() {
        $chunk = $this->WorkerID - 1;
        $objectsPathSource = self::$objectsPathSource;
        $chunkSize = (self::$batchDelete * $this->JobsPerWorker);
        $startLineNumber = $chunk * $chunkSize;
        $endLineNumber = $startLineNumber + $chunkSize;

        $startLineNumber += 1;

        $fetchCommand = "sed -n '{$startLineNumber},{$endLineNumber}p' {$objectsPathSource}";
        $objectPaths = trim(shell_exec($fetchCommand));

        // Tell the parent that we're done with jobbing
        if (!strlen($objectPaths)) {
            $this->finished();
            $this->Jobs = [];
        } else {
            $objectPaths = explode("\n", $objectPaths);
            $this->Jobs = array_chunk($objectPaths, self::$batchDelete);
            $numJobs = sizeof($this->Jobs);
            Workers::Log(Workers::LOG_L_INFO, "   Got $numJobs jobs", Workers::LOG_O_SHOWPID);
        }
    }

    /**
     * Execute a job
     *
     * @param array $Job
     */
    public function runJob($job) {
        $workerID = $this->WorkerID;

        $pathsToDelete = [];
        foreach ($job as $filePath) {
            $pathsToDelete[] = self::$container.'/'.$filePath;
        }
        
        $maxTries = 5;
        $tries = 0;
        $success = false;
        while ($tries < $maxTries) {
            $tries++;
            try {
                $this->rackspaceService->batchDelete($pathsToDelete);
                $success = true;
                break;
            } catch (Exception $ex) {
                if ($tries == 1) {
                    Workers::Log(Workers::LOG_L_WARN, "   Worker[$workerID] batch delete error...", Workers::LOG_O_SHOWPID);
                }

                Workers::Log(Workers::LOG_L_WARN, "   Worker[$workerID] - retry {$tries}", Workers::LOG_O_SHOWPID);
                sleep(1);
            }
        }

        if ($success) {
            Workers::Log(Workers::LOG_L_WARN, "      Worker[$workerID] deleted ".count($pathsToDelete)." files!", Workers::LOG_O_SHOWPID);
            return;
        } else {
            Workers::Log(Workers::LOG_L_WARN, "     - giving up after {$tries} attempts", Workers::LOG_O_SHOWPID);
            throw $ex;
        }
    }
}
