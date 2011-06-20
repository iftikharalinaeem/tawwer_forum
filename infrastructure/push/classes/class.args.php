<?php

/**
 * This file is part of Push.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010, Tim Gunter 
 */

class Args {
   public $flags;
   public $args;
   
   public function __construct() {
      $this->flags = array();
      $this->args  = array();
   
      $argv = $GLOBALS['argv'];
      array_shift($argv);
   
      for ($i = 0; $i < count($argv); $i++) {
          $str = $argv[$i];
   
          // --foo
          if (strlen($str) > 2 && substr($str, 0, 2) == '--') {
              $str = substr($str, 2);
              $parts = explode('=', $str);
              $this->flags[$parts[0]] = true;
   
              // Does not have an =, so choose the next arg as its value
              if (count($parts) == 1 && isset($argv[$i + 1]) && preg_match('/^--?.+/', $argv[$i + 1]) == 0) {
                  $this->flags[$parts[0]] = $argv[$i + 1];
              }
              elseif(count($parts) == 2) {// Has a =, so pick the second piece
                  $this->flags[$parts[0]] = $parts[1];
              }
          }
          elseif (strlen($str) == 2 && $str[0] == '-') {// -a
              $this->flags[$str[1]] = true;
              if (isset($argv[$i + 1]) && preg_match('/^--?.+/', $argv[$i + 1]) == 0)
                  $this->flags[$str[1]] = $argv[$i + 1];
          }
          elseif (strlen($str) > 1 && $str[0] == '-') {// -abcdef
              for($j = 1; $j < strlen($str); $j++)
                  $this->flags[$str[$j]] = true;
          }
      }
   
      for($i = count($argv) - 1; $i >= 0; $i--) {
          if (preg_match('/^--?.+/', $argv[$i]) == 0)
              $this->args[] = $argv[$i];
          else
              break;
      }
   
      $this->args = array_reverse($this->args);
   }
   
   public function Flag($name) {
      return isset($this->flags[$name]) ? $this->flags[$name] : false;
   }
}
