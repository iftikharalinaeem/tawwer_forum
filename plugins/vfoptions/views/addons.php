<?php if (!defined('APPLICATION')) exit();

function PluginSort($a, $b) {
    return strcmp(GetValue('Name', $a), GetValue('Name', $b));
}

$PluginManager = Gdn::PluginManager();
$ApplicationManager = Gdn::ApplicationManager();

// Build AllAvailable array (plugins + apps)
$AvailablePlugins = $PluginManager->AvailablePlugins();
$AvailableApplications = $ApplicationManager->AvailableApplications();
$AllAvailable = array_merge($AvailablePlugins, $AvailableApplications);

// Addons to show 'Contact Us' instead of 'Enable'
$LockedPlugins = C('VFCom.Plugins.RequireAdmin', array('jsConnect', 'Multilingual', 'Pockets', 'Sphinx', 'TrackingCodes', 'VanillaPop', 'Whispers'));

// Addons to hide even when enabled
$HiddenPlugins = C('VFCom.Plugins.Hidden', array('CustomTheme', 'CustomDomain', 'CustomizeText', 'HtmLawed', 'cloudfiles', 'rackmonkey'));

// Allowed plugins list per client's plan
$Plan = GetValue('Plan', Infrastructure::Plan());
$AllowedPluginNames = GetValue('Plugins', json_decode(GetValue('Addons', $Plan)), array());
$AllowedPlugins = array();
foreach($AllowedPluginNames as $Name) {
   // Add available plugins to list to display
   if ($Info = GetValue($Name, $AvailablePlugins))
      $AllowedPlugins[$Name] = $Info;
}

// Enabled plugins minus hidden and vf*
$EnabledPlugins = $PluginManager->EnabledPlugins();
foreach($EnabledPlugins as $Key => $Name) {
   // Skip all vf* plugins
   if (in_array($Key, $HiddenPlugins) || strpos($Key, 'vf') === 0)
      unset($EnabledPlugins[$Key]);
}

$Addons = array_merge($AllowedPlugins, $EnabledPlugins);

// Add 'Plugin' type
foreach ($Addons as $Key => &$Info) {
   $Info['Type'] = 'Plugin';
}

// Kludge on the Reputation app
if ($this->Data('Plan.Subscription.PlanCode') != 'free') {
   $Reputation = array('Reputation' => array('Type' => 'Application', 'Name' => 'Badges', 'IconUrl' => 'http://badges.vni.la/100/lol-2.png'));
   $Addons = array_merge($Reputation, $Addons);
}

uasort($Addons, 'PluginSort');

// Get counts
$PluginCount = 0;
$EnabledCount = 0;
foreach ($Addons as $PluginKey => $Info) {
   if (GetValue($PluginKey, $AvailablePlugins)) {
      $PluginCount++;
      if (array_key_exists($PluginKey, $EnabledPlugins))
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

// Display addons list
foreach ($Addons as $Key => $Info) {
   // Confirm each addon is available & grab info
   $Addon = GetValue($Key, $AllAvailable);
   if (!$Addon)
      continue;
   
   $Type = $Info['Type'];
   $ScreenName = GetValue('Name', $Info, GetValue('Name', $Addon, $Key));
   $Description = GetValue('Description', $Addon, '');
   
   // Is the addon enabled?
   if ($Type == 'Plugin')
      $Enabled = array_key_exists($Key, $PluginManager->EnabledPlugins());
   else
      $Enabled = array_key_exists($Key, $ApplicationManager->EnabledApplications());
   
   $SettingsUrl = $Enabled ? ArrayValue('SettingsUrl', $Addon, '') : '';
   $RowClass = $Enabled ? 'Enabled' : 'Disabled';
   
   // Apply filters
   if ($this->Filter == 'enabled' && !$Enabled)
      continue;
   if ($this->Filter == 'disabled' && $Enabled)
      continue;
   
   // Find addon's icon
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
         $ToggleText = $ToggleClass = $Enabled ? 'Disable' : 'Enable';
         $Url = "/dashboard/settings/addons/".$this->Filter."/".strtolower($ToggleText)."/$Key/".Gdn::Session()->TransientKey();
         
         // Override for plugins that need admin intervention
         // Doesn't stop URL circumvention; if they wanna break their forum, let 'em.
         if (!$Enabled && in_array($Key, $LockedPlugins)) {
            $Url = '/dashboard/settings/vanillasupport';
            $ToggleText = 'Contact Us';
         }
         
         echo Anchor(
            T($ToggleText),
            $Url,
            $ToggleClass . 'Addon SmallButton'
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