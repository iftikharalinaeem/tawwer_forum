<?php if (!defined('APPLICATION')) return; ?>

<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Wrap FormWrapper NoteForm">
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>

<div class="P">
<?php
echo $this->Form->bodyBox('Body');
?>
</div>

<div class="P Gloss">
   <?php echo T('These notes can only be seen by moderators.'); ?>
</div>

<?php
echo '<div class="Buttons Buttons-Confirm">',
   $this->Form->button('OK'), ' ',
   $this->Form->button('Cancel', array('type' => 'button', 'class' => 'Button Close')),
   '</div>';
echo $this->Form->close();
?>
</div>
