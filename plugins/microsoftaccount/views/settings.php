<?php if (!defined('APPLICATION')) exit(); ?>

    <h1><?php echo $this->data('Title'); ?></h1>

    <div class="PageInfo">
        <ul>
            <li>If you haven't already, visit <a href="http://apps.dev.microsoft.com">apps.dev.microsoft.com</a> to register your application.</li>
            <li>Due to Microsoft security requirements, after users sign in they will be directed back to this site behind SSL (HTTPS).</li>
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
