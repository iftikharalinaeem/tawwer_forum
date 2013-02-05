<h1><?php echo $this->Data('Title'); ?></h1>

<div class="StructuredForm">
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<?php if ($this->Data('Group.Registration') == 'Approval'): ?>
   <div class="Center P">
      <?php echo T('You need to be approved before you can join this group.'); ?>
   </div>
<div class="P">
   <?php
      echo $this->Form->Label('Why do you want to join?', 'Reason');
      echo $this->Form->TextBox('Reason', array('MultiLine' => TRUE, 'Wrap' => TRUE));
   ?>
</div>
<?php else: ?>
   <div class="Center P">
      <?php echo T('Are you sure you want to join this group?'); ?>
   </div>
<?php endif; ?>

<div class="Buttons Buttons-Confirm">
   <?php
   echo $this->Form->Button('OK', array('class' => 'Button Primary'));
   echo ' '.$this->Form->Button('Cancel', array('type' => 'button', 'class' => 'Button Close'));
   ?>
</div>
<?php
echo $this->Form->Close();
?>
</div>