<?php

class NewforumTask extends Task {
   
   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   public function Init() {

      $SourceCodeTag = FALSE;
      if (!$this->TaskList->IsValidSourceTag($SourceCodeTag))
         $SourceCodeTag = TaskList::GetConsoleOption('source', FALSE);
      
      if (!$this->TaskList->IsValidSourceTag($SourceCodeTag))
         $SourceCodeTag = $this->TaskList->C('VanillaForums.Spawn.DeploymentVersion', FALSE);
      
      if ($SourceCodeTag === FALSE)
         TaskList::FatalError("No source code tag provided.");
      
      $SourceCodePath = $this->TaskList->IsValidSourceTag($SourceCodeTag);
      if ($SourceCodePath === FALSE)
         TaskList::FatalError("Provided source code tag '{$SourceCodeTag}' was not valid.");
      
      Task::Set('SourceCodeTag',$SourceCodeTag);
   }
   
   protected function Run() {
      
      $ConfigRules = array(
         'source tag'               => array('s','source-tag', TRUE, TRUE),
         'database provisioning'    => array(NULL,'database-provisioning', TRUE, TRUE),
         'database host'            => array('h','database-host', TRUE, FALSE),
         'database user'            => array('u','database-user', TRUE, FALSE),
         'database password'        => array('p','database-password', TRUE, FALSE),
         'database name'            => array('n','database-name', TRUE, FALSE),
         'site title'               => array('t','site-title',TRUE, TRUE),
         'site domain'              => array('d','site-domain',TRUE, TRUE),
         'cookie salt'              => array(NULL,'cookie-salt',TRUE, FALSE),
         'cookie name'              => array(NULL,'cookie-name',TRUE, FALSE),
         'cookie domain'            => array(NULL,'cookie-domain',TRUE, FALSE),
         'vanilla userid'           => array(NULL,'vanilla-userid',TRUE, TRUE),
         'vanilla siteid'           => array(NULL,'vanilla-siteid',TRUE, TRUE),
         'vanilla accountid'        => array(NULL,'vanilla-accountid',TRUE, TRUE)
      );
      
      $this->Options = Task::ConfigOverlay(array(
         'source tag'               => $this->TaskList->C('VanillaForums.Spawn.DeploymentVersion', 'unstable'),
         'database provisioning'    => $this->TaskList->C('VanillaForums.Spawn.DatabaseProvisioning', 'manual'),
         'database host'            => $this->TaskList->C('VanillaForums.Spawn.DatabaseHost', NULL),
         'database user'            => $this->TaskList->C('VanillaForums.Spawn.DatabaseUser', NULL),
         'database password'        => $this->TaskList->C('VanillaForums.Spawn.DatabasePassword', NULL),
         'database name'            => NULL,
         'site title'               => NULL,
         'site domain'              => $this->ClientFolder(),
         'cookie name'              => 'vf_'.$Subdomain.'_'.RandomString(5),
         'cookie salt'              => RandomString(10),
         'cookie domain'            => '.'.ltrim($this->TaskList->C('VanillaForums.Spawn.HostingDomain'),' .'),
         'vanilla userid'           => NULL,
         'vanilla siteid'           => NULL,
         'vanilla accountid'        => NULL
      ), $ConfigRules);
      
      try {
         $this->CheckConfigRules($this->Options, $ConfigRules);
      } catch (Exception $e) {
         TaskList::FatalError($e->getMessage());
      }
      
      $Subdomain = $this->Client->ClientName;
      $ClientFolder = $this->Client->ClientFolder;
      $HostingDomain = trim(str_replace($Subdomain, '', $ClientFolder),' .');
      
      // Check for allowed subdomain
      $AllowedSubdomainRegex = '/^([\d\w-]+)$/si';
      if (!preg_match($AllowedSubdomainRegex, $Subdomain))
         TaskList::FatalError(array(
            'Message'   => 'Your forum name can only contain letters, numbers and dashes.',
            'Type'      => 'user',
            'Code'      => '001'
         ));
      
      $ReservedWords = array(
         'reserved',
         'www',
         'subdomains',
         'awesome',
         'ftp',
         'stats',
         'webmail',
         'mail',
         'billing',
         'payments',
         'support',
         'help',
         'blog',
         'etsy',
         'microsoft',
         'apple'
      );
      if (in_array($Subdomain, $ReservedWords))
         TaskList::FatalError(array(
            'Message'   => 'The forum name you requested is already in use.',
            'Type'      => 'user',
            'Code'      => '002'
         ));
      
      // Determine new DB name
      $DatabaseOptions = $this->ProvisionDatabase($Subdomain);
      $this->Options['database host'] = GetValue('Host', $DatabaseOptions);
      $this->Options['database user'] = GetValue('User', $DatabaseOptions);
      $this->Options['database password'] = GetValue('Password', $DatabaseOptions);
      $this->Options['database name'] = GetValue('Name', $DatabaseOptions);
      
      TaskList::Event("Database server '{$DatabaseOptions['ServerName']}' chosen using {$DatabaseOptions['ProvisioningMode']} mode");
      TaskList::MinorEvent("Host: {$DatabaseOptions['Host']}");
      TaskList::MinorEvent("User: {$DatabaseOptions['User']}");
      TaskList::MinorEvent("Password: {$DatabaseOptions['Password']}");
      
      TaskList::MinorEvent("Building directory structure");
      
      // Create the client vhost folder
      TaskList::Mkdir($this->Client->ClientRoot);
      
      // Create subfolders
      $this->Client->Mkdir('applications');
      $this->Client->Mkdir('cache');
      $this->Client->Mkdir('cache/Smarty');
      $this->Client->Mkdir('cache/Smarty/cache');
      $this->Client->Mkdir('cache/Smarty/compile');
      $this->Client->Mkdir('conf');
      $this->Client->Mkdir('plugins');
      $this->Client->Mkdir('themes');
      $this->Client->Mkdir('uploads');
      
      $this->Client->Chmod('conf', 0775);
      $this->Client->Chmod('uploads', 0775);
      $this->Client->Chmod('cache', 0775);
      $this->Client->Chmod('cache/Smarty', 0775);
      $this->Client->Chmod('cache/Smarty/compile', 0775);
      
      // Create empty client config file
      $this->Client->Touch('conf/config.php');
      $this->Client->Chmod('conf/config.php', 0775);
      
      TaskList::MinorEvent("Creating template config");
      
      $ConfigTemplateFile = Task::GetFile('config.tpl');
      if (!$ConfigTemplateFile) {
         $this->RemoveClient();
         TaskList::FatalError("Could not find config file template '{$ConfigTemplateFile}'.");
      }
      
      // Read config template data
      $ConfigData = file_get_contents($ConfigTemplateFile);
      
      $InitialConfig = FormatString($ConfigData, $this->Options);
      $WroteConfig = $this->Client->Write('conf/config.php', $InitialConfig);
      if (!$WroteConfig)
         TaskList::FatalError("Could not create client config file.");
      
      // Reload client configs
      $this->Client->LoadConfigFiles();
      
      $FinalQuery = FormatString("
         UPDATE GDN_Site
         SET
            Path = '{FullPath}',
            DatabaseName = '{DatabaseName}',
            UpdateUserID = '{UserID}',
            DateUpdated = '{DateUpdated}'
         WHERE
            SiteID = {SiteID}",
         array(
            'FullPath'        => $this->Client->ClientRoot,
            'DatabaseName'    => $this->Options['database name'],
            'DatabaseHost'    => $this->Options['database host'],
            'UserID'          => $this->Options['vanilla userid'],
            'DateUpdated'     => TaskList::ToDateTime(),
            'SiteID'          => $this->Options['vanilla siteid']
         ));
      mysql_query($FinalQuery, $DatabaseOptions['Database']);
   }
   
