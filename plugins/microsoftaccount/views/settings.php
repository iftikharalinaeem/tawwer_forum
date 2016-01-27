<?php if (!defined('APPLICATION')) exit(); ?>

    <h1><?php echo $this->data('Title'); ?></h1>

    <div class="PageInfo">
        <ul>
            <li><?php printf(
                    t('Register your application at %1$s'),
                    anchor('https://apps.dev.microsoft.com', 'https://apps.dev.microsoft.com')
            ); ?></li>
            <li><?php echo t('SSL is required. Users will be redirected back to this site via HTTPS.'); ?></li>
        </ul>
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
