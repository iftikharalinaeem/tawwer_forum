<?php if (!defined('APPLICATION')) exit();

$this->Title(sprintf(T('Give a Badge to %s'), GetValue('Name', $this->User))); ?>

<div id="UserBadgeForm">
    <h1><?php echo sprintf(T('Give a Badge to %s'), GetValue('Name', $this->User)); ?></h1>

    <?php
    echo $this->Form->Open();
    echo $this->Form->Errors();

    echo '<p>', $this->Form->Label('Badge', 'BadgeID');
    echo $this->Form->DropDown('BadgeID', $this->BadgeData, array('ValueField' => 'BadgeID', 'TextField' => 'Name')), '</p>';

    echo '<p>', $this->Form->Label('Reason (optional)', 'Reason');
    echo $this->Form->TextBox('Reason', array('MultiLine' => TRUE)), '</p>';

    echo Anchor('Cancel', 'badge/'.$this->Data('Badge.BadgeID'));

    echo $this->Form->Close('Give Badge');
    ?>
</div>