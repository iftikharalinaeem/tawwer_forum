<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->data('Title'); ?></h1>

<?php
echo $this->Form->open();
echo $this->Form->errors();

echo '<div class="P">'.$this->data('MoveMessage').'</div>';

echo '<div class="Buttons Buttons-Confirm">';
echo $this->Form->button('Cancel', ['type' => 'button', 'class' => 'Button Close']);
echo $this->Form->button('OK', ['class' => 'Button Primary']);
echo '<div>';
echo $this->Form->close();
?>