<?php if (!defined('APPLICATION')) exit();

$AllowedPlugins = C('VFCom.AllowedPlugins');
if (!is_array($AllowedPlugins))
   $AllowedPlugins = array(
      'ButtonBar', 
      'embedvanilla',
      'Emotify',
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
      'GooglePrettify',
      'WhosOnline',
      'cleditor'
   );

$Addons = array();
foreach ($AllowedPlugins as $Key) {
   $Addons[$Key] = array('Type' => 'Plugin');
}

if ($this->Data('Plan.Subscription.PlanCode') != 'free') {
   // Add non-free addons.
   $Addons2 = array(
       'Reputation' => array('Type' => 'Application', 'Name' => 'Badges', 'IconUrl' => 'http://badges.vni.la/100/lol-2.png'),
      'Reactions' => array('Type' => 'Plugin'));
   $Addons = array_merge($Addons2, $Addons);
}

$PluginManager = Gdn::PluginManager();
$AvailablePlugins = $PluginManager->AvailablePlugins();

$ApplicationManager = Gdn::ApplicationManager();
$AvailableApplications = $ApplicationManager->AvailableApplications();

$AllAvailable = array_merge($AvailablePlugins, $AvailableApplications);

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
foreach ($Addons as $Key => $Info) {
   $Addon = GetValue($Key, $AllAvailable);
   if (!$Addon)
      continue;
   
   $Type = $Info['Type'];
   

   $ScreenName = GetValue('Name', $Info, GetValue('Name', $Addon, $Key));
   $Description = GetValue('Description', $Addon, '');
   if ($Type == 'Plugin')
      $Enabled = array_key_exists($Key, $PluginManager->EnabledPlugins());
   else
      $Enabled = array_key_exists($Key, $ApplicationManager->EnabledApplications());
   
   $SettingsUrl = $Enabled ? ArrayValue('SettingsUrl', $Addon, '') : '';
   $RowClass = $Enabled ? 'Enabled' : 'Disabled';

   if ($this->Filter == 'enabled' && !$Enabled)
      continue;
   
   if ($this->Filter == 'disabled' && $Enabled)
      continue;

   if (!$IconUrl = GetValue('IconUrl', $Info)) {
      $IconPath = '/plugins/'.GetValue('Folder', $Addon, '').'/icon.png';
      $IconPath = file_exists(PATH_ROOT.$IconPath) ? $IconPath : 'applications/dashboard/design/images/plugin-icon.png';
      $IconPath = file_exists(PATH_ROOT.$IconPath) ? $IconPath : 'plugins/vfoptions/design/plugin-icon.png';
      $IconUrl = $IconPath;
   }
   ?>
   <tr <?php echo 'id="'.Gdn_Format::Url(strtolower($Key)).'-plugin"', ' class="'.$RowClass.'"'; ?>>
      <td class="Less">
         <?php echo Img($IconUrl, array('class' => 'PluginIcon')); ?>
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