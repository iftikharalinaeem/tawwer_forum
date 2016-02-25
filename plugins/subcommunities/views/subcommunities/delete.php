<?php if (!defined('APPLICATION')) { exit(); } ?>

<h1><?php echo $this->data('Title'); ?></h1>

<?php
echo $this->form->open();
echo $this->form->errors();

echo '<div class="P">'.sprintf(t('Are you sure you want to delete this %s?'), t('site')).'</div>';

echo '<div class="Buttons Buttons-Confirm">';
echo $this->form->button('OK', array('class' => 'Button Primary'));
echo $this->form->button('Cancel', array('type' => 'button', 'class' => 'Button Close'));
echo '<div>';
echo $this->form->close();
?>
