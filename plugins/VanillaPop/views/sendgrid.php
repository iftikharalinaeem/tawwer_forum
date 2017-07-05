<?php if (!defined('APPLICATION')) exit; ?>
<h1><?php echo $this->data('Title'); ?></h1>
<div id="DiscussionForm">
    <?php
    echo $this->Form->open(), $this->Form->errors();

    VanillaPopPlugin::simpleForm($this->Form, [
        'from' => 'TextBox',
        'to' => 'TextBox',
        'subject' => 'TextBox',
        'text' => ['Control' => 'TextBox', 'Options' => ['Multiline' => TRUE]],
        'html' => ['Control' => 'TextBox', 'Options' => ['Multiline' => TRUE]],
        'headers' => ['Control' => 'TextBox', 'Options' => ['Multiline' => TRUE]]
    ]);

    echo $this->Form->close('Post');
    ?>
</div>