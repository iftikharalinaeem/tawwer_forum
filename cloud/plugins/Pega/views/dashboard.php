<?php if (!defined('APPLICATION')) exit(); ?>
<script>

   $(document).ready(function () {

      $("#connect-button").click(function () {
         window.location = '<?php echo url('/plugin/Pega/connect'); ?>';
      });

      $("#disconnect-button").click(function () {
         window.location = '<?php echo url('/plugin/Pega/disconnect?token=' . $this->Data['DashboardConnectionToken']); ?>';
      });

      $("#enable-button").click(function () {
         window.location = '<?php echo url('/plugin/Pega/enable'); ?>';
      });

      $("#disable-button").click(function () {
         window.location = '<?php echo url('/plugin/Pega/disable'); ?>';
      });


   });

</script>
<h1><?php echo t($this->Data['Title']); ?></h1>

<div class="Info">
   <?php echo t('Connects to a Pega account. Once connected staff users will be able to create leads and cases from discussions and comments.'); ?>
</div>

<h3><?php echo t('Pega Settings'); ?></h3>

<?php
// Settings
echo $this->Form->open();
echo $this->Form->errors();
?>

<ul>

   <li>
      <?php
      echo $this->Form->label('ApplicationID', 'Plugins.Pega.ApplicationID');
      echo $this->Form->textBox('Plugins.Pega.ApplicationID');
      ?>
   </li>

   <li>
      <?php
      echo $this->Form->label('Secret', 'Plugins.Pega.Secret');
      echo $this->Form->textBox('Plugins.Pega.Secret');
      ?>
   </li>

   <li>
      <?php
      echo $this->Form->label('Authentication URL', 'Plugins.Pega.AuthenticationUrl');
      echo $this->Form->textBox('Plugins.Pega.AuthenticationUrl');
      ?>
      <span>Default: https://login.Pega.com</span>
   </li>


</ul>
<?php
echo $this->Form->close('Save');
?>
<br />


<h3 id="reconnect"><?php echo t('Global Login'); ?></h3>
<div class="Info">
   <p><?php echo t('This feature will allow you to have all Staff use one Pega Connection.'); ?></p>
   <p><?php echo t('If a user has a Pega connection already established we will use that instead.'); ?></p>
   <p><?php echo t('Note that all Leads and Cases created will show that they have been created by this user.'); ?></p>
</div>

<?php if (!$this->Data['DashboardConnection']) { ?>
   <div class="Info"><?php echo t('Global Login is currently'); ?> <strong><?php echo t('Disabled') ?></strong> </div>

   <button class="Button" id="enable-button"><?php echo t('Enable'); ?></button>
<?php } else { ?>

   <div class="Info">
      <?php echo t('Global Login is currently') ?> <strong><?php echo t('Enabled'); ?></strong>
   <?php if (isset($this->Data['DashboardConnectionProfile']['fullname'])) { ?>
      <p><?php echo t('Connected as:'); ?> <strong><?php echo $this->Data['DashboardConnectionProfile']['fullname']; ?></strong></p>
   <?php } ?>
   </div>

   <button class="Button" id="connect-button"><?php echo t('Connect'); ?></button>

   <button class="Button" id="disable-button"><?php echo t('Disable') ;?></button>

<?php } ?>

