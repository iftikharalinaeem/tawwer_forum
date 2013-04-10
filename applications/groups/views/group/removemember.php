<h1><?php echo $this->Title(); ?></h1>

<div class="StructuredForm Form-Confirm">
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<div class="P">
   <?php echo T('You can remove or ban this member from the group.', 'You can remove or ban this member from the group. Banned members won\'t be able to join the group again.'); ?>
</div>
<div class="P">
   <?php
      echo $this->Form->RadioList('Type', array('Removed' => 'Remove', 'Banned' => 'Ban'));
   ?>
</div>
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
