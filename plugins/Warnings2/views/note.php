<?php if (!defined('APPLICATION')) return; ?>

<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Wrap FormWrappper NoteForm">
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
   
<div class="P">
<?php
echo $this->Form->BodyBox('Body');
?>
</div>
   
<div class="P Gloss">
   <?php echo T('These notes can only be seen by moderators.'); ?>
</div>

<?php
echo '<div class="Buttons Buttons-Confirm">', 
   $this->Form->Button('OK'), ' ',
   $this->Form->Button('Cancel', array('type' => 'button', 'class' => 'Button Close')),
   '</div>';
echo $this->Form->Close();
?>
</div>