<?php

class SplitDatabaseUsersTask extends Task {
   
   protected $ReallyRun = FALSE;
   protected $ProvisioningMode = 'manual';
   
   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   public function Init() {
      
      $ProvisioningMode = $this->TaskList->C('VanillaForums.Spawn.DatabaseProvisioning', 'manual');
      if ($ProvisioningMode != 'automatic') {
         TaskList::Event("Provisioning mode is '{$ProvisioningMode}', SplitDatabaseUsers requires 'automatic' mode.");
         return;
      }
      
      if (TaskList::Cautious()) {
         $Proceed = TaskList::Question("Split database users per database?","Split database users",array('yes','no','exit'),'yes');
         if ($Proceed == 'no') return;
         if ($Proceed == 'exit') exit();
      }
      
      $this->ReallyRun = TRUE;
      
      $ReportFailures = TaskList::GetConsoleOption('report-failures', FALSE);
      $this->ReportFailures = $ReportFailures;
      
      $this->TaskList->RequireValid = TRUE;
   }
   
   protected function Run() {
      if ($this->ReallyRun === FALSE) return;
      
      $ClientFolder = $this->ClientFolder();
      
      if (TaskList::Cautious()) {
         $Proceed = TaskList::Question("Really make a unique database user for {$ClientFolder}?","Split user",array('yes','no','exit'),'yes');
         if ($Proceed == 'no') return;
         if ($Proceed == 'exit') exit();
      }
      
      $CoreUsers = array('root', 'frontend');
      $DatabaseUser = $this->Client->C('Database.User');
      
      // Already split?
      if (!in_array($DatabaseUser, $CoreUsers))
         return TRUE;
      
      $DatabaseName = $this->Client->C('Database.Name');
      $DatabaseHost = $this->Client->C('Database.Host');
      
      TaskList::Event("Splitting database user...");
      if (!LAME) {
         try {
            $DatabaseOptions = $this->ProvisionUser($DatabaseName, $DatabaseHost);
            $this->Client->SaveToConfig(array(
                'Database.Host'     => GetValue('Host', $DatabaseOptions),
                'Database.User'     => GetValue('User', $DatabaseOptions),
                'Database.Password' => GetValue('Password', $DatabaseOptions)
            ));
         } catch (Exception $e) {
            return $this->Problem($e->getMessage());
         }
      }
   }
   
   protected function ProvisionUser($DatabaseName, $DatabaseHost) {
      
      $DatabaseOptions = array(
          'Host'     => NULL,
          'User'     => NULL,
          'Password' => NULL,
          'Name'     => NULL
      );
      
      // Choose a server
      $DatabaseHostAddr = gethostbyname($DatabaseHost);
      
      $Databases = $this->TaskList->C('VanillaForums.Spawn.Database');
      $NumDatabases = sizeof($Databases);
      $TryDatabase = mt_rand(0, $NumDatabases-1);
      $Loops = 0; $Selected = FALSE;
      TaskList::Event("Trying {$NumDatabases} databases");
      do {
         $DatabaseServerName = GetValue($TryDatabase, array_keys($Databases));
         $TestDatabaseHost = GetValue('Host', $Databases[$DatabaseServerName]);
         TaskList::MinorEvent("Trying '{$DatabaseServerName}' => {$TestDatabaseHost}");
         
         $TestDatabaseAddr = gethostbyname($TestDatabaseHost);
         if ($DatabaseHostAddr == $TestDatabaseAddr) {
            $Selected = $Databases[$DatabaseServerName];
         }
         $Loops++;
      } while ($Loops < $NumDatabases);

      if ($Selected === FALSE) {
         throw new Exception("Couldn't find a matching host:\n
Current: {$DatabaseHost} -> {$DatabaseHostAddr}");
      }
      
      $DatabaseOptions['Host'] = GetValue('Host', $Selected);
      $DatabaseOptions['User'] = GetValue('User', $Selected);
      $DatabaseOptions['Password'] = GetValue('Password', $Selected);
      $DatabaseOptions['Name'] = $DatabaseName;
      $DatabaseOptions['ServerName'] = $DatabaseServerName;
      
      try {
         $Database = $this->TaskList->Database(
            $DatabaseOptions['Host'],
            $DatabaseOptions['User'],
            $DatabaseOptions['Password'],
            $DatabaseOptions['Name']
         );
      } catch (Exception $e) {
         throw new Exception("Could not connect to host database: ".$e->getMessage());
      }
      
      $AccessHost = $this->TaskList->C('VanillaForums.Spawn.DatabaseAccessHost', 'localhost');
      $DatabaseOptions['AccessHost'] = $AccessHost;

      TaskList::Event("Generating user credentials");
      $ProvisionUser = substr($this->Client->ClientName, 0, 10).substr($DatabaseOptions['Name'],-6);
      TaskList::MinorEvent("User: {$ProvisionUser}");
      $ProvisionPassword = strtolower(RandomString(16, 'Aa0!'));
      TaskList::MinorEvent("Pass: {$ProvisionPassword}");
      $ProvisionUserQuery = sprintf("
         GRANT alter, create, delete, drop, index, insert, select, update, truncate
         ON %s.* 
         TO '%s'@'%s' 
         IDENTIFIED BY '%s'",
         $DatabaseOptions['Name'],
         $ProvisionUser,
         $AccessHost,
         $ProvisionPassword
      );
      $Success = mysql_query($ProvisionUserQuery, $Database);

      if ($Success === FALSE)
         throw new Exception("Could not provision new database user: ".mysql_error($Database));

      mysql_query("FLUSH PRIVILEGES", $Database);
      $DatabaseOptions['User'] = $ProvisionUser;
      $DatabaseOptions['Password'] = $ProvisionPassword;
      $DatabaseOptions['CreatedUser'] = TRUE;
      
      return $DatabaseOptions;
   }
   
   protected function Problem($Message) {
      if ($this->ReportFailures) {
         try {
            $ClientFolder = $this->ClientFolder();
            $Email = new Email($this->Client);
            $Email->To('tim@vanillaforums.com', 'Tim Gunter')
               ->From('runner@vanillaforums.com','VFCom Runner')
               ->Subject("{$ClientFolder} database user split failed")
               ->Message($Message)
               ->Send();
         } catch (Exception $e) {}
         
      }
      
      TaskList::MajorEvent($Message);
      return;
   }

}

