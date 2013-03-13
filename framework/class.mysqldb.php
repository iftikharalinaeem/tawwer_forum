<?php

/**
 * A connection between Vanilla and MySQL.
 */
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
   
   public $px = 'gdn_';
   
   /// Methods ///
   
   public function __construct($host, $username, $password, $dbname, $port = null) {
      $dsn = array('dbname' => $dbname, 'host' => $host);
      if ($port)
         $dsn['port'] = $port;
      
      $this->pdo = new PDO('mysql:'.http_build_query($dsn, null, ';'), $username, $password);
      $this->pdo->query('set names utf8'); // send this statement outside our query function.
   }
   
   protected function columnDef($name, $def) {
      $result = "`$name` ".$this->parseType($def['type']);
      
      if (val('required', $def))
         $result .= ' not null';
      
      if (val('autoincrement', $def)) {
         $result .= ' auto_increment';
         $def['primary'] = true;
      }
      
      if (val('primary', $def)) {
         $result .= ' primary key';
      }
      return $result;
   }
   
   /**
    * Get the index definitions for a table.
    * 
    * @param string $table The name of the table
    * @return array An array in the form:
    * 
    *     array (
    *        index name => array('columns' => array('column'), 'type' => Db::INDEX_TYPE)
    *     )
    */
   public function indexDefinitions($table) {
      // Keep an internal cache for repeated calls to this function.
      static $cache = array();
      
      if (isset($cache[$table]))
         return $cache[$table];
      
      // Query the indexes from the database.
      $rawdata = $this->query("show indexes from `{$this->px}$table`");
      
      // Parse them into their correct place.
      $result = array();
      foreach ($rawdata as $row) {
         $name = $row['Key_name'];
         
         // Figure out the type.
         if (strcasecmp($name, 'PRIMARY') === 0)
            $type = Db::INDEX_PK;
         elseif ($row['Non_unique'] == 0)
            $type = Db::INDEX_UNIQUE;
         elseif (strcasecmp(substr($name, 0, 2), 'fk') === 0)
            $type = Db::INDEX_FK;
         else
            $type = Db::INDEX_IX;
         
         $result[$name]['columns'][] = $row['Column_name'];
         $result[$name]['type'] = $type;
      }
      $cache[$table] = $result;
      
      return $result;
   }
   
   /**
    * Define an index in the database.
    * 
    * Db->defineIndex() will check to see if the index already exists, and if it doesn't then it will create it.
    * 
    * @param string $table The name of the table that the index is on.
    * @param array|string $column The name(s) of the columns in the index.
    * @param string $type One of the Db::INDEX_* constants.
    * 
    * Db::INDEX_PK
    * : This index is the primary key
    * 
    * Db::INDEX_FK
    * : This index is a foreign key. Its column(s) point to the primary key of another table.
    * 
    * Db::INDEX_IX
    * : This is a regular index.
    * 
    * Db::INDEX_UNIQUE
    * : This is a unique index.
    * 
    * @param string $suffix By default the index will be named based on the column that it's on.  
    *    This suffix overrides that.
    * @return string The name of the index.
    */
   public function defineIndex($table, $column, $type, $suffix = null) {
      $columns = (array)$column;
      
      // Determine the name of the new index.
      if ($type === Db::INDEX_PK) {
         $name = 'PRIMARY';
      } else {
         $prefixes = array(Db::INDEX_FK => 'FK_', Db::INDEX_IX => 'IX_', Db::INDEX_UNIQUE => 'UX_');
         $px = val($type, $prefixes, 'IX_');
         if (!$suffix && $type != Db::INDEX_UNIQUE)
            $suffix = implode('', $columns);
         $name = "{$px}{$table}".($suffix ? "_{$suffix}" : '');
      }
      
      // Get the current definitions.
      $currentIndexes = $this->indexDefinitions($table);
      
      // See if we have to drop and/or add the index.
      $dropIndex = false;
      $addIndex = false;
      if (isset($currentIndexes[$name])) {
         $row = $currentIndexes[$name];
         if ($row['type'] !== $type || $row['columns'] != $columns) {
            // The name is the same, but the definition is different.
            $currentName = $name;
            $currentType = $row['type'];
            $dropIndex = true;
         }
      } else {
         // The index name doesn't exist so we know were adding a new one.
         $addIndex = true;
         
         // See if there is already an index related to these columns and type.
         foreach ($currentIndexes as $key => $row) {
            if ($row['type'] === $type && $row['columns'] == $columns) {
               // The index exists, but with a differnt name so we need to drop it.
               $currentName = $key;
               $currentType = $row['type'];
               $dropIndex = true;
            }
         }
      }
      
      // Now that we know what we have to do build the SQL and execute it.
      $sqls = array();
      $pxtable = $this->px.$table;
      
      if ($dropIndex) {
         if ($currentType === Db::INDEX_PK) {
            $sqls[] = "alter table `$pxtable` drop primary key";
         } else {
            $sqls[] = "alter table `$pxtable` drop index `$currentName`";
         }
      }
      
      if ($addIndex) {
         if ($type === Db::INDEX_PK) {
            $sqls[] = "alter table `$pxtable` add primary key ".bracketList($columns, '`');
         } else {
            $sqls[] = "create".
               ($type === Db::INDEX_UNIQUE ? ' unique' : '').
               " index `$name` on `$pxtable` ".bracketList($columns, '`');
         }
      }
      
      foreach ($sqls as $sql) {
         $this->query($sql, Db::QUERY_DEFINE);
      }
      
      return $name;
   }
   
   public function defineTable($table, $columns, $options = array()) {
      // Go through the table and build the indexes.
      $indexes = array();
      foreach ($columns as $name => $def) {
         $index = val('index', $def);
         if ($index) {
            // The column has one or more indexes on them.
            foreach ((array)$index as $typeString) {
               $parts = explode('.', $typeString, 2);
               $type = strtolower($parts[0]);
               $suffix = val(1, $parts);
               
               if ($type == Db::INDEX_PK)
                  $columns[$name]['required'] = true;
               
               // Save the index for later.
               if ($type == Db::INDEX_PK || $type == Db::INDEX_UNIQUE || $suffix) {
                  // There is only one index of this type.
                  $indexes[$typeString]['columns'][] = $name;
                  $indexes[$typeString]['type'] = $type;
                  $indexes[$typeString]['suffix'] = $suffix;
               } else {
                  // One columns, one index.
                  $indexes[] = array('columns' => $name, 'type' => $type);
               }
            }
         }
      }
      
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
         
         if (val('collate', $options)) {
            $sql .= "\n collate {$options['collate']}";
         }
         
         $this->query($sql, Db::QUERY_DEFINE);
      } else {
         // This is an alter table.
         $currentColumns = $currentDef['columns'];
         
         // Get the columns to add.
         $addColumns = array_diff_key($columns, $currentColumns);
         
         // Get the columns to alter.
         $alterColumns = array_intersect_key($columns, $currentColumns);
         $alter = array();
         foreach ($alterColumns as $name => $def) {
            $currentDef = $currentColumns[$name];
            if ($currentDef['type'] !== $this->parseType($def['type']) ||
               $currentDef['required'] != val('required', $def, false)) {
               
               // The column has changed.
               $alter[$name] = true;
            }
         }
         $alterColumns = array_intersect_key($alterColumns, $alter);
         
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
      
      // Now that the table has been defined we can add the indexes.
      foreach ($indexes as $def) {
         $this->defineIndex($table, $def['columns'], $def['type'], val('suffix', $def));
      }
   }
   
   public function delete($table, $where) {
      trigger_error(__CLASS__.'->'.__FUNCTION__.'() not implemented', E_USER_ERROR);
   }
   
   public function get($table, $where, $order = array(), $limit = false, $options = array()) {
      $sql = '';
      
      // Build the select clause.
      $sql .= "select *";
      
      // Build the from clause.
      $sql .= "\nfrom `$table`";
      
      // Build the where clause.
      $whereString = $this->whereString($where);
      if ($whereString)
         $sql .= "\nwhere ".$whereString;
      
      // Build the order.
      if (!empty($order)) {
         $orders = array();
         foreach ($order as $key => $value) {
            if (is_int($key)) {
               // This is just a column.
               $orders[] = "`$value`";
            } else {
               // This is a column with a direction.
               switch ($value) {
                  case OP_ASC:
                  case OP_DESC:
                     $orders[] = "`$key` $value";
                     break;
                  default:
                     trigger_error("Invalid sort direction '$value' for column '$key'.", E_USER_WARNING); 
               }
            }
         }
         
         $sql .= "\norder by ".implode(', ', $orders);
      }
      
      // Build the limit, offset.
      if ($limit) {
         if (is_numeric($limit))
            $sql .= "\nlimit $limit";
         elseif (is_array($limit)) {
            // The limit is in the form (limit, offset) or (limit, 'page' => page)
            if (isset($limit['page']))
               $offset = $limit[0] * ($limit['page'] - 1);
            else
               list($limit, $offset) = $limit;
            
            $sql .= "\nlimit $limit offset $offset";
         }
      }
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
   
   public function join(&$data, $on, $childcolumns = array(), $options = array()) {
      parent::join($data, $on, $childcolumns, $options);
   }
   
   /**
    * Execute a query on the database.
    * 
    * @param string $sql The sql query to execute.
    * @param string $type One of the Db::QUERY_* constants.
    * 
    * Db::QUERY_READ
    * : The query reads from the database.
    * 
    * Db::QUERY_WRITE
    * : The query writes to the database.
    * 
    * Db::QUERY_DEFINE
    * : The query alters the structure of the datbase.
    * 
    * @param array $options Additional options for the query.
    * 
    * Db::GET_UNBUFFERED
    * : Don't internally buffer the data when selecting from the database.
    * 
    * @return array|PDOStatement|bool The result of the query.
    * 
    * - array: When selecting from the database.
    * - PDOStatement: When performing an unbuffered query.
    * - int: When performing an update or an insert this will return the number of rows affected.
    * - false: When the query was not successful.
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
//            die($message);
            fwrite(STDERR, $sql);
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

      if (!$type) {
         debug_print_backtrace();
         trigger_error("Couldn't parse type $typeString", E_USER_ERROR);
      }

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
   
   /**
    * Build a where clause from a where array.
    * 
    * @param array $where
    */
   protected function whereString($where, $op = OP_AND) {
      static $map = array(OP_GT => '>', OP_GTE => '>=', OP_LT => '<', OP_LTE => '<=');
      $result = '';
      
      foreach ($where as $column => $value) {
         if ($result)
            $result .= ' and ';
         
         if (is_array($value)) {
            $op = array_shift($value);
            $rval = array_shift($value);
            
            switch ($op) {
               case OP_AND:
               case OP_OR:
                  $result .= '('.$this->whereString($rval, $op).')';
                  break;
               case OP_EQ:
                  if ($value === null)
                     $result .= "`$column` is null";
                  else
                     $result .= "`$column` = ".$this->pdo->quote($rval);
                  break;
               case OP_GT:
               case OP_GTE:
               case OP_LT:
               case OP_LTE:
                  $result .= "`$column` {$map[$op]} ".$this->pdo->quote($rval);
                  break;
               case OP_IN:
                  // Quote the in values.
                  $rval = array_map(array($this->pdo, 'quote'), (array)$rval);
                  $result .= "`$column` in (".implode(', ', $rval).')';
                  break;
               case OP_NE:
                  if ($value === null)
                     $result .= "`$column` is null";
                  else
                     $result .= "`$column` = ".$this->pdo->quote($rval);
                  break;
            }
         } else {
            // This is just an equality operator.
            if ($value === null)
               $result .= "`$column` is null";
            else
               $result .= "`$column` = ".$this->pdo->quote($value);
         }
      }
   }
}

/**
 * 
 * @param type $row
 * @param type $quote
 * @return type
 */
function bracketList($row, $quote = "'") {
   return "($quote".implode("$quote, $quote", $row)."$quote)";
}