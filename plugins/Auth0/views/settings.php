<?php if (!defined('APPLICATION')) exit(); ?>
   <div class="Help Aside">
      <?php
      echo '<h2>', t('Need More Help?'), '</h2>';
      echo '<ul>';
      echo wrap(anchor(t("Identity Protocols supported by Auth0"), 'https://auth0.com/docs/protocols'), 'li');
      echo '</ul>';
      ?>
   </div>

   <h1><?php echo $this->data('Title'); ?></h1>

    <div class="PageInfo">
        <h2><?php echo t('Create Single Sign On integration with Auth0!'); ?></h2>

        <p>
            <?php
            echo t('<p>If you haven\'t already, go to <a href=\'http://www.auth0.com\'>www.auth0.com</a> to create an SSO application.</p>
                    <p>Once your application is created you will receive a unique Client ID, Client Secret and Domain.</p>');
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
                <div class="Info">Auth0 requires that you provide allowed callback URLs, in part, to validate requests. Copy and past the URLs below into your Auth0 application settings.</div>
                <textarea class="TextBox textarea-autosize"><?php echo $this->data('redirectUrls'); ?></textarea>
            </li>
        </ul>
    </div>

<?php
echo $this->Form->close();

echo '</div>';

