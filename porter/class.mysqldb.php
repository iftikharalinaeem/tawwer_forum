<?php
/**
 * Base class for all database access.
 */
class Db {
   /// Constants ///
   
   const GET_UNBUFFERED = 'unbuffered';
   const INSERT_REPLACE = 'replace';
   const INSERT_IGNORE = 'ignore';
   const UPDATE_UPSERT = 'upsert';
   
   const MODE_EXEC = 'exec';
   const MODE_CAPTURE = 'capture';
   const MODE_ECHO = 'echo';
   
   const QUERY_DEFINE = 'define';
   const QUERY_READ = 'read';
   const QUERY_WRITE = 'write';
}

class MySqlDb extends Db {
   public $mode = Db::MODE_EXEC;
   
   /**
    * @var mysqli 
    */
//   public $mysqli;
   
   /**
    *
    * @var PDO
    */
   public $pdo;
   
   public $px = 'GDN_';
   
   /// Methods ///
   
   public function __construct($host, $username, $password, $dbname) {
//      $this->mysqli = new mysqli($host, $username, $password, $dbname);
//      $this->mysqli->set_charset('utf8');
      
      $this->pdo = new PDO("mysql:dbname=$dbname;host=$host", $username, $password);
      $this->pdo->query('set names utf8'); // send this statement outside our query function.
   }
   
   public function defineTable($table, $columns) {
      // Get the current definition.
      $currentDef = $this->tableDefinition($table);
      
      if (!$currentDef) {
         // The table doesn't exist so this is a create table.
         $parts = array();
         foreach ($columns as $name => $def) {
            $parts[] = $this->columnDef($name, $def);
         }
         
         $sql = "create table `{$this->px}$table` (\n  ".
            implode(",\n  ", $parts).
            "\n)";
         
         $this->query($sql, Db::QUERY_DEFINE);
      } else {
         // This is an alter table.
         $currentColumns = $currentDef['columns'];
         
         // Get the columns to add.
         $addColumns = array_diff_key($columns, $currentColumns);
         
         // Get the columns to alter.
         $alterColumns = array_intersect_key($columns, $currentColumns);
         foreach ($alterColumns as $name => $def) {
            if (val('primary', $def))
               $def['required'] = true;
            
            $currentDef = $currentColumns[$name];
            if ($currentDef['type'] !== $this->parseType($def['type']) ||
               $currentDef['required'] != val('required', $def, false)) {
               
               // The column has changed, continue.
               continue;
            }
            
            // The column has not changed.
            unset($alterColumns[$name]);
         }
         
         $parts = array();
         foreach ($addColumns as $name => $def) {
            $parts[] = 'add '.$this->columnDef($name, $def, true);
         }
         
         foreach ($alterColumns as $name => $def) {
            $parts[] = 'modify '.$this->columnDef($name, $def, true);
         }
         
         if (count($parts) > 0) {
            $sql = "alter table `{$this->px}$table` \n  ".
               implode(",\n  ", $parts);
         
            $this->query($sql, Db::QUERY_DEFINE);
         }
      }
   }
   
   protected function columnDef($name, $def) {
      $result = "`$name` ".$this->parseType($def['type']);
      
      if (val('required', $def))
         $result .= ' not null';
      
      if (val('autoincrement', $def))
         $result .= ' auto_increment';
      
      if (val('primary', $def)) {
         $result .= ' primary key';
      }
      return $result;
   }
   
   public function delete($table, $where) {
      trigger_error(__CLASS__.'->'.__FUNCTION__.'() not implemented', E_USER_ERROR);
   }
   
   public function get($table, $where, $options = array()) {
      trigger_error(__CLASS__.'->'.__FUNCTION__.'() not implemented', E_USER_ERROR);
   }
   
   public function insert($table, $row, $options = array()) {
      $result = $this->insertMulti($table, array($row), $options);
      if ($result) {
         $result = $this->pdo->lastInsertId();
//         $result = $this->mysqli->insert_id;
      }
      return $result;
   }
   
   public function insertMulti($table, $rows, $options = array()) {
      if (count($rows) == 0)
         return;
      
      reset($rows);
      $columns = array_keys(current($rows));
      
      // Build the insert statement.
      if (val(Db::INSERT_REPLACE, $options)) {
         $sql = 'replace ';
      } else {
         $sql = 'insert '.valif(Db::INSERT_IGNORE, $options, 'ignore ');
      }
      
      $sql .= "`{$this->px}$table`\n";
      
      $sql .= bracketList($columns, '`')."\n".
         "values\n";
      
      $first = true;
      foreach ($rows as $row) {
         if ($first)
            $first = false;
         else
            $sql .= ",\n";
         
         // Escape the values.
         $row = array_map(array($this->pdo, 'quote'), $row);
//         $row = array_map(array($this->mysqli, 'real_escape_string'), $row);
         $sql .= bracketList($row, '');
      }
     
      $result = $this->query($sql, Db::QUERY_WRITE, $options);
      if (is_a($result, 'PDOStatement')) {
         $result = $result->rowCount();
      }
      return $result;
   }
   
