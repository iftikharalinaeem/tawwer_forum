<?php if (!defined('APPLICATION')) exit(); ?>
<h2><?php echo $this->Data('Title'); ?></h2>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <?php if ($this->Data('ForceEditing') && $this->Data('ForceEditing') != FALSE) { ?>
      <div class="Warning"><?php echo sprintf(T("You are editing %s's Online settings"),$this->Data('ForceEditing')); ?></div>
   <?php } ?>
   <li>
      <?php
         echo $this->Form->Label('Settings');
         echo $this->Form->CheckBox('PrivateMode','Hide my online status from other members');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');