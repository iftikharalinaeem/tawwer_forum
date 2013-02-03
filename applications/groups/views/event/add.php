<div class="FormTitleWrapper">
   <h1><?php echo T($this->Data['Title']); ?></h1>

   <div class="FormWrapper StructuredForm">
      <?php 
         echo $this->Form->Errors(); 
         echo $this->Form->Open();
      ?>

      <div class="Event">
         <div class="P Name">
            <?php echo $this->Form->Label('Name of the Event', 'EventName'); ?>
            <div><?php echo $this->Form->TextBox('EventName'); ?></div>
         </div>

         <div class="P Details">
            <?php echo $this->Form->Label('Event Details', 'EventDetails'); ?>
            <div><?php echo $this->Form->BodyBox('Body', array('Table' => 'Event')); ?></div>
         </div>

         <div class="P Where">
            <?php echo $this->Form->Label('Where', 'EventWhere'); ?>
            <div><?php echo $this->Form->TextBox('EventWhere'); ?></div>
         </div>
         
         <div class="EventTime">
            
            <div class="P From">
               <?php echo $this->Form->Label('When', 'EventDateStarts', array('class' => 'When')); ?>
               <?php echo $this->Form->Label('From', 'EventDateStarts'); ?>
               <div>
                  <?php echo $this->Form->TextBox('EventDateStarts', array(
                     'class'        => 'InputBox DatePicker',
                     'title'        => "Date. Expects 'mm/dd/yyyy'."
                  )); ?> 
                  <?php echo $this->Form->TextBox('EventTimeStarts', array(
                     'class'        => 'InputBox TimePicker',
                     'placeholder'  => 'Add a time?'
                  )); ?>
               </div>
               <div class="Timebased Timezone">
                  <?php echo $this->Form->Hidden('EventTimezone', array('class' => 'EventTimezone')); ?>
                  <a class="EventTimezoneDisplay" data-dropdown="#dropdown-timezone">Test</a>
               </div>
               <div class="Timebased EndTime"><?php echo Anchor(T('End time?'), '#'); ?></div>
            </div>

            <div class="P To">
               <?php echo $this->Form->Label('To', 'EventDateEnds'); ?>
               <div>
                  <?php echo $this->Form->TextBox('EventDateEnds', array(
                     'class'        => 'InputBox DatePicker',
                     'title'        => "Date. Expects 'mm/dd/yyyy'."
                  )); ?> 
                  <?php echo $this->Form->TextBox('EventTimeEnds', array(
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