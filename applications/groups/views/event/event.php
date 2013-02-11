<div class="Event-Header">
   <!-- Edit/Delete Buttons -->
   <?php WriteEventButtons(); ?>
   <h1 class="Event-Title"><?php echo htmlspecialchars($this->Data('Event.Name')); ?></h1>
</div>

<div class="P EventInfo" data-eventid="<?php echo $this->Data('Event.EventID'); ?>">
   <ul>
      <?php
      $AllDay = (bool)$this->Data('Event.AllDayEvent');
      
      $DateFormatString = '{Date} at {Time}';
      $DateFormat = 'l, F j, Y';
      $TimeFormat = 'g:ia';
      $ShowDates = array();
      $UTC = new DateTimeZone('UTC');
      $TimezoneID = $this->Data('Event.Timezone');
      $LocaleTimezone = new DateTimeZone($TimezoneID);
      
      $FromDate = new DateTime($this->Data('Event.DateStarts'), $UTC);
      $FromDate->setTimezone($LocaleTimezone);
      $FromDateSlot = $FromDate->format('Ymd');
      
      $ToDate = new DateTime($this->Data('Event.DateEnds'), $UTC);
      $ToDate->setTimezone($LocaleTimezone);
      $ToDateSlot = $ToDate->format('Ymd');
      
      // If we're 'all day' and only on one day
      if ($AllDay && $FromDateSlot == $ToDateSlot) $DateFormatString = '{Date}';
      
      $ShowDates['From'] = FormatString($DateFormatString, array(
         'Date'   => $FromDate->format($DateFormat),
         'Time'   => $FromDate->format($TimeFormat)
      ));
      
      // If we're not 'all day', or if 'all day' spans multiple days
      if (!$AllDay || $FromDateSlot != $ToDateSlot):
         
         $ToDate = new DateTime($this->Data('Event.DateEnds'), $UTC);
         $ToDate->setTimezone($LocaleTimezone);
         $ShowDates['To'] = FormatString($DateFormatString, array(
            'Date'   => $ToDate->format($DateFormat),
            'Time'   => $ToDate->format($TimeFormat)
         ));
         
      endif;
      
      // Output format
      $WhenFormat = "{ShowDates.From}{AllDay}";
      if (sizeof($ShowDates) > 1)
         $WhenFormat = "{ShowDates.From} <b>until</b> {ShowDates.To}{AllDay}";
      
      $TimezoneLabel = EventModel::Timezones($TimezoneID);
      $Transition = array_shift($T = $LocaleTimezone->getTransitions(time(), time()));
      if (!$TimezoneLabel) {
         $TZLocation = $LocaleTimezone->getLocation();
         $TimezoneLabel = GetValue('comments', $TZLocation);
      } else {
         preg_match('`([\w& -]+) [A-Z]+$`', $TimezoneLabel, $Matches);
         $TimezoneLabel = $Matches[1];
      }
      $TimezoneAbbr = $Transition['abbr'];
      
      ?>
      <li><span class="Label">When</span> <?php echo FormatString($WhenFormat, array(
         'ShowDates' => $ShowDates,
         'AllDay'    => ($AllDay) ? Wrap(T('all day'), 'span', array('class' => 'Tag Tag-AllDay')) : ''
      )); ?>
      </li>
      <li><span class="Label">Where</span> <?php echo $this->Data('Event.Location'); ?> <span class="Tip">( <?php echo Wrap($TimezoneAbbr, 'span', array('class' => 'Timezone', 'title' => $TimezoneLabel)); ?> )</span></li>
      <?php if ($this->Data('Group')): ?>
         <li><span class="Label">Group</span> <?php echo Anchor(GetValue('Name', $this->Data('Group')), GroupUrl($this->Data('Group'))); ?></li>
      <?php endif; ?>
      <li><span class="Label">Organizer</span> <?php echo UserAnchor($this->Data('Event.Organizer')); ?></li>
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