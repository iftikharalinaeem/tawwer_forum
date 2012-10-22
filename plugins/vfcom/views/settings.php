<?php if (!defined('APPLICATION')) exit(); 

   $HasLocalPluginCache = FALSE;
   
?>
<h1><?php echo T($this->Data('Title')); ?></h1>
<div class="Info">
   <?php
   if (defined('CLIENT_NAME')) {
      echo '<div><b>CLIENT_NAME:</b> '.htmlspecialchars(CLIENT_NAME).'</div>';
   }

   echo T('Monitor and control infrastructure configuration for this installation. Clear and increment caches.');
   ?>
</div>
<div class="ContentArea">
   <?php
      echo $this->Form->Open();
      echo $this->Form->Errors();
   ?>

   <h3><?php echo T("Plugin Cache"); ?></h3>
   <div class="Info">
      <?php echo T('The plugin cache is common to all sites deployed against this cluster, and contains the parsed PluginInfo arrays of all known plugins. Clearing this cache causes the next pageload to manually index all plugin searchpaths.'); ?>
      <div class="Information">
         <div><b>Search Paths</b></div>
         <?php
            foreach (Gdn::PluginManager()->SearchPaths() as $SearchPath => $SearchPathName) {
               if ($SearchPathName == 'local')
                  $HasLocalPluginCache = TRUE;
               $NumPlugins = sizeof(Gdn::PluginManager()->AvailablePluginFolders($SearchPath));
               $NumPluginsText = sprintf(Plural($NumPlugins, "%d plugin", "%d plugins"), $NumPlugins);
               ?><div class="PluginSearchPath"><?php echo FormatString(T("[{SearchPathName}] {SearchPath} - <b>{NumPlugins}</b>"),array(
                   "SearchPathName"    => $SearchPathName,
                   "SearchPath"        => $SearchPath,
                   "NumPlugins"        => $NumPluginsText
               )); ?></div><?php
            }
         ?>
      </div>
   </div>
   <?php
      echo $this->Form->Button("Clear plugin cache", array('Name' => 'Plugin_vfcom_ClearCache'));
      if ($HasLocalPluginCache)
         echo $this->Form->Button("Clear local plugin cache", array('Name' => 'Plugin_vfcom_ClearLocalCache'));
   ?>
   
   <h3><?php echo T("Cache Revision"); ?></h3>
   <div class="Info">
      <?php echo T("The cache revision is appended to the installation prefix. Incrementing the revision number causes the full cache prefix to change, thereby invalidating all installation-specific cache entries, including the local config file cache."); ?>
      <div class="Information">
         <div><b>Cache Keys and Revision</b></div>
         <?php
            $CacheRevision = Gdn::Cache()->GetRevision(NULL, TRUE);
            $FullCachePrefix = Gdn::Cache()->GetPrefix(NULL, TRUE);
            $CachePrefix = Gdn::Cache()->GetPrefix(NULL, FALSE);
         ?>
         <div class="CacheKeyInfo"><?php echo sprintf(T("Full prefix: <b>%s</b>"), $FullCachePrefix); ?></div>
         <div class="CacheKeyInfo"><?php echo sprintf(T("Revision: <b>%s = %d</b>"), "{$CachePrefix}.Revision", $CacheRevision); ?></div>
      </div>
   </div>
   <?php
      echo $this->Form->Button("Increment cache revision", array('Name' => 'Plugin_vfcom_IncrementCacheRevision'));
      echo $this->Form->Button("Reload client config", array('Name' => 'Plugin_vfcom_ReloadConfig'));
   ?>
   
   <h3><?php echo T("Client Settings"); ?></h3>
   <div class="Info">
      <?php echo T("Control client-specific config settings. Debug mode, vfoptions, spoofing, etc..."); ?>
   </div>
   
   <table id="ReportingTable" border="0" cellpadding="0" cellspacing="0" class="AltColumns">
      <thead>
         <tr>
            <th><?php echo T('Feature'); ?></th>
            <th class="Alt"><?php echo T('Description'); ?></th>
         </tr>
      </thead>

      <?php
         $Settings = array(
            "Debug Mode"   => "Detailed error reporting. Turns on/off the in-depth error page for Bonks.",
            "Update Mode"  => "Disables the site for non-admins.",
            "VF Options"   => "VF.com admin options.",
            "VF Spoof"     => "Allows Vanilla employees to gain access to hosted forums by logging in with a vf.com administrative user.",
            "Advanced Stats" => "Turns on/off advanced statistics tracking."
         );
      ?>

      <tbody>
         <?php
            $Alt = FALSE;
            foreach ($Settings as $SettingKey => $SettingDescription) {
//               if (is_array($SettingDescription)) {
//                  $ConfigKey = $SettingDescription[0];
//                  $SettingDescription = $SettingDescription[1];
//               } else {
//                  $ConfigKey = 
//               }
               
               $Alt = !$Alt;
               $ShortSettingKey = str_replace(' ', '', $SettingKey);
               $SettingEnabled = $this->Data($ShortSettingKey);
         ?>
         <tr <?php echo ($Alt ? 'class="Alt"' : ''); ?>>
            <td class="Info nowrap"><?php echo $SettingKey; ?>
               <div>
               <strong><?php echo $SettingEnabled ? 'Enabled' : 'Disabled'; ?></strong>
               <?php
                  $ButtonAction = $SettingEnabled ? 'disable': 'enable';
                  echo $this->Form->Button(ucfirst($ButtonAction), array(
                     'Name'   => "Plugin_vfcom_Toggle{$ShortSettingKey}",
                     'Class'  => 'SmallButton'
                  ));
               ?>
               </div>
            </td>
            <td class="Alt"><?php echo Gdn_Format::Text($SettingDescription); ?></td>
         </tr>
         <?php
            }
         ?>
      </tbody>
   </table>
   
   <?php
      echo $this->Form->Close();
   ?>
</div>