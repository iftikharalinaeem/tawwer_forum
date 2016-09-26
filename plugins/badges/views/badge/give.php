<?php if (!defined('APPLICATION')) exit();

$this->title(t('Give a Badge')); ?>

<div class="UserBadgeForm">
    <h1><?php echo t('Give Badge') . ': ' . Gdn_Format::text($this->data('Badge.Name')); ?></h1>
    <p class="padded"><?php echo Gdn_Format::text($this->data('Badge.Body')); ?></p>

    <?php
    echo $this->Form->open();
    echo $this->Form->errors(); ?>

    <div class="form-group">
        <div class="label-wrap">
            <?php echo $this->Form->label('Recipients', 'To'); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->textBox('To', ['MultiLine' => true, 'class' => 'MultiComplete']), '</p>'; ?>
        </div>
    </div>
    <div class="form-group">
        <div class="label-wrap">
            <?php echo $this->Form->label('Reason (optional)', 'Reason'); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->textBox('Reason', ['MultiLine' => true]); ?>
        </div>
    </div>
    <div class="js-modal-footer form-footer">
        <?php echo $this->Form->button('Cancel', ['type' => 'button', 'class' => 'btn btn-link js-modal-close']);
        echo $this->Form->button('Give Badge'); ?>
    </div>

    <?php echo $this->Form->close(); ?>
</div>
