<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cloud\Scripts;

/**
 * Script for syncing vanilla/vanilla-cloud to vanilla/vanilla.
 */
class OssSyncScript {

    const VERSION = "0.0.1";
    const OSS_ORIGIN = "vanilla-oss";

    private $cwd;

    /** @var SimpleScriptLogger */
    private $logger;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->cwd = getcwd();
        $this->logger = new SimpleScriptLogger();
    }

    const SYNC_EXCLUDE_LIST = [
        // Not really paths.
        ".",
        "..",
        ".git",

        // Cloud exclusive. SHOULD NEVER SYNC.
        "cloud",

        // Vendors. Should already be done by .gitignore, but just in case.
        "vendor",
        "node_modules",

        // Actually different and maintained separately.
        ".circleci",
        "phpunit.xml.dist",
        "README.md",
    ];

    /**
     * Run the command.
     */
    public function run() {
        $this->logger->title("Vanilla OSS SyncTool");
        $this->logger->info("Version: ".self::VERSION);

        // Switch to root directory.
        $this->shellOrFail("cd ".$this->getRootDir());
        $this->gitIntegrityCheck();
        $this->ensureOriginCreated();
        $this->createBranch();
    }

    /**
     * Cleanup function for the script.
     */
    public function cleanup() {
        $this->shellOrFail("cd ".$this->cwd);

        // Make sure we switch back to master branch.
        system("git checkout master");
    }

    /**
     * Get the root directory of vanilla.
     *
     * @return string
     */
    private function getRootDir(): string {
        return realpath(__DIR__."/../..");
    }

    /**
     * Validate that there the git status is clean.
     *
     * Exits otherwise.
     */
    private function gitIntegrityCheck() {
        $this->logger->title("Validating integrity of the git repo");
        $gitStatus = shell_exec("git status");
        $isDirty = strpos($gitStatus, "working tree clean") !== false;
        if ($isDirty) {
            $this->logger->success("Working tree is clean.");
        } else {
            $this->logger->error("Git working tree is dirty. Unable to proceed.", 2);
            $this->logger->info($gitStatus);
            die(1);
        }
    }

    /**
     * Ensure that our remote origin for vanilla/vanilla is created.
     */
    private function ensureOriginCreated() {
        $OSS_ORIGIN = self::OSS_ORIGIN;
        $this->logger->title("Validating Git Origins");

        $existingOrigins = shell_exec("git remote -v");
        $hasOrigin = strpos($existingOrigins, $OSS_ORIGIN) !== false;

        if ($hasOrigin) {
            $this->logger->info("Found existing remote origin $OSS_ORIGIN");
        } else {
            $this->logger->info("Could not find existing remote origin $OSS_ORIGIN. Creating it now.", [SimpleScriptLogger::CONTEXT_LINE_COUNT => 2]);
            $this->shellOrFail("git remote add $OSS_ORIGIN git@github.com:vanilla/vanilla.git");
        }
    }

    /**
     * Create a new branch with the synced changes.
     */
    private function createBranch() {
        $OSS_ORIGIN = self::OSS_ORIGIN;
        $currentTime = new \DateTime();
        $dateStamp = $currentTime->format("Y-m-d");
        $timeInt = $currentTime->getTimestamp();
        $branchName = "sync/$dateStamp-$timeInt";

        $this->logger->title("Ensuring remote branches are up to date");
        $this->shellOrFail("git fetch --all");

        $this->logger->title("Creating New Sync Branch");
        $this->logger->info("Branch name will be: $branchName", [SimpleScriptLogger::CONTEXT_LINE_COUNT => 2]);

        // Gather all the directories to sync.
        $pathSpec = $this->gatherPathSpecToSync();

        $this->shellOrFail("git checkout -b $branchName $OSS_ORIGIN/master");
        $this->shellOrFail("git checkout master -- $pathSpec");
        $this->shellOrFail("git add . && git commit -m \"Syncing files from vanilla-cloud.\"");
        $this->shellOrFail("git push origin $branchName");
    }

    /**
     * Get the pathspec to pass for git of the files to copy.
     *
     * @return string
     */
    private function gatherPathSpecToSync(): string {
        $this->logger->title("Gathering paths to sync");
        $this->logger->info("Switching to latest master branch.");
        $this->shellOrFail("git checkout master");
        $this->shellOrFail("git pull");
        $this->gitIntegrityCheck();

        $paths = scandir($this->getRootDir());
        $allowedPaths = [];
        $excludedPaths = [];
        foreach ($paths as $path) {
            if (!in_array($path, self::SYNC_EXCLUDE_LIST)) {
                $allowedPaths[] = $path;
            } elseif ($path !== "." && $path !== "..") {
                $excludedPaths[] = $path;
            }
        }

        $this->logger->alert("The following paths will be synced:", 1);
        $this->logger->success(implode("\n", $allowedPaths), [SimpleScriptLogger::CONTEXT_LINE_COUNT => 2]);
        $this->logger->alert("The following paths will be excluded:");
        $this->logger->error(implode("\n", $excludedPaths), [SimpleScriptLogger::CONTEXT_LINE_COUNT => 2]);
        $this->logger->promptContinue("Do they look correct?");

        $pathSpec = implode(" ", $allowedPaths);
        return $pathSpec;
    }

    /**
     * Run a shell comamnd or exit if it fails.
     *
     * @param string $command
     */
    private function shellOrFail(string $command) {
        system($command, $result);
        if ($result !== 0) {
            $this->logger->error("an error was encountered while running", 1);
            die($result);
        }
        echo "\n";
    }
}
