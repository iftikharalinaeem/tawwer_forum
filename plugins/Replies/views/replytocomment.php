<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title'); ?></h1>

<?php
echo $this->Form->Open();
echo $this->Form->Errors();

echo '<div class="P">'.$this->Data('MoveMessage').'</div>';

echo '<div class="Buttons Buttons-Confirm">';
echo $this->Form->Button('Cancel', array('type' => 'button', 'class' => 'Button Close'));
echo $this->Form->Button('OK', array('class' => 'Button Primary'));
echo '<div>';
echo $this->Form->Close();
?>