<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
<div class="Configuration">
   <div class="ConfigurationForm">
      <ul>
         <li class="form-group">
            <div class="label-wrap">
               <?php echo $this->Form->label(t('VigLink.ApiKeyLabel'), 'ApiKey'); ?>
               <div class="info"><?php echo t('VigLink.GetAPIKey'); ?></div>
            </div>
            <?php echo $this->Form->textBoxWrap('ApiKey'); ?>
         </li>
      </ul>
   </div>
</div>
<?php echo $this->Form->close('Save');
