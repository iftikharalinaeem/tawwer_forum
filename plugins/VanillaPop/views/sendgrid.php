<?php if (!defined('APPLICATION')) exit; ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div id="DiscussionForm">
<?php
echo $this->Form->Open(), $this->Form->Errors();

VanillaPopPlugin::SimpleForm($this->Form, array(
    'from' => 'TextBox',
    'to' => 'TextBox',
    'subject' => 'TextBox',
    'text' => array('Control' => 'TextBox', 'Options' => array('Multiline' => TRUE)),
    'html' => array('Control' => 'TextBox', 'Options' => array('Multiline' => TRUE)),
    'headers' => array('Control' => 'TextBox', 'Options' => array('Multiline' => TRUE))
));

echo $this->Form->Close('Post');
?>
</div>