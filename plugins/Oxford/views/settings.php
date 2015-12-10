<?php if (!defined('APPLICATION')) exit(); ?>

   <h1><?php echo $this->data('Title'); ?></h1>

    <div class="PageInfo">
        <h2><?php echo t('Create Single Sign On integration with Oxford!'); ?></h2>

        <p>
            <?php
            echo t('<p>Enter your unique Client ID, Client Secret and Domain below.</p>');
            ?>
        </p>
    </div>
<?php

echo $this->Form->open(),
   $this->Form->errors();

echo $this->Form->simple($this->data('_Form'));

echo '<div class="Buttons">';
echo $this->Form->button('Save');

?>

    <div>
        <ul>
            <li>
                <label>Important</label>
                <div class="Info">OAuth2 protocol requires that you provide allowed callback URLs, in part, to validate requests. Copy and past the URLs below into your OAuth2 application settings on the authenticating server.</div>
                <textarea class="TextBox textarea-autosize"><?php echo $this->data('redirectUrls'); ?></textarea>
            </li>
        </ul>
    </div>

<?php
echo $this->Form->close();

echo '</div>';

