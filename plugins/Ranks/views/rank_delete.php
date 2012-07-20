<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>

<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<div class="Info Center">
   <?php
      printf(T('Are you sure you want to delete this %s?'), T('Rank'));
   ?>
</div>

<div class="Buttons Buttons-Confirm">
<?php
echo $this->Form->Button('Yes');
echo $this->Form->Button('No', array('type' => 'button', 'class' => 'Button Close'));
?>
</div>
<?php
echo $this->Form->Close();