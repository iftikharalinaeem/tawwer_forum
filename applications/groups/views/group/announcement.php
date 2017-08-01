<?php if (!defined('APPLICATION')) exit(); ?>

<div id="GroupForm" class="FormTitleWrapper">
    <h1><?php echo $this->data('Title'); ?></h1>
    <div class="FormWrapper StructuredForm">
        <?php
        echo $this->Form->open();
        echo $this->Form->errors();
        ?>
        <div class="P P-Name">
            <?php
            echo $this->Form->label('Name', 'Name', ['class' => 'B']);
            echo $this->Form->textBox('Name', ['maxlength' => 100, 'class' => 'InputBox BigInput']);
            ?>
        </div>
        <div class="P P-Body">
            <?php
            echo $this->Form->bodyBox('Body');
            ?>
        </div>
        <div class="Buttons">
            <?php
            $Group = $this->data('Group');
            if ($Group)
                echo anchor(t('Cancel'), groupUrl($Group), 'Button');
            else
                echo anchor(t('Cancel'), '/groups', 'Button');

            echo ' '.$this->Form->button('Save', ['class' => 'Button Primary']);
            ?>
        </div>
        <?php echo $this->Form->close(); ?>
    </div>
</div>