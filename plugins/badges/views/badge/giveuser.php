<?php if (!defined('APPLICATION')) exit();

$this->title(sprintf(t('Give a Badge to %s'), val('Name', $this->User))); ?>

<div id="UserBadgeForm">
    <h1><?php echo sprintf(t('Give a Badge to %s'), val('Name', $this->User)); ?></h1>

    <?php
    echo $this->Form->open();
    echo $this->Form->errors();

    echo '<p>', $this->Form->label('Badge', 'BadgeID');
    echo $this->Form->dropDown('BadgeID', $this->BadgeData, ['ValueField' => 'BadgeID', 'TextField' => 'Name']), '</p>';

    echo '<p>', $this->Form->label('Reason (optional)', 'Reason');
    echo $this->Form->textBox('Reason', ['MultiLine' => true]), '</p>';

    echo anchor('Cancel', 'badge/'.$this->data('Badge.BadgeID'));

    echo $this->Form->close('Give Badge');
    ?>
</div>