#!/usr/bin/env php
<?php

namespace Vanilla\Scripts;

PathsAndBranches::initCwd();
$rootDir = realpath(__DIR__."/../..");

Logger::title("Vanilla OSS SyncTool");
Logger::log("Version: v0.0.1");

// Switch to root.
shellOrFail("cd $rootDir");
gitIntegrityCheck();
ensureOriginCreated();
createBranch();

function gitIntegrityCheck() {
    return;


    Logger::title("Validating integrity of the git repo");
    $gitStatus = shell_exec("git status");
    $isDirty = strpos($gitStatus, "working tree clean") !== false;
    if ($isDirty) {
        Logger::success("Working tree is clean.");
    } else {
        Logger::error("Git working tree is dirty. Unable to proceed.", 2);
        Logger::log($gitStatus);
        die(1);
    }
}

function ensureOriginCreated() {
    $OSS_ORIGIN = PathsAndBranches::OSS_ORIGIN;
    Logger::title("Validating Git Origins");

    $existingOrigins = shell_exec("git remote -v");
    $hasOrigin = strpos($existingOrigins, $OSS_ORIGIN) !== false;

    if ($hasOrigin) {
        Logger::log("Found existing remote origin $OSS_ORIGIN");
    } else {
        Logger::log("Could not find existing remote origin $OSS_ORIGIN. Creating it now.", 2);
        shellOrFail("git remote add $OSS_ORIGIN git@github.com:vanilla/vanilla.git");
    }
}

function createBranch() {
    $OSS_ORIGIN = PathsAndBranches::OSS_ORIGIN;
    $currentTime = new \DateTime();
    $dateStamp = $currentTime->format("Y-m-d");
    $timeInt = $currentTime->getTimestamp();
    $branchName = "sync/$dateStamp-$timeInt";

    Logger::title("Ensuring remote branches are up to date");
    shellOrFail("git fetch --all");

    Logger::title("Creating New Sync Branch");
    Logger::log("Branch name will be: $branchName", 2);

    // Gather all the directories to sync.
    $pathSpec = gatherPathSpecToSync();

    shellOrFail("git checkout -b $branchName $OSS_ORIGIN/master");
    shellOrFail("git checkout master -- $pathSpec");
    shellOrFail("git add . && git commit -m \"Syncing files from vanilla-cloud.\"");
    shellOrFail("git push origin $branchName");
}

function gatherPathSpecToSync(): string {
    Logger::title("Gathering paths to sync");
    Logger::log("Switching to latest master branch.");
    shellOrFail("git checkout master");
    shellOrFail("git pull");
    gitIntegrityCheck();

    $paths = scandir(PathsAndBranches::getRootDir());
    $allowedPaths = [];
    $excludedPaths = [];
    foreach ($paths as $path) {
        if (!in_array($path, PathsAndBranches::DISALLOWED_PATHS)) {
            $allowedPaths[] = $path;
        } else if ($path !== "." && $path !== "..") {
            $excludedPaths[] = $path;
        }
    }

    Logger::warn("The following paths will be synced:", 1);
    Logger::success(implode("\n", $allowedPaths), 2);
    Logger::warn("The following paths will be excluded:");
    Logger::error(implode("\n", $excludedPaths), 2);
    Logger::promptContinue("Do they look correct?");

    $pathSpec = implode(" ", $allowedPaths);
    return $pathSpec;
}

function shellOrFail(string $command) {
    system($command, $result);
    if ($result !== 0) {
        Logger::error("an error was encountered while running", 1);
        die($result);
    }
    echo "\n";
}

class PathsAndBranches {

    const DISALLOWED_PATHS = [
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
    ];
    const OSS_ORIGIN = "vanilla-oss";

    private static $cwd;

    public static function getRootDir(): string {
        return realpath(__DIR__."/../..");
    }

    public static function initCwd() {
        self::$cwd = getcwd();
    }

    public static function restoreCwd() {
        shellOrFail("cd ".self::$cwd);
    }
}

class Logger {
    const RED = "0;31";
    const GREEN = "0;32";
    const YELLOW = "1;33";
    const PURPLE = "1;35";
    const CYAN = "0;36";

    public static function log(string $text, int $countNewLines = 1, ?string $escapeSequence = null) {
        $newlines = "";
        for ($i = 0; $i < $countNewLines; $i++) {
            $newlines .= "\n";
        }
        $result = "$text$newlines";

        if ($escapeSequence !== null) {
            $result = "\033[${escapeSequence}m${result}\033[0m";
        }
        echo $result;
    }

    public static function success(string $text, int $countNewLines = 1) {
        self::log($text, $countNewLines, self::GREEN);
    }

    public static function error(string $text, int $countNewLines = 1) {
        self::log($text, $countNewLines, self::RED);
    }

    public static function warn(string $text, int $countNewLines = 1) {
        self::log($text, $countNewLines, self::YELLOW);
    }

    public static function title(string $title, int $countNewLines = 1, ?string $escapeSequence = self::CYAN) {
        self::log("\n======  $title  =======", $countNewLines, $escapeSequence);
    }

    public static function promptContinue(string $prompt) {
        $result = readline($prompt . " (y\\n): ");
        if (strpos(strtolower($result), "y") === false) {
            self::log("Existing.");
            die(1);
        }
    }
}

register_shutdown_function(function () {
    PathsAndBranches::restoreCwd();
    // Switch branch back to master.
    shellOrFail("git checkout master");
});