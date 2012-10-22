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
   
   public function Counts() {
      $DiscussionModel = new DiscussionModel();
      
      $DiscussionModel->Counts('CountComments');
      $DiscussionModel->Counts('FirstCommentID');
      $DiscussionModel->Counts('LastCommentID');
      $DiscussionModel->Counts('DateLastComment');
      $DiscussionModel->Counts('LastCommentUserID');
      
      $DefaultRoleIDs = C('Garden.Registration.DefaultRoles');
      if (is_array($DefaultRoleIDs)) {
         $RoleID = array_pop($DefaultRoleIDs);
         $Sql = "insert GDN_UserRole (
               UserID,
               RoleID
            )	
            select
               u.UserID,
               $RoleID as RoleID
            from GDN_User u
            left join GDN_UserRole ur
               on u.UserID = ur.UserID
            where ur.RoleID is NULL
               and u.Source = '_source_'";
         $this->Query($Sql);
      }
   }
   
   public function DefineTables() { }
   
   public function Insert($Table, $Row = NULL) {
      static $LastTable = NULL;
      static $Rows = array();
      
      if (isset($Row['Attributes']) && is_array($Row['Attributes']))
         $Row['Attributes'] = serialize($Row['Attributes']);
      
      
      if ($Table === NULL) {
         $this->InsertMulti($LastTable, $Rows);
         $LastTable = NULL;
         $Rows = array();
         
         return;
      }
      
      if ($LastTable && $LastTable != $Table || count($Rows) >= $this->BufferSize) {
         $this->InsertMulti($LastTable, $Rows);
         $Rows = array();
      }
      
      $LastTable = $Table;
      $Rows[] = $Row;
   }
   
   public function InsertMulti($Table, $Rows) {
      if (empty($Rows))
         return;
      
      $Px = Gdn::Database()->DatabasePrefix;
      $PDO = Gdn::Database()->Connection();
      
      $Sql = '';
      foreach ($Rows as $Row) {
         if ($Sql)
            $Sql .= ",\n";
         
         $Values = array_map(array($PDO, 'quote'), $Row);
         $Sql .= '('.implode(',', $Values).')';
      }
      
      $Sql = "insert ignore {$Px}$Table\n".
         '('.implode(',', array_keys($Rows[0])).") values\n".
         $Sql;
      
//      echo htmlspecialchars($Sql);
      Gdn::Database()->Query($Sql);
//      die();
   }
   
   public function Import() {
      $this->DefineTables();
      $this->Parse();
      $this->RunQueries = FALSE;
      $this->InsertTables();
      $this->Counts();
   }
   
   public function InsertTables() {
   }
   
   protected function _InsertUsers($Table, $Columns = array('Email', 'Name'), $UserTable = FALSE) {
      // First join the users based on our own table
      if ($UserTable) {
         $Sql = "update GDN_{$Table} z
            join GDN_$UserTable zu
               on zu.Name = z.UserName
            set z.InsertUserID = zu.UserID";
         
         $this->Query($Sql);
      }
      
      // Next join in users based on the columns.
      foreach ($Columns as $Column) {
         $Sql = "update GDN_{$Table} z
            join GDN_User u
               on z.User{$Column} = u.$Column
            set z.InsertUserID = u.UserID";
               
         $this->Query($Sql);
      }
      
      // Insert the missing users.
      $Sql = "insert GDN_User (
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
         from GDN_{$Table} i
         left join GDN_User u
            on i.UserName = u.Name
         where u.UserID is null
            and i.InsertUserID is null
         group by
            i.UserName,
            i.UserEmail";
      $this->Query($Sql);
      
      // Assign the user IDs back.
      $Sql = "update GDN_{$Table} i
         join GDN_User u
            on u.Name = i.UserName
         set i.InsertUserID = u.UserID";
      $this->Query($Sql);
   }
   
   /**
    * @param string $Type
    * @return CommentImportModel 
    */
   public static function NewModel($Type) {
      $Result = new $Type.'ImportModel';
      return $Result;
   }
   
   public function Parse() {
   }
   
   public function Query($Sql, $Parameters = NULL) {
      $Px = Gdn::Database()->DatabasePrefix;
      if ($Px != 'GDN_')
         $Sql = str_replace(' GDN_', ' '.$Px, $Sql);
      $Sql = str_replace(':_', $Px, $Sql);
      $Sql = str_replace('_source_', $this->Source, $Sql);
      
      $Sql = trim($Sql, ';');
      
      echo '<pre>'.htmlspecialchars($Sql).";\n\n</pre>";
      
      if ($this->RunQueries)
         return Gdn::Database()->Query($Sql, $Parameters);
      else
         return TRUE;
   }
}