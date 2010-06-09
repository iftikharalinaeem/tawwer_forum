#!/usr/bin/php
<?php

$GZip = TRUE;
$BackupFolder = '/backups/db/';
$TmpPathFormat = "/tmp/%s";
$FileFormat = "backup-%db-%date-%itr.sql";
$DateFormat = "Y-m-d-H_i";
$UserName = 'backup';
$Password = 'DIqwd27HJa';

if ($argc < 2) exit("requires a database name as the first parameter\n");
$DatabaseName = $argv[1];

$Date = date($DateFormat, time());
$TmpName = uniqid('database-backup-');
$TmpPath = sprintf($TmpPathFormat, $TmpName);

echo "database backup running [{$Date}] for [{$DatabaseName}]\n";

// Perform Data Check.
echo "  running database check... ";
$Output = false; $ReturnValue = 0;
$CheckCommand = "mysqlcheck --user={$UserName} --password={$Password} {$DatabaseName}";
exec($CheckCommand, $Output, $ReturnValue);

if ($ReturnValue > 0) {
   echo "\ndatabase check failed. mysqlcheck returned {$ReturnValue}\n";
   echo implode("\n",$Output)."\n";
   exit();
} else
   echo "[ OK ]\n";


// Perform Data Dump.
echo "  running database dump... ";
$Output = false; $ReturnValue = 0;
$DumpCommand = "mysqldump --extended-insert --no-create-db --user={$UserName} --password={$Password} {$DatabaseName} > {$TmpPath}";
exec($DumpCommand, $Output, $ReturnValue);

if ($ReturnValue > 0) {
   echo "\ndatabase backup failed. mysqldump returned {$ReturnValue}\n";
   echo implode("\n",$Output)."\n";
   exit();
} else
   echo "[ OK ]\n";

if (!file_exists($TmpPath)) exit("database backup failed. target file not created.\n");

$Iter = 0;
do {
   $Replacements = array(
      '%db'       => $DatabaseName,
      '%date'     => $Date,
      '%itr'      => $Iter
   );

   $TargetFile = str_replace(array_keys($Replacements),array_values($Replacements),$FileFormat);
   $TargetPath = $BackupFolder . $TargetFile;
   $Iter++;

} while (file_exists($TargetPath));
rename($TmpPath, $TargetPath);
echo "  database dump complete. plaintext ".(($GZip) ? 'temporarily ': '')."stored at {$TargetPath}\n";

if (!$GZip) return;
echo "  running compression... ";

$Output = false; $ReturnValue = 0;
$GzCommand = "gzip --best {$TargetPath}";
exec($GzCommand, $Output, $ReturnValue);

if ($ReturnValue > 0) {
   echo "\ndatabase compression failed. gzip returned {$ReturnValue}\n";
   echo implode("\n",$Output)."\n";
   exit();
} else
   echo "[ OK ]\n";

echo "  database compression complete. stored at {$TargetPath}.gz\n";

?>