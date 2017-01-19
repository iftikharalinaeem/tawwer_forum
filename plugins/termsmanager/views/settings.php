<h1><?php echo $this->data('Title'); ?></h1>

<?php

echo $this->Form->open(),
$this->Form->errors();

echo $this->Form->simple($this->data('_Form'));

echo '<div class="Buttons">';
echo $this->Form->button('Save');

echo $this->Form->close();


