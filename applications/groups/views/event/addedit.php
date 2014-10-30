<div class="FormTitleWrapper">
   <h1><?php echo T($this->Data['Title']); ?></h1>
   <div class="FormWrapper StructuredForm AddEvent">
      
      <?php 
         echo $this->Form->Errors(); 
         echo $this->Form->Open();
      ?>

      <div class="Event" data-groupid="<?php echo $this->Data('Group.GroupID'); ?>">
         
         <?php if ($this->Data('Group')): ?>
         <div class="P Group">
            <?php echo $this->Form->Label('Group', 'Group'); ?>
            <?php WriteGroupCard($this->Data('Group'), FALSE); ?>
         </div>
         <?php endif; ?>
         
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
         
         <?php $Both = $this->Data('Event.DateEnds') ? ' Both' : ''; ?>
         <div class="EventTime Times <?php echo $Both; ?>">
            
            <div class="P From">
               <?php echo $this->Form->Label('When', 'DateStarts', array('class' => 'When')); ?>
               <?php echo $this->Form->Label('From', 'DateStarts'); ?>
               <span>
                  <?php echo $this->Form->TextBox('DateStarts', array(
                     'class'        => 'InputBox DatePicker',
                     'title'        => T("Date. Expects 'mm/dd/yyyy'.")
                  )); ?> 
                  <?php echo $this->Form->TextBox('TimeStarts', array(
                     'class'        => 'InputBox TimePicker',
                     'placeholder'  => T('Add a time?')
                  )); ?>
               </span>
               <span class="Timebased Timezone">
                  <?php echo $this->Form->Hidden('Timezone', array('class' => 'EventTimezone')); ?>
                  <?php echo $this->Form->Hidden('TimezoneAbbr', array('class' => 'EventTimezoneAbbr')); ?>
                  <a class="EventTimezoneDisplay" data-dropdown="#dropdown-timezone"><?php echo $this->Data('Event.TimezoneAbbr'); ?></a>
               </span>
               <span class="Timebased EndTime"><?php echo Anchor(T('End time?'), '#'); ?></span>
            </div>

            <div class="P To">
               <?php echo $this->Form->Label('To', 'DateEnds'); ?>
               <span>
                  <?php echo $this->Form->TextBox('DateEnds', array(
                     'class'        => 'InputBox DatePicker',
                     'title'        => T("Date. Expects 'mm/dd/yyyy'.")
                  )); ?> 
                  <?php echo $this->Form->TextBox('TimeEnds', array(
                     'class'        => 'InputBox TimePicker',
                     'placeholder'  => T('Add a time?')
                  )); ?>
               </span>
               <span class="Timebased NoEndTime"><?php echo Anchor(T('x'), '#'); ?></span>
            </div>
         </div>

         <div class="Buttons">
            <?php echo $this->Form->Button('Save', array('Type' => 'submit', 'class' => 'Button Primary')); ?> 
            <?php echo $this->Form->Button('Cancel', array('Type' => 'button', 'class' => 'Button CancelButton')); ?>
         </div>
         
         <?php
         echo '<div id="dropdown-timezone" class="EventTimezonePicker dropdown-menu has-tip has-scroll"><ul>';
         foreach ($this->Data('Timezones') as $TimezoneID => $TimezoneLabel) {
            echo Wrap(Wrap($TimezoneLabel, 'a', array(
               'data-timezoneid' => $TimezoneID
            )), 'li');
         }
         echo '</ul></div>';
         ?>

      </div>
      <?php echo $this->Form->Close(); ?>
   </div>
</div>