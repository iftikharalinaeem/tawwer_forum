<?php if (!defined('APPLICATION')) exit();

$this->Title(T('Give a Badge')); ?>

<div class="UserBadgeForm">
    <h1><?php echo T('Give Badge') . ': ' . Gdn_Format::Text($this->Data('Badge.Name')); ?></h1>
    <p class="padded"><?php echo Gdn_Format::Text($this->Data('Badge.Body')); ?></p>

    <?php
    echo $this->Form->Open();
    echo $this->Form->Errors(); ?>

    <div class="form-group row">
        <div class="label-wrap">
            <?php echo $this->Form->Label('Recipients', 'To'); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->TextBox('To', array('MultiLine' => TRUE, 'class' => 'MultiComplete')), '</p>'; ?>
        </div>
    </div>
    <div class="form-group row">
        <div class="label-wrap">
            <?php echo $this->Form->Label('Reason (optional)', 'Reason'); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->TextBox('Reason', array('MultiLine' => TRUE)); ?>
        </div>
    </div>
    <div class="js-modal-footer form-footer">
        <?php echo $this->Form->button('Cancel', ['type' => 'button', 'class' => 'btn btn-link js-modal-close']);
        echo $this->Form->button('Give Badge'); ?>
    </div>

    <?php echo $this->Form->close(); ?>
</div>
