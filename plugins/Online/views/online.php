<?php if (!defined('APPLICATION')) exit(); ?>
<h2 class="H"><?php echo $this->data('Title'); ?></h2>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
<ul>
   <?php if ($this->data('ForceEditing') && $this->data('ForceEditing') != FALSE) { ?>
      <div class="Warning"><?php echo sprintf(T("You are editing %s's Online settings"),$this->data('ForceEditing')); ?></div>
   <?php } ?>
   <li>
      <?php
         echo $this->Form->label('Settings');
         echo $this->Form->checkBox('PrivateMode','Hide my online status from other members');
      ?>
   </li>
</ul>
<?php echo $this->Form->close('Save');