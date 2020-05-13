<?php if (!defined('APPLICATION')) exit(); ?>

  <div class="Box-Events">
    <h2><?php echo t($this->data('Title')); ?></h2>
    <?php $EmptyMessage = t('GroupEmptyEvents', "Aw snap, no events are coming up."); ?>
    <?php writeEventList($this->data('Events'), $this->data('Group'), $EmptyMessage, $this->data('Button', true)); ?>
  </div>

<?php

/**
 * Output an HTML list of events or an empty state message.
 *
 * @param array $events
 * @param string $emptyMessage What to show when there's no content.
 */
function writeEventList($events, $group = null, $emptyMessage = '', $button = true) {
    $groupID = getValue('GroupID', $group, '');
    if (groupPermission('Member') && $button) {
        echo '<div class="Button-Controls">';
        echo ' '.anchor(t('New Event'), "/event/add/{$groupID}", 'Button Primary Group-NewEventButton').' ';
        echo '</div>';
    }

    if (!$events)
        writeEmptyState($emptyMessage);
    else {
        echo '<ul class="NarrowList DataList-Events">';
        foreach ($events as $event) {
            echo '<li>';
            writeEventCard($event);
            echo '</li>';
        }
        echo '</ul>';
    }
}

/**
 * Write an event card
 *
 * Optionally write rich listing
 *
 * @param array $event
 */
function writeEventCard($event) {
    $dateStarts = new DateTime($event['DateStarts'], Gdn::session()->getTimeZone());
    if (Gdn::session()->isValid() && $hourOffset = Gdn::session()->User->HourOffset)
        $dateStarts->modify("{$hourOffset} hours");

    echo '<div class="Event">';
    if (getValue('Rich', $event)) {

    } else {

        echo dateTile($dateStarts->format('Y-m-d'));
        /** @var EventModel $eventModel */
        $eventModel = \Gdn::getContainer()->get(EventModel::class);
        echo '<h3 class="Event-Title">'.anchor(Gdn_Format::text($event['Name']), $eventModel->eventUrl($event));
        if ($dateStarts->format('g:ia') != '12:00am')
            echo ' <span class="Event-Time MItem">'.$dateStarts->format('g:ia').'</span>';
        echo '</h3>';

        echo '<div class="Event-Location">'.Gdn_Format::text($event['Location']).'</div>';
        echo '<p class="Event-Description">'.sliceParagraph(Gdn_Format::text($event['Body']), 100).'</p>';

    }
    echo '</div>';
}