   protected function RemoveClient() {
      TaskList::RemoveFolder($this->Client->ClientRoot);
   }

   protected function ProvisionDatabase($Subdomain) {
      
      $DatabaseOptions = array(
          'Host'     => NULL,
          'User'     => NULL,
          'Password' => NULL,
          'Name'     => NULL
      );
      
      // Choose a server
      $ProvisioningMode = $this->Option('database provisioning', 'manual');
      $DatabaseOptions['ProvisioningMode'] = $ProvisioningMode;
      switch ($ProvisioningMode) {
         case 'automatic':
            $Databases = $this->TaskList->C('VanillaForums.Spawn.Database');
            
            $NumDatabases = sizeof($Databases);
            $ChosenDatabase = mt_rand(0, $NumDatabases-1);
            $DatabaseServerName = GetValue($ChosenDatabase, array_keys($Databases));
            
            $DatabaseOptions['Host'] = GetValue('Host', $Databases[$DatabaseServerName]);
            $DatabaseOptions['User'] = GetValue('User', $Databases[$DatabaseServerName]);
            $DatabaseOptions['Password'] = GetValue('Password', $Databases[$DatabaseServerName]);
            
            break;
         
         case 'manual':
         default:
            $DatabaseServerName = 'static';
            $DatabaseOptions['Host'] = $this->Option('database host', NULL);
            $DatabaseOptions['User'] = $this->Option('database user', NULL);
            $DatabaseOptions['Password'] = $this->Option('database password', NULL);
            
            break;
      }
      $DatabaseOptions['ServerName'] = $DatabaseServerName;
      
      // Get DB name
      $DatabaseName = $this->Option('database name', NULL);
      if (is_null($DatabaseName)) {
         $DatabaseName = 'vf_'.$Subdomain.'_'.RandomString(5);
      }
      
      $DatabaseOptions['Name'] = $DatabaseName;
      
      // If the database itself doesn't exist, create it
      try {
         $Database = $this->TaskList->Database(
            $DatabaseOptions['Host'],
            $DatabaseOptions['User'],
            $DatabaseOptions['Password'],
            $DatabaseOptions['Name']
         );
         
         $DatabaseOptions['Database'] = $Database;
      } catch (SelectException $e) {
         // Create it
         $Database = $this->TaskList->Database(
            $DatabaseOptions['Host'],
            $DatabaseOptions['User'],
            $DatabaseOptions['Password']
         );
         
         $DatabaseOptions['Database'] = $Database;
         mysql_query("create database {$DatabaseOptions['Name']}", $Database);
         $DatabaseOptions['CreatedDatabase'] = TRUE;
      } catch (ConnectException $e) {
         TaskList::FatalError($e->getMessage());
      }
      
      // Now create the unprivileged user for this database
      if ($ProvisioningMode == 'automatic') {
         $AccessHost = $this->TaskList->C('VanillaForums.Spawn.DatabaseAccessHost', 'localhost');
         $DatabaseOptions['AccessHost'] = $AccessHost;
         
         $ProvisionUser = substr($Subdomain, 0, 10).substr($DatabaseOptions['Name'],-6);
         $ProvisionPassword = strtolower(RandomString(16, 'Aa0!'));
         $Success = mysql_query(sprintf("
            GRANT alter, create, delete, drop, index, insert, select, update 
            ON %s.* 
            TO '%s'@'%s' 
            IDENTIFIED BY '%s'",
            $DatabaseOptions['Name'],
            $ProvisionUser,
            $AccessHost,
            $ProvisionPassword
         ), $Database);
         
         if ($Success === FALSE) {
            $this->RollebackProvision($DatabaseOptions);
            TaskList::FatalError("Could not provision new database user: ".mysql_error($Database));
         }
         
         mysql_query("FLUSH PRIVILEGES", $Database);
         $DatabaseOptions['User'] = $ProvisionUser;
         $DatabaseOptions['Password'] = $ProvisionPassword;
         $DatabaseOptions['CreatedUser'] = TRUE;
      }
      
      //return array_intersect_key($DatabaseOptions, array_flip(array('Host', 'User', 'Password', 'Name')));
      return $DatabaseOptions;
   }
   
   protected function RollebackProvision($DatabaseOptions) {
      if (GetValue('CreatedDatabase', $DatabaseOptions, FALSE)) {
         // Delete table
         mysql_query(sprintf("DROP DATABASE '%s'",
            $DatabaseOptions['Name']
         ), $DatabaseOptions['Database']);
      }
      
      if (GetValue('CreatedUser', $DatabaseOptions, FALSE)) {
         // Delete user
         mysql_query(sprintf("DROP USER '%s'@'%s'",
            $DatabaseOptions['User'],
            $DatabaseOptions['AccessHost']
         ), $DatabaseOptions['Datbase']);
      }
   }
   
}
