<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title'); ?></h1>

<?php
echo $this->form->Open();
echo $this->form->Errors();

echo '<div class="P">'.sprintf(T('Are you sure you want to delete this %s?'), T('site')).'</div>';

echo '<div class="Buttons Buttons-Confirm">';
echo $this->form->Button('OK', array('class' => 'Button Primary'));
echo $this->form->Button('Cancel', array('type' => 'button', 'class' => 'Button Close'));
echo '<div>';
echo $this->form->Close();
?>
