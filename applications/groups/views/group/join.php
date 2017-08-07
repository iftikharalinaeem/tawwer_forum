<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->data('Title'); ?></h1>

<div class="StructuredForm">
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
<?php if ($this->data('Group.Registration') == 'Approval'): ?>
    <div class="Center P">
        <?php echo t('You need to be approved before you can join this group.'); ?>
    </div>
<div class="P">
    <?php
        echo $this->Form->label('Why do you want to join?', 'Reason');
        echo $this->Form->textBox('Reason', ['MultiLine' => true, 'Wrap' => true]);
    ?>
</div>
<?php else: ?>
    <div class="Center P">
        <?php echo t('Are you sure you want to join this group?'); ?>
    </div>
<?php endif; ?>

<div class="Buttons Buttons-Confirm">
    <?php
    echo $this->Form->button('OK', ['class' => 'Button Primary']);
    echo ' '.$this->Form->button('Cancel', ['type' => 'button', 'class' => 'Button Close']);
    ?>
</div>
<?php
echo $this->Form->close();
?>
</div>