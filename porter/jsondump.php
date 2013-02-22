<?php
/// Hey Peeps!, this file needs to be symlinked into the porter folder. Call it from there!

error_reporting(E_ALL); //E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
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
         $value = 'int';
      elseif (is_float ($value))
         $value = 'float';
      elseif (is_double($value))
         $value = 'double';
      elseif (preg_match('`\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)Z?`', $value))
         $value = 'datetime';
      else
         $value = 'varchar(255)';
      
      $defs[$name] = "$name $value";
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
   $columns = array_map(function($val) { return "`$val`"; }, array_keys($rows[0]));
   echo "insert `$table` (".implode(', ', $columns).") values\n";
   
   $first = true;
   foreach ($rows as $row) {
      if (!$first) {
         echo ",\n";
      } else {
         $first = false;
      }
      
      $values = array_map('mysql_real_escape_string', $row);
      
      echo "('".
         implode("','", $values).
         "')";
   }
   
   echo ";\n";
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

$data = json_decode(file_get_contents($path), true);
foreach ($data as $tablename => $rows) {
   writeTable($tablename, $rows);
}