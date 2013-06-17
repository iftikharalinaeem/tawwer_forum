<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php

echo $this->Form->Open(),
   $this->Form->Errors();

echo $this->Form->Simple($this->Data('_Form'));


echo '<div class="Buttons">';
echo $this->Form->Button('Save'),
   $this->Form->Close();
echo '</div>';