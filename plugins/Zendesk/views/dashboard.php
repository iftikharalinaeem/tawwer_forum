<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T($this->Data['Title']); ?></h1>
<div class="Info">
   <?php echo T('ZendeskDashboard', 'This plugin allows you to submit user discussion and comments to your hosted Zend Desk'); ?>
</div>

<div class="Description">
   <p>
      If you already have an account you need to enable API Access for this plugin to work

   <ul style="list-style-type: circle; margin: 5px 20px; padding: 10px;">
      <li>Login to your Zen Desk Site</li>
      <li>Go to the <strong>Admin</strong> Setting</li>
      <li>Under <strong>Channels</strong> Select API</li>
      <li>Enable <strong>Token Access</strong> and <strong>Password Access</strong></li>
      <li>Copy the <strong>API Token</strong> and enter it below</li>
   </ul>

   </p>


   <p>
      If you don't have an account you can create one for free at <a href="http://www.zendesk.com/" target="_blank">Zendesk</a>
   </p>
</div>

<h3><?php echo T('Zen Desk Settings'); ?></h3>

<?php
// Settings
echo $this->Form->Open();
echo $this->Form->Errors();
?>

<ul>

   <li>
      <?php
      echo $this->Form->Label('Zen Desk URL', 'Plugin.Zendesk.Url');
      echo $this->Form->TextBox('Plugin.Zendesk.Url');
      ?>
   </li>

   <li>
      <?php
      echo $this->Form->Label('API User', 'Plugin.Zendesk.User');
      echo $this->Form->TextBox('Plugin.Zendesk.User');
      ?>
   </li>

   <li>
      <?php
      echo $this->Form->Label('API Token', 'Plugin.Zendesk.ApiKey');
      echo $this->Form->TextBox('Plugin.Zendesk.ApiKey');
      ?>
   </li>


   <li>
      <?php
      echo $this->Form->Label('API URL', 'Plugin.Zendesk.ApiUrl');
      echo $this->Form->TextBox('Plugin.Zendesk.ApiUrl');
      ?>
      https://XXXXX.zendesk.com/api/v2
   </li>

</ul>
<?php
echo $this->Form->Close('Save');
?>

<h3><?php echo T('Enable in discussions '); ?></h3>
<div class="Info">
   <?php echo T('ZendeskHeading', 'Configure the settings above before enabling the Plugin.'); ?>
</div>


<div class="FilterMenu">
   <?php
   $ToggleName = C('Plugins.Zendesk.Enabled') ? T('Disable Zendesk in Discussions') : T('Enable Zendesk in Discussions');
   echo "<div>" . Wrap(Anchor($ToggleName, 'plugin/Zendesk/toggle/' . Gdn::Session()->TransientKey(), 'SmallButton')) . "</div>";
   ?>
</div>

