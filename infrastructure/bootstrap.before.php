<?php if (!defined('APPLICATION')) exit();
   
define('PATH_CLIENTS', '/srv/clients');
$UserRoot = PATH_CLIENTS.DS.$_SERVER['HTTP_HOST'];

try {

   if (is_dir($UserRoot)) {
      define('PATH_LOCAL_ROOT', $UserRoot);

      $UserConfDir = PATH_LOCAL_ROOT.'/conf';
      if (is_dir($UserConfDir)) define('PATH_LOCAL_CONF', $UserConfDir);
      
      $UserPluginDir = PATH_LOCAL_ROOT.'/plugins';
      if (is_dir($UserPluginDir)) define('PATH_LOCAL_PLUGINS', $UserPluginDir);
      
      $UserCacheDir = PATH_LOCAL_ROOT.'/cache';
      if (is_dir($UserCacheDir)) define('PATH_LOCAL_CACHE', $UserCacheDir);
      
      $UserUploadsDir = PATH_LOCAL_ROOT.'/uploads';
      if (is_dir($UserUploadsDir)) define('PATH_LOCAL_UPLOADS', $UserUploadsDir);
   } else {
      throw new Exception();
   }
   
} catch (Exception $e) {}