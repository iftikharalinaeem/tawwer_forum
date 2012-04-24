<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session(); ?>

<h1><?php echo $this->Data('Title') ?></h1>
<div class="Info">
   These are features you can add to your site by enabling them.
   We are always adding new features, so check back from time to time.
</div>
<div class="Tabs FilterTabs">
   <ul>
      <li<?php echo $this->Filter == 'all' ? ' class="Active"' : ''; ?>><?php echo Anchor(sprintf(T('All %1$s'), Wrap($this->Data('PluginCount'))), 'settings/addons/all'); ?></li>
      <li<?php echo $this->Filter == 'enabled' ? ' class="Active"' : ''; ?>><?php echo Anchor(sprintf(T('Enabled %1$s'), Wrap($this->Data('EnabledCount'))), 'settings/addons/enabled'); ?></li>
      <li<?php echo $this->Filter == 'disabled' ? ' class="Active"' : ''; ?>><?php echo Anchor(sprintf(T('Disabled %1$s'), Wrap($this->Data('DisabledCount'))), 'settings/addons/disabled'); ?></li>
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
   
<?php foreach ($this->Data('Addons') as $Key => $Info) : ?>
   
   <?php $Enabled = GetValue('Enabled', $Info); ?>
   <tr <?php echo 'id="'.Gdn_Format::Url(strtolower($Key)).'-plugin" class="'.($Enabled ? 'Enabled' : 'Disabled').'"'; ?>>
      <td class="Less">
         <?php echo Img(GetValue('IconUrl', $Info), array('class' => 'PluginIcon')); ?>
      </td>
      <td class="AddonName">
      <?php
         echo Wrap(GetValue('Name', $Info, $Key), 'strong');
         
         echo '<div class="Buttons">';
         echo Anchor(T(GetValue('ToggleText', $Info, '')), GetValue('ToggleUrl', $Info, ''), ($Enabled ? 'Disable' : 'Enable').'Addon SmallButton');
         $SettingsUrl = ArrayValue('SettingsUrl', $Info);
         if ($Enabled && $SettingsUrl != '')
            echo Anchor(T('Settings'), $SettingsUrl, 'SmallButton');
         echo '</div>';
      ?>
      </td>
      <td><?php echo Gdn_Format::Html(GetValue('Description', $Info)); ?></td>
   </tr>
   
<?php endforeach; ?>

   </tbody>
</table>