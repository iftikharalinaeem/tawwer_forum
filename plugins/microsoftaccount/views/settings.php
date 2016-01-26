<?php if (!defined('APPLICATION')) exit(); ?>

    <h1><?php echo $this->data('Title'); ?></h1>

    <div class="PageInfo">
        <p>
            If you haven't already, visit <a href="http://apps.dev.microsoft.com">apps.dev.microsoft.com</a> to register your application.
        </p>
    </div>

<?php

echo $this->Form->open(),
$this->Form->errors();

echo $this->Form->simple($this->data('_Form'));

echo '<div class="Buttons">';
echo $this->Form->button('Save');

?>

<?php
echo $this->Form->close();

echo '</div>';

