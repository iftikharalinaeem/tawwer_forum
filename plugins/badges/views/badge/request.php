<?php if (!defined('APPLICATION')) exit(); ?>

<div class="BadgeRequestForm">
    <h1><?php echo t('Request Badge') .  ': '. $this->data('Badge.Name'); ?></h1>
    <p><?php echo t('BadgeReasonPrompt', 'Think you deserve this badge? Tell us why.'); ?></p>
    <?php
    echo $this->Form->open();
    echo '<p>', $this->Form->textBox('Reason', ['MultiLine' => true]), '</p>';
    echo $this->Form->close('Send Request');
    ?>
</div>