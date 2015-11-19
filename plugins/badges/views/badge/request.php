<?php if (!defined('APPLICATION')) exit(); ?>

<div class="BadgeRequestForm">
    <h1><?php echo T('Request Badge') .  ': '. $this->Data('Badge.Name'); ?></h1>
    <p><?php echo T('BadgeReasonPrompt', 'Think you deserve this badge? Tell us why.'); ?></p>
    <?php
    echo $this->Form->Open();
    echo '<p>', $this->Form->TextBox('Reason', array('MultiLine' => TRUE)), '</p>';
    echo $this->Form->Close('Send Request');
    ?>
</div>