<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->data('Title') ?></h1>
<div class="">
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>

    <div class="P">
        <?php
//        $this->Form = new Gdn_Form();
        echo '<div>'.$this->Form->dropDown('Status', $this->data('Statuses'), array('Default' => $this->data('CurrentStatusID'), 'list' => true)).'</div>';
        echo '<div>'.t('Add an explanation.').'</div>';
        echo $this->Form->textbox('StatusNotes', array('Multiline' => true, 'value' => $this->data('StatusNotes')));
        ?>
    </div>

    <?php
    echo '<div class="Buttons Buttons-Confirm">',
    $this->Form->button(t('OK')), ' ',
    $this->Form->button(t('Cancel'), array('type' => 'button', 'class' => 'Button Close')),
    '</div>';
    echo $this->Form->close();
    ?>
</div>
