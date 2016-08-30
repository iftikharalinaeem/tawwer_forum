<?php if (!defined('APPLICATION')) exit(); ?>

    <h1><?php echo $this->data('Title'); ?></h1>

    <div class="padded">
        <p><?php printf(
                t('Register your application at %1$s using your redirect URI: %2$s'),
                anchor('https://apps.dev.microsoft.com', 'https://apps.dev.microsoft.com'),
                Gdn::request()->url('/entry/microsoftaccount', true, true)
        ); ?></p>
        <p><?php echo t('SSL is required. Users will be redirected back to this site via HTTPS.'); ?></p>
    </div>

    <?php
        echo $this->Form->open(),
        $this->Form->errors();

        echo $this->Form->simple($this->data('_Form'));
    ?>

    <div class="Buttons">
    <?php
        echo $this->Form->button('Save');
        echo $this->Form->close();
    ?>
    </div>
