<div class="FormTitleWrapper">
   <h1><?php echo T($this->Data['Title']); ?></h1>
   <div class="FormWrapper StructuredForm AddEvent">
      
      <?php 
         echo $this->Form->Errors(); 
         echo $this->Form->Open();
      ?>

      <div class="Event">
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
         
         <div class="EventTime">
            
            <div class="P From">
               <?php echo $this->Form->Label('When', 'DateStarts', array('class' => 'When')); ?>
               <?php echo $this->Form->Label('From', 'DateStarts'); ?>
               <div>
                  <?php echo $this->Form->TextBox('DateStarts', array(
                     'class'        => 'InputBox DatePicker',
                     'title'        => "Date. Expects 'mm/dd/yyyy'."
                  )); ?> 
                  <?php echo $this->Form->TextBox('TimeStarts', array(
                     'class'        => 'InputBox TimePicker',
                     'placeholder'  => 'Add a time?'
                  )); ?>
               </div>
               <div class="Timebased Timezone">
                  <?php echo $this->Form->Hidden('Timezone', array('class' => 'EventTimezone')); ?>
                  <a class="EventTimezoneDisplay" data-dropdown="#dropdown-timezone">Test</a>
               </div>
               <div class="Timebased EndTime"><?php echo Anchor(T('End time?'), '#'); ?></div>
            </div>

            <div class="P To">
               <?php echo $this->Form->Label('To', 'DateEnds'); ?>
               <div>
                  <?php echo $this->Form->TextBox('DateEnds', array(
                     'class'        => 'InputBox DatePicker',
                     'title'        => "Date. Expects 'mm/dd/yyyy'."
                  )); ?> 
                  <?php echo $this->Form->TextBox('TimeEnds', array(
                     'class'        => 'InputBox TimePicker',
                     'placeholder'  => 'Add a time?'
                  )); ?>
               </div>
               <div class="Timebased NoEndTime"><?php echo Anchor(T('x'), '#'); ?></div>
            </div>
         </div>

         <div class="Buttons">
            <?php echo $this->Form->Button('Create Event', array('Type' => 'submit', 'class' => 'Button Primary')); ?> 
            <?php echo $this->Form->Button('Cancel', array('Type' => 'button', 'class' => 'PopupClose Button CancelButton')); ?>
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