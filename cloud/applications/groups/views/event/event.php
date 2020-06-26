<?php if (!defined('APPLICATION')) exit();

/** @var EventController $this */

if ($this->data('Group')) {
    $header = new GroupHeaderModule($this->data('Group'));
    echo $header;
}
?>
<div class="Event-Header PageTitle">
    <!-- Edit/Delete Buttons -->
    <div class="Options">
         <?php writeEventOptions($this->data('Event')); ?>
    </div>
    <h1 class="Event-Title"><?php echo htmlspecialchars($this->data('Event.Name')); ?></h1>
</div>

<div class="P EventInfo" data-eventid="<?php echo $this->data('Event.EventID'); ?>">
    <ul>
        <li class="When">
            <span class="Label"><?php echo t('When'); ?></span><span class="FieldInfo"><?php
                echo $this->formatEventDates(
                     $this->data('Event.DateStarts'),
                     $this->data('Event.DateEnds')
                );
                ?>
            </span>

        </li>
        <li class="Where">
            <span class="Label"><?php echo t('Where'); ?></span><span class="FieldInfo"><?php echo htmlspecialchars($this->data('Event.Location')); ?></span>
        </li>

        <?php if ($this->data('Group')): ?>
            <li class="EventGroup"><span class="Label"><?php echo t('Group'); ?></span><span class="FieldInfo"><?php echo anchor(htmlspecialchars($this->data('Group.Name')), groupUrl($this->data('Group'))); ?></li>
        <?php endif; ?>

        <li class="Organizer"><span class="Label"><?php echo t('Organizer'); ?></span><span class="FieldInfo"><?php echo userAnchor($this->data('Event.Organizer')); ?></span></li>
    </ul>

    <div class="Body"><?php echo \Gdn::formatService()->renderHTML($this->data('Event.Body'), $this->data('Event.Format')); ?></div>
</div>
<?php if (!EventModel::isEnded($this->data('Event')))  { ?>
<div class="FormWrapper StructuredForm Attending">
  <div class="P Attending">
     <?php echo $this->Form->label('Are you attending this event?'); ?>
     <div><?php echo $this->Form->radioList('Attending', [
          'Yes'         => 'Yes',
          'No'          => 'No',
          'Maybe'      => 'Maybe'
        ], ['class' => 'EventAttending']); ?></div>
  </div>
</div>
<div class="FormTitleWrapper">
    <h2><?php echo t("Who's going?"); ?></h2>
<?php } else  { ?>
  <h2><?php echo t("Who went?"); ?></h2>
<?php } ?>
    <div class="Attendees" id="EventAttendees">
        <?php echo $this->fetchView('attendees'); ?>
    </div>
</div>
