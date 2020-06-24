<?php if (!defined('APPLICATION')) exit; ?>
<h1><?php echo $this->data('Title'); ?></h1>
<div id="DiscussionForm">
    <?php
    echo $this->Form->open(), $this->Form->errors();

    VanillaPopPlugin::simpleForm($this->Form, [
        'from' => 'TextBox',
        'to' => 'TextBox',
        'subject' => 'TextBox',
        'text' => ['Control' => 'TextBox', 'Options' => ['Multiline' => true]],
        'html' => ['Control' => 'TextBox', 'Options' => ['Multiline' => true]],
        'headers' => ['Control' => 'TextBox', 'Options' => ['Multiline' => true]]
    ]);

    echo $this->Form->close('Post');
    ?>
</div>