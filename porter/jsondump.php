#!/usr/bin/env php
<?php
/// Hey Peeps!, this file needs to be symlinked into the porter folder. Call it from there!
/**
 * Command to run:
 *    php jsondump.php file.json
 *
 * Outputs SQL to create MySQL tables of data.
 *
 * Second optional parameter 'type' does special JSON parsing per platform. Example:
 *    php jsondump.php file.json moot
 */

error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
ini_set('display_errors', 'on');
ini_set('track_errors', 1);

function writeTable($tablename, $rows) {
   if (count($rows) == 0)
      return;
   
   writeTableDef($tablename, $rows[0]);
   writeRows($tablename, $rows);
}

function writeTableDef($tablename, $row) {
   $defs = array();
   
   // First we need to guess row types.
   foreach ($row as $name => &$value) {
      if (is_bool($value))
         $value = 'tinyint';
      elseif (is_int($value))
         $value = 'bigint'; // int kills unix timestamps in 64bit
      elseif (is_float ($value))
         $value = 'float';
      elseif (is_double($value))
         $value = 'double';
      elseif (preg_match('`\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)Z?`', $value))
         $value = 'datetime';
      elseif (in_array(strtolower($name), array('body','content')))
         $value = 'text';
      else
         $value = 'varchar(255)';
      
      $defs[$name] = "`$name` $value";
   }
   
   $result = '';

   // Drop the table.
   $result .= "drop table if exists `$tablename`;\n";

   // Create the table.
   $result .= "create table `$tablename` (\n".implode(",\n", $defs).");\n";
   
   echo $result;
}

function writeRows($table, $rows) {
   // Write the insert statement.
   $columns = array_keys($rows[0]);
   $icolumns = array_map(function($val) { return "`$val`"; }, $columns);
   echo "insert `$table` (".implode(', ', $icolumns).") values\n";
   
   $first = true;
   foreach ($rows as $row) {
      if (!$first) {
         echo ",\n";
      } else {
         $first = false;
      }
      
      $values = array_fill_keys($columns, '');
      
      foreach ($values as $key => $value) {
         if (array_key_exists($key, $row))
            $values[$key] = $row[$key];
      }
      
      $values = array_map('mysql_real_escape_string', $values);
      
      echo "('".
         implode("','", $values).
         "')";
   }
   
   echo ";\n";
}

function writeError() {
   switch (json_last_error()) {
      case JSON_ERROR_NONE:
         echo 'No errors';
         break;
      case JSON_ERROR_DEPTH:
         echo 'Maximum stack depth exceeded';
         break;
      case JSON_ERROR_STATE_MISMATCH:
         echo 'Underflow or the modes mismatch';
         break;
      case JSON_ERROR_CTRL_CHAR:
         echo 'Unexpected control character found';
         break;
      case JSON_ERROR_SYNTAX:
         echo 'Syntax error, malformed JSON';
         break;
      case JSON_ERROR_UTF8:
         echo 'Malformed UTF-8 characters, possibly incorrectly encoded';
         break;
      default:
         echo 'Unknown error';
         break;
   }

   echo PHP_EOL;
}

function parseJson($json) {
   $data = json_decode($json, true);
   if ($data === null) {
      echo "Invalid JSON: ";
      writeError();
      die();
   }

   foreach ($data as $tablename => $rows) {
      writeTable($tablename, $rows);
   }
}

function parseMoot($moot) {
   // Moot scraper produces actually produces multiple JSON outputs in a single file
   // One output per line, with commas between
   $moot = str_replace("  {", "{",$moot); // moot scraper gets spacey sometimes
   $moot = explode("\n{\"path",$moot);
   $json = false;
   foreach ($moot as $key => $row) {
      // Skip on the first run so we can wrap the whole thing at the end
      if ($json === false) {
         $json = '"'.$key.'":'.$row;
      } else {
         $json .= "\n".'"'.$key.'":{"path'.$row;
      }
   }
   unset($moot);
   //print_r('{'.$json.'}'); die();
   $data = json_decode('{'.$json.'}', true);

   // Be helpful if it chokes on invalid JSON
   if ($data === null) {
      echo "Invalid JSON: ";
      writeError();
      die();
   }

   // Write moot tables
   writeTableDef('channel', array('title'=>'s', 'category'=>'s'));
   writeTableDef('discussion', array('author'=>'s', 'date'=>date('U'), 'body'=>'s', 'key'=>'s', 'title'=>'s', 'category'=>'s'));
   writeTableDef('comment', array('author'=>'s', 'date'=>date('U'), 'body' => 'string', 'key'=>'s'));

   // Write tables per-record
   foreach ($data as $discussion) {
      // Category gleaned from 'path'
      $path = explode('#', $discussion['path']);
      $category = trim($path[0], '/');

      // OP is 'seed'
      $discussion['seed']['category'] = $category;
      $discussion['seed']['body'] = implode("\n\n", $discussion['seed']['body']);

      // Channels & discussions have the same data structure basically
      if (!$discussion['seed']['body'] && !$discussion['seed']['key'] && !count($discussion['replies'])) {
         // We found a channel
         unset($discussion['seed']['author']);
         unset($discussion['seed']['date']);
         unset($discussion['seed']['body']);
         unset($discussion['seed']['key']);
         writeRows('channel', array($discussion['seed']));
      } else {
         // Really is a discussion
         writeRows('discussion', array($discussion['seed']));
      }

      // Comments are 'replies'
      if (count($discussion['replies'])) {
         foreach ($discussion['replies'] as &$reply) {
            $reply['key'] = $discussion['seed']['key'];
            $reply['body'] = implode("\n\n", $reply['body']);
         }
         writeRows('comment', $discussion['replies']);
      }
   }
}

if ($argc < 2) {
   echo 'php '.basename(__FILE__)." path\n";
   die();
}

$path = $argv[1];
if (!file_exists($path)) {
   echo "File not found: $path\n";
   die();
}

$contents = trim(file_get_contents($path));

if (isset($argv[2])) {
   switch ($argv[2]) {
      case 'moot':
         parseMoot($contents);
         break;
      default:
         parseJson($contents);
         break;
   }
} else {
   parseJson($contents);
}