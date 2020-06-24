<?php

/**
 * SendUp
 *
 * Utility class that handles sending a chunk of files to CloudFiles.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010, Vanilla Forums, Inc
 */
class SendUp extends Worker {

    protected static $Folder;
    protected static $JobsFile;
    protected static $Container;
    protected static $Prefix;
    protected static $Lower;
    protected static $Move;
    protected static $Mode;
    protected static $RackspaceUser;
    protected static $RackspaceApiKey;
    protected static $RackspaceFacing;
    protected static $RackspaceRegion;
    protected $CloudFiles;

    /**
     * Set the target folder
     *
     * Also scans that folder for
     *
     * @param string $Folder
     */
    protected static function Folder($Folder) {
        self::$Mode = "local";

        $folderParts = explode(':', $Folder);
        if (sizeof($folderParts > 1)) {
            $cloudy = array_shift($folderParts);
            if ($cloudy == '~cf') {
                self::$Mode = 'xcopy';
                $sourceContainer = array_shift($folderParts);
                $sourcePrefix = implode(':', $folderParts);
                $sourcePrefix = rtrim($sourcePrefix, '/') . '/';
            }
        }

        $ParentPid = Workers::$ParentPid;
        $JobsFile = "/tmp/" . APPLICATION . ".{$ParentPid}.jobs";
        if (file_exists($JobsFile)) {
            unlink($JobsFile);
        }
        self::$JobsFile = $JobsFile;

        switch (self::$Mode) {

            // Local to remote
            case 'local':

                if (!file_exists($Folder)) {
                    throw new Exception("No such file or folder");
                }
                Workers::Log(Workers::LOG_L_NOTICE, " Scanning local target", Workers::LOG_O_SHOWPID);
                Workers::Log(Workers::LOG_L_NOTICE, " Filesysten - {$Folder}", Workers::LOG_O_SHOWPID);
                self::$Folder = $Folder;

                if (is_dir(self::$Folder)) {
                    shell_exec("tree -if --noreport {$Folder} > {$JobsFile}");
                } else {
                    file_put_contents(self::$JobsFile, $Folder);
                }
                break;

            // Remote to remote
            case 'xcopy':

                // Prepare CloudFiles
                $cloudFiles = new CloudFiles("https://identity.api.rackspacecloud.com/v1.1/", [
                    "username" => self::$RackspaceUser,
                    "apiKey" => self::$RackspaceApiKey,
                    "region" => strtolower(self::$RackspaceRegion),
                    "context" => self::$RackspaceFacing,
                    "flavor" => "cloudservers",
                    "provider" => "rackspace",
                    "url" => "https://identity.api.rackspacecloud.com/v1.1/"
                ]);
                $cloudFiles->CacheCredentials(FALSE);

                // Get complete remote listing
                Workers::Log(Workers::LOG_L_NOTICE, " Scanning remote target", Workers::LOG_O_SHOWPID);
                Workers::Log(Workers::LOG_L_NOTICE, " CloudFiles - {$sourceContainer}:{$sourcePrefix}", Workers::LOG_O_SHOWPID);
                $objects = $cloudFiles->ListObjects($sourceContainer, array(
                    'prefix' => trim($sourcePrefix, '/')
                ));
                self::$Folder = $sourcePrefix;

                // Distill into text list
                $output = '';
                foreach ($objects as $objectName => $object) {
                    $output .= "~cf:{$sourceContainer}:{$objectName}\n";
                }
                file_put_contents(self::$JobsFile, $output);
                break;
        }
    }

