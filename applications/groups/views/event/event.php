<?php
   $header = new GroupHeaderModule($this->data('Group'));
   echo $header;
?>
<div class="Event-Header PageTitle">
   <!-- Edit/Delete Buttons -->
   <div class="Options">
       <?php writeEventOptions(); ?>
   </div>
   <h1 class="Event-Title"><?php echo htmlspecialchars($this->Data('Event.Name')); ?></h1>
</div>

<div class="P EventInfo" data-eventid="<?php echo $this->Data('Event.EventID'); ?>">
   <ul>
      <li class="When">
         <span class="Label"><?php echo T('When'); ?></span><span class="FieldInfo"><?php
            echo $this->formatEventDates(
                $this->data('Event.DateStarts'),
                $this->data('Event.DateEnds')
            );
            ?>
         </span>

      </li>
      <li class="Where">
         <span class="Label"><?php echo T('Where'); ?></span><span class="FieldInfo"><?php echo htmlspecialchars($this->Data('Event.Location')); ?></span>
      </li>

      <?php if ($this->Data('Group')): ?>
         <li class="EventGroup"><span class="Label"><?php echo T('Group'); ?></span><span class="FieldInfo"><?php echo Anchor(GetValue('Name', $this->Data('Group')), GroupUrl($this->Data('Group'))); ?></li>
      <?php endif; ?>

      <li class="Organizer"><span class="Label"><?php echo T('Organizer'); ?></span><span class="FieldInfo"><?php echo UserAnchor($this->Data('Event.Organizer')); ?></span></li>
   </ul>

   <div class="Body"><?php echo Gdn_Format::To($this->Data('Event.Body'), $this->Data('Event.Format')); ?></div>
</div>
<?php if (!EventModel::isEnded($this->Data('Event')))  { ?>
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
<div class="FormTitleWrapper">
   <h2><?php echo T("Who's going?"); ?></h2>
<?php } else  { ?>
  <h2><?php echo T("Who went?"); ?></h2>
<?php } ?>
   <div class="Attendees" id="EventAttendees">
      <?php echo $this->FetchView('attendees'); ?>
   </div>
</div>
