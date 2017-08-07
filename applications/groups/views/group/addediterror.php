<?php if (!defined('APPLICATION')) exit(); ?>

<div id="GroupForm" class="FormTitleWrapper">
    <h1><?php echo $this->data('Title'); ?></h1>

    <div class="FormWrapper StructuredForm">
        <?php
        echo $this->Form->open(['enctype' => 'multipart/form-data']);
        echo $this->Form->errors();

        echo $this->Form->close();
        ?>
    </div>
</div>