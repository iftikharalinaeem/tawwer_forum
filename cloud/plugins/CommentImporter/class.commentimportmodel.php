<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class CommentImportModel {
   /// Properties ///
   public $BufferSize = 250;
   
   public $Path;
   
   public $Source = 'Comment Importer';
   
   public $RunQueries = TRUE;
   
   /// Methods ///
   
   public function counts() {
      $discussionModel = new DiscussionModel();
      
      $discussionModel->counts('CountComments');
      $discussionModel->counts('FirstCommentID');
      $discussionModel->counts('LastCommentID');
      $discussionModel->counts('DateLastComment');
      $discussionModel->counts('LastCommentUserID');
      
      $defaultRoleIDs = c('Garden.Registration.DefaultRoles');
      if (is_array($defaultRoleIDs)) {
         $roleID = array_pop($defaultRoleIDs);
         $sql = "insert GDN_UserRole (
               UserID,
               RoleID
            )	
            select
               u.UserID,
               $roleID as RoleID
            from GDN_User u
            left join GDN_UserRole ur
               on u.UserID = ur.UserID
            where ur.RoleID is NULL
               and u.Source = '_source_'";
         $this->query($sql);
      }
   }
   
   public function defineTables() { }
   
   public function insert($table, $row = NULL) {
      static $lastTable = NULL;
      static $rows = [];
      
      if (isset($row['Attributes']) && is_array($row['Attributes']))
         $row['Attributes'] = dbencode($row['Attributes']);
      
      
      if ($table === NULL) {
         $this->insertMulti($lastTable, $rows);
         $lastTable = NULL;
         $rows = [];
         
         return;
      }
      
      if ($lastTable && $lastTable != $table || count($rows) >= $this->BufferSize) {
         $this->insertMulti($lastTable, $rows);
         $rows = [];
      }
      
      $lastTable = $table;
      $rows[] = $row;
   }
   
   public function insertMulti($table, $rows) {
      if (empty($rows))
         return;
      
      $px = Gdn::database()->DatabasePrefix;
      $pDO = Gdn::database()->connection();
      
      $sql = '';
      foreach ($rows as $row) {
         if ($sql)
            $sql .= ",\n";
         
         $values = array_map([$pDO, 'quote'], $row);
         $sql .= '('.implode(',', $values).')';
      }
      
      $sql = "insert ignore {$px}$table\n".
         '('.implode(',', array_keys($rows[0])).") values\n".
         $sql;
      
//      echo htmlspecialchars($Sql);
      Gdn::database()->query($sql);
//      die();
   }
   
   public function import() {
      $this->defineTables();
      $this->parse();
      $this->RunQueries = TRUE; // Manually override whether to do import or just show SQL
      $this->insertTables();
      $this->counts();
   }
   
   public function insertTables() {
   }
   
   protected function _InsertUsers($table, $columns = ['Email', 'Name'], $userTable = FALSE) {
      // First join the users based on our own table
      if ($userTable) {
         $sql = "update GDN_{$table} z
            join GDN_$userTable zu
               on zu.Name = z.UserName
            set z.InsertUserID = zu.UserID";
         
         $this->query($sql);
      }
      
      // Next join in users based on the columns.
      foreach ($columns as $column) {
         $sql = "update GDN_{$table} z
            join GDN_User u
               on z.User{$column} = u.$column
            set z.InsertUserID = u.UserID";
               
         $this->query($sql);
      }
      
      // Insert the missing users.
      $sql = "insert GDN_User (
            Name,
            Email,
            Password,
            HashMethod,
            DateInserted,
            InsertIPAddress,
            Source
         )
         select
            i.UserName,
            i.UserEmail,
            'xxx' as Password,
            'Random' as HashMethod,
            curdate() as DateInserted,
            max(i.InsertIPAddress),
            '_source_' as Source
         from GDN_{$table} i
         left join GDN_User u
            on i.UserName = u.Name
         where u.UserID is null
            and i.InsertUserID is null
         group by
            i.UserName,
            i.UserEmail";
      $this->query($sql);
      
      // Assign the user IDs back.
      $sql = "update GDN_{$table} i
         join GDN_User u
            on u.Name = i.UserName
         set i.InsertUserID = u.UserID";
      $this->query($sql);
   }
   
   /**
    * @param string $type
    * @return CommentImportModel 
    */
   public static function newModel($type) {
      $result = new $type.'ImportModel';
      return $result;
   }
   
   public function parse() {
   }
   
   public function query($sql, $parameters = NULL) {
      $px = Gdn::database()->DatabasePrefix;
      if ($px != 'GDN_')
         $sql = str_replace(' GDN_', ' '.$px, $sql);
      $sql = str_replace(':_', $px, $sql);
      $sql = str_replace('_source_', $this->Source, $sql);
      
      $sql = trim($sql, ';');
      
      echo '<pre>'.htmlspecialchars($sql).";\n\n</pre>";
      
      if ($this->RunQueries)
         return Gdn::database()->query($sql, $parameters);
      else
         return TRUE;
   }
}