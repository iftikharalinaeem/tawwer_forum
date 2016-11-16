<h1><?php echo $this->data('Title'); ?></h1>

<div class="PageInfo">
    <h2><?php echo t('Create Single Sign On integration using the JSON Web Token!'); ?></h2>

    <p>
        <?php
        echo t('<p>Provide information for connecting with your Authentication provider.</p>');
        ?>
    </p>
</div>
<?php

echo $this->Form->open(),
$this->Form->errors();

echo $this->Form->simple($this->data('_Form'));

echo '<div class="Buttons">';
echo $this->Form->button('Save');
echo $this->Form->close();
echo '</div>';

echo '<div style="border:solid 1px #ccc;background-color: #eee;padding:10px;margin: 10px; max-width: 550px;float: left;clear: left;border-radius: 3px">';

echo wordwrap($this->data('jwt'), 55, "\n<br>", true);

echo '</div>';
