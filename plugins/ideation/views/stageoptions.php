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
        echo $this->Form->radioList('Stage', $this->data('Stages'), array('Default' => $this->data('CurrentStageID'), 'list' => true));
        echo '<div>'.t('Add an explanation.').'</div>';
        echo $this->Form->textbox('StageNotes', array('Multiline' => true, 'value' => $this->data('StageNotes')));
        ?>
    </div>

    <?php
    echo '<div class="Buttons Buttons-Confirm">',
    $this->Form->button(T('OK')), ' ',
    $this->Form->button(T('Cancel'), array('type' => 'button', 'class' => 'Button Close')),
    '</div>';
    echo $this->Form->close();
    ?>
</div>
