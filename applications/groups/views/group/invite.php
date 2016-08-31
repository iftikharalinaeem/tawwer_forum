<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title'); ?></h1>

<div class="StructuredForm">
    <?php
    echo $this->Form->Open();
    echo $this->Form->Errors();
    ?>
    <div class="P">
        <?php
        echo $this->Form->Label('Invite one or more people to join this group.', 'To');
        echo Wrap($this->Form->TextBox('Recipients', array('MultiLine' => true, 'class' => 'Tokens-User MultiComplete')), 'div', array('class' => 'TextBoxWrapper'));
        ?>
    </div>
    <div class="Buttons Buttons-Confirm">
        <?php
        echo $this->Form->Button('OK', array('class' => 'Button Primary'));
        echo ' '.$this->Form->Button('Cancel', array('type' => 'button', 'class' => 'Button Close'));
        ?>
    </div>
    <?php
    echo $this->Form->Close();
    ?>
</div>