    /**
     * Prepare for forking and execution
     */
    public static function Prefork() {

        $Args = getopt('', array('folder:', 'container:', 'prefix:', 'move', 'lower'));

        $Folder = val('folder', $Args, FALSE);
        if (!$Folder) {
            throw new Exception("Required argument --folder not provided");
        }

        $Container = val('container', $Args, FALSE);
        if (!$Container) {
            throw new Exception("Required argument --container not provided");
        }

        if (!`which tree`) {
            echo "Shell command 'tree' not found, please install before using sendup.\n";
            exit(1);
        }

        $Move = array_key_exists('move', $Args);
        $Lower = array_key_exists('lower', $Args);

        $EntryPrefix = val('prefix', $Args, '');

        // Prepare the cloudfiles container
        self::$Container = $Container;
        self::$Prefix = $EntryPrefix;
        self::$Lower = $Lower;
        self::$Move = FALSE;

        if ($Lower) {
            Workers::Log(Workers::LOG_L_NOTICE, " Lowercasing target paths", Workers::LOG_O_SHOWPID);
        }

        if ($Move) {
            $MoveTarget = rtrim($Folder, ' /') . '-completed';
            if (!is_dir($MoveTarget)) {
                mkdir($MoveTarget);
            }

            Workers::Log(Workers::LOG_L_NOTICE, " Moving completed files to: {$MoveTarget}", Workers::LOG_O_SHOWPID);
            self::$Move = $MoveTarget;
        }

        // Prepare cloudfiles
        self::$RackspaceUser = getenv('RACKSPACE_USERNAME');
        if (!self::$RackspaceUser) {
            throw new Exception("Could not import RACKSPACE_USERNAME");
        }
        self::$RackspaceApiKey = getenv('RACKSPACE_APIKEY');
        if (!self::$RackspaceApiKey) {
            throw new Exception("Could not import RACKSPACE_APIKEY");
        }

        self::$RackspaceFacing = getenv('RACKSPACE_FACING');
        if (!self::$RackspaceFacing) {
            self::$RackspaceFacing = 'public';
        }
        self::$RackspaceRegion = getenv('RACKSPACE_REGION');
        if (!self::$RackspaceRegion) {
            self::$RackspaceRegion = 'dfw';
        }
        self::$RackspaceRegion = strtolower(self::$RackspaceRegion);

        // Prepare the job source
        self::Folder($Folder);
    }

    /**
     * Cleanup
     */
    public static function Complete() {
        Workers::Log(Workers::LOG_L_NOTICE, " Cleaning up", Workers::LOG_O_SHOWPID);
        unlink(self::$JobsFile);
    }

    /**
     * Prepare to execute our jobs
     *
     * @param Workers $Workers
     */
    public function Prepare($Workers = NULL) {

        if ($Workers) {
            parent::Prepare($Workers);
        }

        // Prepare CloudFiles
        $this->CloudFiles = new CloudFiles("https://identity.api.rackspacecloud.com/v1.1/", [
            "username" => self::$RackspaceUser,
            "apiKey" => self::$RackspaceApiKey,
            "region" => strtolower(self::$RackspaceRegion),
            "context" => self::$RackspaceFacing,
            "flavor" => "cloudservers",
            "provider" => "rackspace",
            "url" => "https://identity.api.rackspacecloud.com/v1.1/"
        ]);
        $this->CloudFiles->CacheCredentials(FALSE);

        $containerName = self::$Container;
        Workers::Log(Workers::LOG_L_WARN, " Checking container: {$containerName}");
        $container = $this->CloudFiles->ContainerInfo($containerName);
        if ($container) {
            foreach ($container as $cKey => $cVal) {
                if (is_scalar($cVal)) {
                    Workers::Log(Workers::LOG_L_WARN, "  {$cKey}: {$cVal}");
                }
            }
        } else {
            Workers::Log(Workers::LOG_L_WARN, "  not found");
        }
    }

