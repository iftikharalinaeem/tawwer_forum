<?php
/// Hey Peeps!, Make sure functions.commandline.php is symlinked into this folder.

error_reporting(E_ALL); //E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
ini_set('display_errors', 'on');
ini_set('track_errors', 1);

require_once __DIR__.'/../framework/bootstrap.php';

header('Content-Type: text/plain; charset=UTF-8');

define('BUFFER_SIZE', 100);

function pgConnect($host, $dbname, $username = null, $password = null, $port = 5432) {
   $connectionString = "host=$host port=$port dbname=$dbname";
   
   if ($username)
      $connectionString .= " user=$username";
   
   if ($password)
      $connectionString .= " password=$password";
   $pg = pg_connect($connectionString);
   return $pg;
}

function pgDefineTables($pg, &$schemas = null) {
   if (!$schemas)
      $schemas = array();
   
   $Sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public';";

   $Result = pg_query($Sql);
   $Rows = pg_fetch_all($Result);
   pg_free_result($Result);

   $ImportTables = array();

   foreach ($Rows as $Row) {
      $tablename = $Row['table_name'];
      
      $defSql = pgDefineTable($pg, $tablename, $schema);
      $schemas[$tablename] = $schema;
      
      echo $defSql."\n";
   }
}

function pgDefineTable($pg, $tablename, &$schema = null) {
   $TypeMaps = array("character varying" => 'varchar', 'timestamp without time zone' => 'datetime', 'bytea' => 'text');
   
   $Sql = "SELECT column_name, data_type, character_maximum_length FROM information_schema.columns WHERE table_name ='$tablename' order by ordinal_position;";
   pg_send_query($pg, $Sql);
   $Result = pg_get_result($pg);
   $Error = pg_result_error($Result);
   if ($Error) {
      echo "$Sql\n$Error";
      return;
   }
   $Data = pg_fetch_all($Result);
   
   $Columns = array();
   $ByteColumns = array();
   foreach ($Data as $Row) {
      if ($Row['data_type'] == 'bytea') {
         $ByteColumns[$Row['column_name']] = $Row;
      } else {
         $Columns[$Row['column_name']] = $Row;
      }
   }
   
   $schema = array(
      'columns' => $Columns,
      'bytecolumns' => $ByteColumns
      );
      
   // Loop through the columns and buld up a tabledef.
   $Defs = array();
   foreach ($Columns as $ColumnName => $Info) {
      $ColumnName = trim($ColumnName);
      if (!$ColumnName) {
         continue;
      }

      $Type = $Info['data_type'];
      if (isset($TypeMaps[$Type]))
         $Type = $TypeMaps[$Type];

      if ($Info['character_maximum_length']) {
         $Defs[$ColumnName] = "$ColumnName $Type({$Info['character_maximum_length']})";
      } else
         $Defs[$ColumnName] = $ColumnName.' '.$Type;
   }
   
   $result = '';

   // Drop the table.
   $result .= "drop table if exists `$tablename`;\n";

   // Create the table.
   $result .= "create table `$tablename` (\n".implode(",\n", $Defs).");\n";
   
   // Show binary columns.
   if (count($ByteColumns)) {
      $result .= '-- bytea columns: '.implode(', ', array_keys($ByteColumns));
   }
   return $result;
}

function pgDumpTables($pg, $schemas) {
   foreach ($schemas as $tablename => $schema) {
      pgDumpTable($tablename, $schema);
   }
}

function pgDumpTable($table, $schema) {
   $columns = $schema['columns'];
   
   $sql = "select * from $table";
   $r = pg_query($sql);
   
   if (!pg_num_rows($r)) {
      pg_free_result($r);
      return;
   }
   
   // Write the insert statements.
   $rows = array();
   while ($row = pg_fetch_assoc($r)) {
      $row = array_intersect_key($row, $columns);
      $rows[] = $row;
      
      if (count($rows) > BUFFER_SIZE) {
         writeRows($table, $rows);
         $rows = array();
      }
   }
   
   if (count($rows) > 0)
      writeRows($table, $rows);
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
      
      $values = array_map('mysql_escape_string', $row);
      
      echo "('".
         implode("','", $values).
         "')";
   }
   
   echo ";\n";
}

$options = array(
   'host' => array('Host to connect to (IP address or hostname).', 'default' => '127.0.0.1'),
   'dbname' => array('The name of the database.'),
   'user' => array('The username of the database.', 'default' => ''),
   'password' => array('The password to use when connecting to the server.', 'default' => ''),
   'help' => array('Show help.')
);

// Grab the options.
$opts = ParseCommandLine('pgdump', $options);

$pg = pgConnect($opts['host'], $opts['dbname'], $opts['user'], $opts['password']);
pgDefineTables($pg, $schemas);
pgDumpTables($pg, $schemas);