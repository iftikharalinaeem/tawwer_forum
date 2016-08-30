<?php if (!defined('APPLICATION')) exit(); ?>
<div id="GroupForm" class="FormTitleWrapper">
    <h1><?php echo $this->Data('Title'); ?></h1>

    <div class="FormWrapper StructuredForm">
        <?php
        echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
        echo $this->Form->Errors();

        echo $this->Form->Close();
        ?>
    </div>
</div>