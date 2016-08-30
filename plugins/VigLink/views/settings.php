<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<div class="Configuration">
   <div class="ConfigurationForm">
      <ul>
         <li class="form-group row">
            <div class="label-wrap">
               <?php echo $this->Form->Label(T('VigLink.ApiKeyLabel'), 'ApiKey'); ?>
               <div class="info"><?php echo T('VigLink.GetAPIKey'); ?></div>
            </div>
            <?php echo $this->Form->textBoxWrap('ApiKey'); ?>
         </li>
      </ul>
   </div>
</div>
<div class="form-footer js-modal-footer">
   <?php echo $this->Form->Button(t('Save')); ?>
</div>
<?php
   echo $this->Form->Close();
