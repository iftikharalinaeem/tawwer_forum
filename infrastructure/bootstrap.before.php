<?php if (!defined('APPLICATION')) exit();

define('PATH_CLIENTS', '/srv/clients');
if (is_array($_SERVER) && array_key_exists('HTTP_HOST', $_SERVER))
   $FolderName = $_SERVER['HTTP_HOST'];

if (!isset($FolderName))
   throw new Exception("Could not read hostname from webserver environment.");

// Stop chopping with this many elements remaining
$MaxTrimDepth = 2;
$Matched = FALSE;
$CompressionFactor = 0;

$ExpandedFolderName = explode('.', $FolderName);

do {

   $CompressedFolderName = implode('.', $ExpandedFolderName);
   $UserRoot = PATH_CLIENTS.'/'.$CompressedFolderName;

   if (is_dir($UserRoot)) {
      $UserRoot = realpath($UserRoot);
      $ClientFolderName = @array_pop(explode("/", $UserRoot));
      define('PATH_LOCAL_ROOT', $UserRoot);

      $UserConfDir = PATH_LOCAL_ROOT.'/conf';
      if (is_dir($UserConfDir)) define('PATH_LOCAL_CONF', $UserConfDir);

      $UserPluginDir = PATH_LOCAL_ROOT.'/plugins';
      if (is_dir($UserPluginDir)) define('PATH_LOCAL_PLUGINS', $UserPluginDir);

      $UserCacheDir = PATH_LOCAL_ROOT.'/cache';
      if (is_dir($UserCacheDir)) define('PATH_LOCAL_CACHE', $UserCacheDir);

      $UserUploadsDir = PATH_LOCAL_ROOT.'/uploads';
      if (is_dir($UserUploadsDir)) define('PATH_LOCAL_UPLOADS', $UserUploadsDir);

      define('FORCE_CACHE_PREFIX', $ClientFolderName);
      $Matched = TRUE;
      break;
   }

   // Trim
   array_shift($ExpandedFolderName);

} while (sizeof($ExpandedFolderName) >= $MaxTrimDepth);

if (!$Matched)
   header("Status: 404 Not Found", TRUE, 404);
