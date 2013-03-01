<?php

require_once __DIR__.'/functions.core.php';
require_once __DIR__.'/functions.commandline.php';
require_once __DIR__.'/functions.errorhandler.php';

spl_autoload_register(function ($name) {
   $path = __DIR__.'/class.'.strtolower($name).'.php';
   if (file_exists($path))
      require_once $path;
});