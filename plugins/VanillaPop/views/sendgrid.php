<?php if (!defined('APPLICATION')) exit; ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div id="DiscussionForm">
    <?php
    echo $this->Form->Open(), $this->Form->Errors();

    VanillaPopPlugin::SimpleForm($this->Form, [
        'from' => 'TextBox',
        'to' => 'TextBox',
        'subject' => 'TextBox',
        'text' => ['Control' => 'TextBox', 'Options' => ['Multiline' => TRUE]],
        'html' => ['Control' => 'TextBox', 'Options' => ['Multiline' => TRUE]],
        'headers' => ['Control' => 'TextBox', 'Options' => ['Multiline' => TRUE]]
    ]);

    echo $this->Form->Close('Post');
    ?>
</div>