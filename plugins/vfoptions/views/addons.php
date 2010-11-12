<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title') ?></h1>
<div class="Info">
Hi there! This page lists some of the great things you can add to your site to change or enhance its functionality.
Click the enable/disable buttons to enable or disable addons.
We are always adding new features so make sure you check back from time to time.
</div>

<?php
echo $this->Form->Errors();
?>

<?php
function WritePlugin($Key, $Description = '') {
   static $Alt = FALSE;
   
   $PM = Gdn::PluginManager();
   $Plugin = $PM->AvailablePlugins($Key);
   if (!$Plugin)
      return;

   $Enabled = array_key_exists($Key, $PM->EnabledPlugins());
   $RowClass = $Enabled ? 'Enabled' : 'Disabled';

   $IconPath = '/plugins/vfoptions/design/'.strtolower($Key).'.png';
   if (file_exists(PATH_ROOT.$IconPath))
      $IconPath = Asset($IconPath);
   else
      $IconPath = '';
   
   echo '<tr class="'.$Alt.' '.$RowClass.'" valign="top">';

   echo '<td width="135">';

   if ($IconPath) {
      echo "<img src='$IconPath' class='AddonIcon' />";
   }

   if ($Enabled) {
      $Url = Url("/dashboard/settings/addons/disable/$Key?TransientKey=".Gdn::Session()->TransientKey());
      echo "<a href='$Url' class='SmallButton'>Disable</a>";
   } else {
      $Url = Url("/dashboard/settings/addons/enable/$Key?TransientKey=".Gdn::Session()->TransientKey());
      echo "<a href='$Url' class='SmallButton'>Enable</a>";
   }

   if ($Enabled && GetValue('SettingsUrl', $Plugin)) {
      echo " <a href='{$Plugin['SettingsUrl']}' class='SmallButton'>Settings</a>";
   }

   echo '</td>';


   echo '<td>';
   echo "<h2>{$Plugin['Name']}</h2>";

   if (!$Description)
      $Description = $Plugin['Description'];
   echo "<div>{$Description}</div>";
   echo '</td>';

   echo '</tr>';

   $Alt = !$Alt;
}
?>
<table class="Label AltRows Addons">
   <tbody>
   <?php
      WritePlugin('Emotify', 'Do you know what <a href="http://en.wikipedia.org/wiki/Emoticon">emoticons</a> are? This addon will replace all of your text emoticons with pretty pictures.');
      WritePlugin('cleditor', 'Adds a <a href="http://en.wikipedia.org/wiki/WYSIWYG">WYSIWYG</a> editor to your forum so that your users can more easily enter rich text comments.');
      WritePlugin('Facebook');
      WritePlugin('Twitter');
   ?>
   </tbody>
</table>