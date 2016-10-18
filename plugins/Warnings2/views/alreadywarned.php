<?php if (!defined('APPLICATION')) exit(); ?>

  <h1><?php echo $this->Data('Title'); ?></h1>

<?php
echo $this->Form->open();
echo $this->Form->errors();

echo '<div class="P">'.sprintf(t('The user has already been warned for this %s.'), t('post')).'</div>';

echo '<div class="Buttons Buttons-Confirm">';
echo $this->Form->button('OK', array('class' => 'Button Primary'));
echo '<div>';
echo $this->Form->close();
?>