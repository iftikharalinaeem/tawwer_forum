<?php
namespace Framework;

class ErrorException  extends \ErrorException {
   protected $context;
   
   public function __construct($message, $errno, $file, $line, $context) {
      parent::__construct($message, $errno, 0, $file, $line);
      $this->context = $context;
   }
   
   public function getContext() {
      return $this->context;
   }
}

function errorHandler($errno, $message, $file, $line, $context) {
   $reporting = error_reporting();
   
   // Ignore errors that are below the current error reporting level.
   if (($reporting & $errno) != $errno)
      return FALSE;
   
//   $backtrace = debug_backtrace();
   
//   if (($errono & (E_NOTICE | E_USER_NOTICE)) > 0 & function_exists('Trace')) {
//      $Tr = '';
//      $i = 0;
//      foreach ($Backtrace as $Info) {
//         if (!isset($Info['file']))
//            continue;
//         
//         $Tr .= "\n{$Info['file']} line {$Info['line']}.";
//         if ($i > 2)
//            break;
//         $i++;
//      }
//      Trace("$errstr{$Tr}", TRACE_NOTICE);
//      return FALSE;
//   }
   
   throw new ErrorException($message, $errno, $file, $line, $context);
}

set_error_handler('Framework\errorHandler', E_ALL);