<div class="FormTitleWrapper">
   <h1><?php echo T($this->Data['Title']); ?></h1>
   <div class="FormWrapper StructuredForm AddEvent">

      <?php
         echo $this->Form->Errors();
         echo $this->Form->Open();
      ?>

      <div class="Event" data-groupid="<?php echo $this->Data('Group.GroupID'); ?>">

         <div class="P Name">
            <?php echo $this->Form->Label('Name of the Event', 'Name'); ?>
            <div><?php echo $this->Form->TextBox('Name'); ?></div>
         </div>

         <div class="P Details">
            <?php echo $this->Form->Label('Event Details', 'EventDetails'); ?>
            <div><?php echo $this->Form->BodyBox('Body', array('Table' => 'Event')); ?></div>
         </div>

         <div class="P Where">
            <?php echo $this->Form->Label('Where', 'Location'); ?>
            <div><?php echo $this->Form->TextBox('Location'); ?></div>
         </div>

         <?php
         $Both = $this->Data('Event') && HasEndDate($this->Data('Event')) ? ' Both' : '';
         ?>
         <div class="EventTime Times <?php echo $Both; ?>">

            <div class="P From">
               <?php echo $this->Form->Label('When', 'RawDateStarts', array('class' => 'When')); ?>
               <?php echo $this->Form->Label('From', 'RawDateStarts'); ?>
               <?php echo $this->dateTimePicker('Starts', '12:00am'); ?>
               <span class="Timebased EndTime"><?php echo Anchor(T('End time?'), '#'); ?></span>
            </div>

            <div class="P To">
               <?php echo $this->Form->Label('To', 'RawDateEnds'); ?>
               <?php echo $this->dateTimePicker('Ends', '11:59pm'); ?>
               <span class="Timebased NoEndTime"><?php echo Anchor('&times;', '#'); ?></span>
            </div>
         </div>

         <div class="Buttons">
            <?php echo $this->Form->Button('Save', array('Type' => 'submit', 'class' => 'Button Primary')); ?>
            <?php echo $this->Form->Button('Cancel', array('Type' => 'button', 'class' => 'Button CancelButton')); ?>
         </div>
      </div>
      <?php echo $this->Form->Close(); ?>
   </div>
</div>
