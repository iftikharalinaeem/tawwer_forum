<?php if (!defined('APPLICATION')) exit(); ?>

<div id="EventAddEditForm" class="FormTitleWrapper">
    <h1><?php echo t($this->Data['Title']); ?></h1>
    <div class="FormWrapper StructuredForm AddEvent">
        <?php
            echo $this->Form->errors();
            echo $this->Form->open();
        ?>
        <div class="Event" data-groupid="<?php echo $this->data('Group.GroupID'); ?>">

            <div class="P Name">
                <?php echo $this->Form->label('Name of the Event', 'Name'); ?>
                <div><?php echo $this->Form->textBox('Name'); ?></div>
            </div>

            <div class="P Details">
                <?php echo $this->Form->label('Event Details', 'EventDetails'); ?>
                <div><?php echo $this->Form->bodyBox('Body', ['Table' => 'Event']); ?></div>
            </div>

            <div class="P Where">
                <?php echo $this->Form->label('Where', 'Location'); ?>
                <div><?php echo $this->Form->textBox('Location'); ?></div>
            </div>

            <?php
            $Both = $this->data('Event') && hasEndDate($this->data('Event')) ? ' Both' : '';
            ?>
            <div class="EventTime Times <?php echo $Both; ?>">

                <div class="P From">
                    <?php echo $this->Form->label('When', 'RawDateStarts', ['class' => 'When']); ?>
                    <?php echo $this->Form->label('From', 'RawDateStarts'); ?>
                    <?php echo $this->dateTimePicker('Starts', '12:00am'); ?>
                    <span class="Timebased EndTime"><?php echo anchor(t('End time?'), '#'); ?></span>
                </div>

                <div class="P To">
                    <?php echo $this->Form->label('To', 'RawDateEnds'); ?>
                    <?php echo $this->dateTimePicker('Ends', '11:59pm'); ?>
                    <span class="Timebased NoEndTime"><?php echo anchor('&times;', '#'); ?></span>
                </div>
            </div>

            <div class="Buttons">
                <?php echo $this->Form->button('Save', ['Type' => 'submit', 'class' => 'Button Primary']); ?>
                <?php echo $this->Form->button('Cancel', ['Type' => 'button', 'class' => 'Button CancelButton']); ?>
            </div>
        </div>
        <?php echo $this->Form->close(); ?>
    </div>
</div>
