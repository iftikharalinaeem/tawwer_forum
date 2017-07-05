<?php if (!defined('APPLICATION')) exit; ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div id="DiscussionForm">
    <?php
    echo $this->Form->Open(), $this->Form->Errors();

    VanillaPopPlugin::SimpleForm($this->Form, [
        'From' => 'TextBox',
        'To' => 'TextBox',
        'Subject' => 'TextBox',
        'Body' => ['Control' => 'TextBox', 'Options' => ['Multiline' => TRUE]],
        'Format' => ['Control' => 'RadioList', 'Items' => ['Html' => 'Html', 'Text' => 'Text'], 'Options' => ['Default' => 'Html']],
        'MessageID' => 'TextBox',
        'ReplyTo' => 'TextBox'
    ]);

    echo '<div class="Buttons">'.$this->Form->Close('Post').'</div>';
    ?>
</div>