   /**
    * 
    * @param type $sql
    * @param type $type
    * @param type $options
    * @return boolean|\MySqliResultIterator
    */
   protected function query($sql, $type = Db::QUERY_READ, $options = array()) {
      $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, !val(Db::GET_UNBUFFERED, $options, false));
      
      if ($this->mode === Db::MODE_ECHO && $type != Db::QUERY_READ) {
         echo rtrim($sql, ';').";\n\n";
         return true;
      } else {
//         $result = $this->mysqli->query($sql, $resultmode);
         $result = $this->pdo->query($sql);
         
         if (!$result) {
            list($code, $dbCode, $message) = $this->pdo->errorInfo();
            die($message);
            trigger_error($message, E_USER_ERROR);
//            trigger_error($this->mysqli->error."\n\n".$sql, E_USER_ERROR);
         }
      }
      
      if ($type == Db::QUERY_READ) {
         $result->setFetchMode(PDO::FETCH_ASSOC);
         if (!val(Db::GET_UNBUFFERED, $options)) {
            $result = $result->fetchAll();
//            $result = $result->fetch_all(MYSQLI_ASSOC);
         }
      }
      
      return $result;
   }
   
   /**
    * Parse a column type string and return it in a way that is suitible for a create/alter table statement.
    * 
    * @param string $typeString
    * @return string
    */
   public function parseType($typeString) {
      $type = null;

      if (substr($type, 0, 4) === 'enum') {
         // This is an enum which will come in as an array.
         if (preg_match_all("`'([^']+)'`", $typeString, $matches))
            $type = $matches[1];
      } else {
         if (preg_match('`([a-z]+)\s*(?:\((\d+(?:\s*,\s*\d+)*)\))?\s*(unsigned)?`', $typeString, $matches)) {
   //         var_dump($matches);
            $str = $matches[1];
            $length = @$matches[2];
            $unsigned = @$matches[3];
            
            if (substr($str, 0, 1) == 'u') {
               $unsigned = true;
               $str = substr($str, 1);
            }

            // Remove the length from types without real lengths.
            if (in_array($str, array('tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'float', 'double'))) {
               $length = null;
            }

            $type = $str;
            if ($length) {
               $length = str_replace(' ', '', $length);
               $type .= "($length)";
            }
            if ($unsigned)
               $type .= ' unsigned';
         }
      }

      if (!$type)
         trigger_error("Couldn't parse type $typeString", E_USER_ERROR);

      return $type;
   }
   
   /**
    * Return the definition for a table.
    * 
    * @param string $table The name of the table.
    * @return array
    */
   public function tableDefinition($table) {
      if (!$this->tableExists($table))
         return null;
      
      $result = array();
      
      // Load all of the column definitions from the table.
      $coldata = $this->query("describe `{$this->px}$table`");
      $columns = array();
      foreach ($coldata as $row) {
         $coldef = array(
            'type' => $this->parseType($row['Type']),
            'required' => !forceBool($row['Null'])
            );
         $columns[$row['Field']] = $coldef;
      }
      
      $result['columns'] = $columns;
      
      return $result;
   }
   
   public function tableExists($table) {
      $sql = "show tables like '{$this->px}$table'";
      $data = $this->query($sql, Db::QUERY_READ);
      return (count($data) > 0);
   }
   
   public function update($table, $row, $where, $options = array()) {
      trigger_error(__CLASS__.'->'.__FUNCTION__.'() not implemented', E_USER_ERROR);
   }
}

class MySqliResultIterator implements Iterator {
   protected $current = false;
   
   
   public $position = -1;
   
   /**
    * @var mysqli_result
    */
   public $result;
   
   public function __construct($result) {
      $this->result = $result;
      $this->position = -1;
      $this->current = false;
   }
   
   public function current() {
      if ($this->current === false) {
         // We are before the beginning of the dataset.
         $this->current = $this->result->fetch_assoc();
         if ($this->current)
            $this->position++;
      }
      return $this->current;
   }
   
   public function key() {
      return $this->position;
   }
   
   public function next() {
      $this->current = $this->result->fetch_assoc();
      if ($this->current)
         $this->position++;
   }
   
   public function rewind() {
      $this->result->data_seek(0);
      $this->current = false;
      $this->position = -1;
   }
   
   public function valid() {
      return $this->current !== null;
   }
}

function bracketList($row, $quote = "'") {
   return "($quote".implode("$quote, $quote", $row)."$quote)";
}

function addBackticks($str) {
   return "`$str`";
}