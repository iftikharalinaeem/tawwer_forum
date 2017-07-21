<?php if (!defined('APPLICATION')) exit(); ?>

  <div class="Box-Events">
    <h2><?php echo T($this->Data('Title')); ?></h2>
    <?php $EmptyMessage = T('GroupEmptyEvents', "Aw snap, no events are coming up."); ?>
    <?php WriteEventList($this->Data('Events'), $this->Data('Group'), $EmptyMessage, $this->Data('Button', true)); ?>
  </div>

<?php

/**
 * Output an HTML list of events or an empty state message.
 *
 * @param array $events
 * @param string $emptyMessage What to show when there's no content.
 */
function WriteEventList($events, $group = null, $emptyMessage = '', $button = true) {
    $groupID = GetValue('GroupID', $group, '');
    if (GroupPermission('Member') && $button) {
        echo '<div class="Button-Controls">';
        echo ' '.Anchor(T('New Event'), "/event/add/{$groupID}", 'Button Primary Group-NewEventButton').' ';
        echo '</div>';
    }

    if (!$events)
        WriteEmptyState($emptyMessage);
    else {
        echo '<ul class="NarrowList DataList-Events">';
        foreach ($events as $event) {
            echo '<li>';
            WriteEventCard($event);
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
function WriteEventCard($event) {
    $dateStarts = new DateTime($event['DateStarts'], Gdn::session()->getTimeZone());
    if (Gdn::Session()->IsValid() && $hourOffset = Gdn::Session()->User->HourOffset)
        $dateStarts->modify("{$hourOffset} hours");

    echo '<div class="Event">';
    if (GetValue('Rich', $event)) {

    } else {

        echo DateTile($dateStarts->format('Y-m-d'));
        echo '<h3 class="Event-Title">'.Anchor(Gdn_Format::Text($event['Name']), EventUrl($event));
        if ($dateStarts->format('g:ia') != '12:00am')
            echo ' <span class="Event-Time MItem">'.$dateStarts->format('g:ia').'</span>';
        echo '</h3>';

        echo '<div class="Event-Location">'.Gdn_Format::Text($event['Location']).'</div>';
        echo '<p class="Event-Description">'.SliceParagraph(Gdn_Format::Text($event['Body']), 100).'</p>';

    }
    echo '</div>';
}
