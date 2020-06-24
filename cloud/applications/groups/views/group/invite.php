<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->data('Title'); ?></h1>

<div class="StructuredForm">
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>
    <div class="P">
        <?php
        echo $this->Form->label('Invite one or more people to join this group.', 'To');
        echo wrap($this->Form->textBox('Recipients', ['MultiLine' => true, 'class' => 'Tokens-User MultiComplete']), 'div', ['class' => 'TextBoxWrapper']);
        ?>
    </div>
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