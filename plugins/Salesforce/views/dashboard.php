<?php if (!defined('APPLICATION')) exit(); ?>
<script>

   $(document).ready(function () {

      $("#connect-button").click(function () {
         window.location = '<?php echo Url('/plugin/Salesforce/connect'); ?>';
      });

      $("#disconnect-button").click(function () {
         window.location = '<?php echo Url('/plugin/Salesforce/disconnect?token=' . $this->Data['DashboardConnectionToken']); ?>';
      });

      $("#enable-button").click(function () {
         window.location = '<?php echo Url('/plugin/Salesforce/enable'); ?>';
      });

      $("#disable-button").click(function () {
         window.location = '<?php echo Url('/plugin/Salesforce/disable'); ?>';
      });


   });

</script>
<h1><?php echo T($this->Data['Title']); ?></h1>

<div class="Info">
   <?php echo T('Connects to a Salesforce account. Once connected staff users will be able to create leads and cases from discussions and comments.'); ?>
</div>

<h3><?php echo T('Salesforce Settings'); ?></h3>

<?php
// Settings
echo $this->Form->Open();
echo $this->Form->Errors();
?>

<ul>

   <li>
      <?php
      echo $this->Form->Label('ApplicationID', 'Plugins.Salesforce.ApplicationID');
      echo $this->Form->TextBox('Plugins.Salesforce.ApplicationID');
      ?>
   </li>

   <li>
      <?php
      echo $this->Form->Label('Secret', 'Plugins.Salesforce.Secret');
      echo $this->Form->TextBox('Plugins.Salesforce.Secret');
      ?>
   </li>

   <li>
      <?php
      echo $this->Form->Label('Authentication URL', 'Plugins.Salesforce.AuthenticationUrl');
      echo $this->Form->TextBox('Plugins.Salesforce.AuthenticationUrl');
      ?>
      <span>Default: https://login.salesforce.com</span>
   </li>


</ul>
<?php
echo $this->Form->Close('Save');
?>
<br />


<h3 id="reconnect"><?php echo T('Global Login'); ?></h3>
<div class="Info">
   <p><?php echo T('This feature will allow you to have all Staff use one Salesforce Connection.'); ?></p>
   <p><?php echo T('If a user has a Salesforce connection already established we will use that instead.'); ?></p>
   <p><?php echo T('Note that all Leads and Cases created will show that they have been created by this user.'); ?></p>
</div>

<?php if (!$this->Data['DashboardConnection']) { ?>
   <div class="Info"><?php echo T('Global Login is currently'); ?> <strong><?php echo T('Disabled') ?></strong> </div>

   <button class="Button" id="enable-button"><?php echo T('Enable'); ?></button>
<?php } else { ?>

   <div class="Info">
      <?php echo T('Global Login is currently') ?> <strong><?php echo T('Enabled'); ?></strong>
   <?php if (isset($this->Data['DashboardConnectionProfile']['fullname'])) { ?>
      <p><?php echo T('Connected as:'); ?> <strong><?php echo $this->Data['DashboardConnectionProfile']['fullname']; ?></strong></p>
   <?php } ?>
   </div>

   <button class="Button" id="connect-button"><?php echo T('Connect'); ?></button>

   <button class="Button" id="disable-button"><?php echo T('Disable') ;?></button>

<?php } ?>

