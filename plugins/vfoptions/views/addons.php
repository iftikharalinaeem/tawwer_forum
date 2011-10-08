<?php if (!defined('APPLICATION')) exit();

$AllowedPlugins = C('VFCom.AllowedPlugins');
if (!is_array($AllowedPlugins))
   $AllowedPlugins = array(
      'embedvanilla',
      'Emotify',
      'cleditor',
      'Facebook',
      'Twitter',
      'OpenID',
      'GoogleSignIn',
      'CustomProfileFields',
      'Flagging',
      'Tagging',
      'Gravatar',
      'vanillicon',
      'QnA',
      'RoleTitle',
      'Signatures',
      'Sitemaps',
      'SplitMerge',
      'Spoof',
      'StopForumSpam',
      'WhosOnline'
   );
   
   // $AllowedPlugins = array('Emotify','cleditor','Facebook','Twitter','OpenID','GoogleSignIn','CustomProfileFields','Flagging','Tagging','Gravatar','vanillicon','OpenID','QnA','RoleTitle','Signatures','SplitMerge','Spoof','WhosOnline','FileUpload','Voting','Quotes','Signatures','Pockets','PostCount','LastEdited','Sitemaps','ShareThis','SEOCompanion','AllViewed','Vanoogle','TrackingCodes');
   
$PluginManager = Gdn::PluginManager();
$AvailablePlugins = $PluginManager->AvailablePlugins();
$PluginCount = 0;
$EnabledCount = 0;
foreach ($AllowedPlugins as $PluginKey) {
   if (GetValue($PluginKey, $AvailablePlugins)) {
      $PluginCount++;
      if (array_key_exists($PluginKey, $PluginManager->EnabledPlugins()))
         $EnabledCount++;
   }
}
$DisabledCount = $PluginCount - $EnabledCount;

$Session = Gdn::Session();
?>
<style type="text/css">
/* Temporary css fixes until vfcom core can be pushed. */
img.PluginIcon { height: 50px; width: 50px; }
table th,
table td {
	padding: 6px 6px 6px 20px;
	vertical-align: top;
}
table th.Less,
table td.Less {
	padding: 6px;
}
table tbody tr.More th.Less,
table tbody tr.More td.Less,
table tbody th,
table tbody td {
	border-bottom: 1px solid #e0e0e0;
}
.AddonName .Buttons {
   white-space: nowrap;
}
</style>
<h1><?php echo $this->Data('Title') ?></h1>
<div class="Info">
   Here are some great features you can add to your site to change or enhance its functionality.
   Click the enable/disable buttons to enable or disable addons.
   We are always adding new features so make sure you check back from time to time.
</div>
<div class="Tabs FilterTabs">
   <ul>
      <li<?php echo $this->Filter == 'all' ? ' class="Active"' : ''; ?>><?php echo Anchor(sprintf(T('All %1$s'), Wrap($PluginCount)), 'settings/addons/all'); ?></li>
      <li<?php echo $this->Filter == 'enabled' ? ' class="Active"' : ''; ?>><?php echo Anchor(sprintf(T('Enabled %1$s'), Wrap($EnabledCount)), 'settings/addons/enabled'); ?></li>
      <li<?php echo $this->Filter == 'disabled' ? ' class="Active"' : ''; ?>><?php echo Anchor(sprintf(T('Disabled %1$s'), Wrap($DisabledCount)), 'settings/addons/disabled'); ?></li>
   </ul>
</div>
<?php echo $this->Form->Errors(); ?>
<table class="AltRows">
   <thead>
      <tr>
         <th colspan="2"><?php echo T('Plugin'); ?></th>
         <th><?php echo T('Description'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Alt = FALSE;
foreach ($AllowedPlugins as $Key) {
   $Plugin = GetValue($Key, $AvailablePlugins);
   if (!$Plugin)
      continue;
   

   $ScreenName = GetValue('Name', $Plugin, $Key);
   $Description = GetValue('Description', $Plugin, '');
   $Enabled = array_key_exists($Key, $PluginManager->EnabledPlugins());
   $SettingsUrl = $Enabled ? ArrayValue('SettingsUrl', $Plugin, '') : '';
   $RowClass = $Enabled ? 'Enabled' : 'Disabled';

   if ($this->Filter == 'enabled' && !$Enabled)
      continue;
   
   if ($this->Filter == 'disabled' && $Enabled)
      continue;

   $IconPath = '/plugins/'.GetValue('Folder', $Plugin, '').'/icon.png';
   $IconPath = file_exists(PATH_ROOT.$IconPath) ? $IconPath : 'applications/dashboard/design/images/plugin-icon.png';
   $IconPath = file_exists(PATH_ROOT.$IconPath) ? $IconPath : 'plugins/vfoptions/design/plugin-icon.png';
   ?>
   <tr <?php echo 'id="'.Gdn_Format::Url(strtolower($Key)).'-plugin"', ' class="'.$RowClass.'"'; ?>>
      <td class="Less">
         <?php echo Img($IconPath, array('class' => 'PluginIcon')); ?>
      </td>
      <td class="AddonName">
      <?php
         echo Wrap($ScreenName, 'strong');
         echo '<div class="Buttons">';
         $ToggleText = $Enabled ? 'Disable' : 'Enable';
         $Url = "/dashboard/settings/addons/".$this->Filter."/".strtolower($ToggleText)."/$Key/".Gdn::Session()->TransientKey();
         echo Anchor(
            T($ToggleText),
            $Url,
            $ToggleText . 'Addon SmallButton'
         );
         
         if ($SettingsUrl != '')
            echo Anchor(T('Settings'), $SettingsUrl, 'SmallButton');
         
         echo '</div>'
      ?>
      </td>
      <td><?php echo Gdn_Format::Html($Description); ?></td>
   </tr>
   <?php   
   $Alt = !$Alt;
}
?>
   </tbody>
</table>