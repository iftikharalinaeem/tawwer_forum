<h1><?php echo $this->Data('Title'); ?></h1>

<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<div class="P">
   <?php echo T('Are you sure you want to leave this group?'); ?>
</div>

<div class="Buttons Buttons-Confirm">
   <?php
   echo $this->Form->Button('OK', array('class' => 'Button Primary'));
   echo ' '.$this->Form->Button('Cancel', array('type' => 'button', 'class' => 'Button Close'));
   ?>
</div>
<?php
echo $this->Form->Close();