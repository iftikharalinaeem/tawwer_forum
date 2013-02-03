<h1><?php echo T($this->Data['Title']); ?></h1>

<div class="P EventInfo" data-eventid="<?php echo $this->Data('Event.EventID'); ?>">
   <ul>
      <?php
      $AllDay = (bool)$this->Data('Event.AllDayEvent');
      
      $DateFormatString = '{Date}';
      $DateFormat = 'l, F j, Y';
      $TimeFormat = 'h:ia';
      $ShowDates = array();
      
      if (!$AllDay) $DateFormatString .= ' at {Time}';
      
      $FromDate = new DateTime($this->Data('Event.DateStarts'));
      $ShowDates['From'] = FormatString($DateFormatString, array(
         'Date'   => $FromDate->format($DateFormat),
         'Time'   => $FromDate->format($TimeFormat)
      ));
      
      $WhenFormat = "{ShowDates.From}{AllDay}";
      if ($this->Data('Event.DateEnds')):
         
         $ToDate = new DateTime($this->Data('Event.DateEnds'));
         $ShowDates['To'] = FormatString($DateFormatString, array(
            'Date'   => $ToDate->format($DateFormat),
            'Time'   => $ToDate->format($TimeFormat)
         ));
         $WhenFormat = "{ShowDates.From} <b>until</b> {ShowDates.To}{AllDay}";
         
      endif;
      
      ?>
      <li><span>When</span> <?php echo FormatString($WhenFormat, array(
         'ShowDates' => $ShowDates,
         'AllDay'    => ($AllDay) ? T(', all day') : ''
      )); ?>
      </li>
      <li><span>Where</span> <?php echo $this->Data('Event.Location'); ?></li>
      <li><span>Organizer</span> <?php echo UserAnchor($this->Data('Event.Organizer')); ?></li>
   </ul>
   
   <div class="Body"><?php echo $this->Data('Event.Body'); ?></div>
</div>

<div class="FormTitleWrapper">
   <h2><?php echo T("Who's going?"); ?></h2>
   <div class="FormWrapper StructuredForm Attending">
      
      <div class="P Attending">
         <?php echo $this->Form->Label('Are you attending this event?'); ?>
         <div><?php echo $this->Form->RadioList('Attending', array(
            'Yes'       => 'Yes',
            'No'        => 'No',
            'Maybe'     => 'Maybe'
         ), array('class' => 'EventAttending')); ?></div>
      </div>
      
   </div>
   
   <div class="Attendees" id="EventAttendees">
      <?php echo $this->FetchView('attendees'); ?>
   </div>
</div>