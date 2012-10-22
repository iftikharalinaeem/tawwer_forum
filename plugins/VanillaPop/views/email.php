<?php if (!defined('APPLICATION')) exit; ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div id="DiscussionForm">
<?php
echo $this->Form->Open(), $this->Form->Errors();

VanillaPopPlugin::SimpleForm($this->Form, array(
    'From' => 'TextBox',
    'To' => 'TextBox',
    'Subject' => 'TextBox',
    'Body' => array('Control' => 'TextBox', 'Options' => array('Multiline' => TRUE)),
    'Format' => array('Control' => 'RadioList', 'Items' => array('Html' => 'Html', 'Text' => 'Text'), 'Options' => array('Default' => 'Html')),
    'MessageID' => 'TextBox',
    'ReplyTo' => 'TextBox'
));

echo '<div class="Buttons">'.$this->Form->Close('Post').'</div>';
?>
</div>