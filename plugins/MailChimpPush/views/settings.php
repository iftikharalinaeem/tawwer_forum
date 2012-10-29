<?php if (!defined('APPLICATION')) exit();
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<style class="text/css">
   .MailChimpSettings ul {
      padding-bottom: 15px !important;
   }
</style>
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="InfoRow MailChimpSettings">
   
   <div class="Warning">API Authentication settings.</div>
   <ul>
      <li><?php
         echo $this->Form->Label("API Key", "ApiKey");
         echo $this->Form->TextBox('ApiKey');
      ?></li>
   </ul>
   
   <?php if ($this->Data('Configured')): ?>
   <div class="Warning">Mailing List settings.</div>
   <ul>
      <li><?php
         echo $this->Form->Label("Mailing List", "ListID");
         echo $this->Form->DropDown('ListID', $this->Data('Lists'), array('IncludeNull' => TRUE));
      ?></li>
      
      <li><?php
         echo $this->Form->CheckBox('ConfirmJoin', 'Send confirmation email?');
      ?></li>
   </ul>
   <?php endif; ?>

</div>

<?php echo $this->Form->Close('Save');