    /**
     * Get a list of jobs for the worker to execute
     */
    public function GetJobs() {

        $Chunk = $this->WorkerID;
        $JobsFile = self::$JobsFile;
        $StartJobs = $Chunk * $this->JobsPerWorker;
        $EndJobs = $StartJobs + $this->JobsPerWorker;

        // Sed foibles
        if (!$StartJobs || $Chunk == 1) {
            $StartJobs = 1;
        }
        if ($EndJobs > 1) {
            $EndJobs -= 1;
        }

        $Alloc = "sed -n '{$StartJobs},{$EndJobs}p' {$JobsFile}";
        $Jobs = trim(shell_exec($Alloc));

        // Tell the parent that we're done with jobbing
        if (!strlen($Jobs)) {
            $this->Finished();
            $this->Jobs = array();
        } else {
            $this->Jobs = explode("\n", $Jobs);
            $NumJobs = sizeof($this->Jobs);
            Workers::Log(Workers::LOG_L_INFO, "   Got {$NumJobs} jobs", Workers::LOG_O_SHOWPID);
        }
    }

    /**
     * Execute a job
     *
     * @param array $Job
     */
    public function RunJob($Job) {
        $Job = stripslashes($Job);

        $file = ltrim($Job, '.');
        if (!$file) {
            return;
        }

        $mode = "local";

        $fileParts = explode(':', $file);
        if (sizeof($fileParts > 1)) {
            $cloudy = array_shift($fileParts);
            if ($cloudy == '~cf') {
                $mode = 'xcopy';
                $sourceContainer = array_shift($fileParts);
                $file = implode(':', $fileParts);
            }
        }

        $replace = preg_quote(self::$Folder);
        $destFile = preg_replace("`^{$replace}`", '', $file);
        $destPaths = array();
        if (self::$Prefix) {
            $destPaths[] = self::$Prefix;
        }
        $destPaths[] = $destFile;

        $destFilePath = CombinePaths($destPaths);
        $destFileParts = explode('/', $destFilePath);
        $destFileParts = array_map('rawurlencode', $destFileParts);
        $destFilePath = implode('/', $destFileParts);

        $maxTries = 5;
        $tries = 0;
        $success = FALSE;
        while ($tries < $maxTries) {
            $tries++;
            try {

                switch ($mode) {

                    // Local to remote
                    case 'local':

                        if (is_dir($Job)) {
                            return;
                        }

                        if (self::$Lower) {
                            $destFilePath = strtolower($destFilePath);
                        }
                        $this->CloudFiles->PutObject(self::$Container, $destFilePath, array($Job));
                        $success = TRUE;
                        break;

                    // Remote to remote
                    case 'xcopy':

                        if (self::$Lower) {
                            $destFilePath = strtolower($destFilePath);
                        }
                        $this->CloudFiles->CopyObject($sourceContainer, $file, self::$Container, $destFilePath);
                        $success = TRUE;
                        break;
                }
            } catch (Exception $Ex) {
                if ($tries == 1) {
                    Workers::Log(Workers::LOG_L_WARN, "   {$file} upload error...", Workers::LOG_O_SHOWPID);
                }

                Workers::Log(Workers::LOG_L_WARN, "   {$file} - retry {$tries}", Workers::LOG_O_SHOWPID);
                sleep(1);
            }
        }

        if ($success) {
            // Give a different message if we've been retrying
            if ($tries > 1) {
                Workers::Log(Workers::LOG_L_WARN, "     - success! {$destFilePath}", Workers::LOG_O_SHOWPID);
            } else {
                Workers::Log(Workers::LOG_L_NOTICE, "   {$destFilePath} ({$file})", Workers::LOG_O_SHOWPID);
            }

            // Move file to completed path if needed
            if (self::$Move) {
                $freplace = rtrim(self::$Folder, '/');
                $FileMove = str_replace($freplace, self::$Move, $Job);
                $FileMovePath = dirname($FileMove);
                @mkdir($FileMovePath, 0755, true);
                rename($Job, $FileMove);
            }

            return;
        } else {
            Workers::Log(Workers::LOG_L_WARN, "     - giving up after {$tries} attempts", Workers::LOG_O_SHOWPID);
            throw $Ex;
        }
    }

}
