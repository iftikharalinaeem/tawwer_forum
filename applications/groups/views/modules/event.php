<?php if (!defined('APPLICATION')) exit(); ?>

  <div class="Box-Events">
    <h2><?php echo T($this->Data('Title')); ?></h2>
    <?php $EmptyMessage = T('GroupEmptyEvents', "Aw snap, no events are coming up."); ?>
    <?php WriteEventList($this->Data('Events'), $this->Data('Group'), $EmptyMessage, $this->Data('Button', TRUE)); ?>
  </div>

<?php

/**
 * Output an HTML list of events or an empty state message.
 *
 * @param array $Events
 * @param string $EmptyMessage What to show when there's no content.
 */
function WriteEventList($Events, $Group = NULL, $EmptyMessage = '', $Button = TRUE) {
    $GroupID = GetValue('GroupID', $Group, '');
    if (GroupPermission('Member') && $Button) {
        echo '<div class="Button-Controls">';
        echo ' '.Anchor(T('New Event'), "/event/add/{$GroupID}", 'Button Primary Group-NewEventButton').' ';
        echo '</div>';
    }

    if (!$Events)
        WriteEmptyState($EmptyMessage);
    else {
        echo '<ul class="NarrowList DataList-Events">';
        foreach ($Events as $Event) {
            echo '<li>';
            WriteEventCard($Event);
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
 * @param array $Event
 */
function WriteEventCard($Event) {
    $UTC = new DateTimeZone('UTC');
    $Timezone = new DateTimeZone($Event['Timezone']);
    $DateStarts = new DateTime($Event['DateStarts'], $UTC);
    if (Gdn::Session()->IsValid() && $HourOffset = Gdn::Session()->User->HourOffset)
        $DateStarts->modify("{$HourOffset} hours");

    echo '<div class="Event">';
    if (GetValue('Rich', $Event)) {

    } else {

        echo DateTile($DateStarts->format('Y-m-d'));
        echo '<h3 class="Event-Title">'.Anchor(Gdn_Format::Text($Event['Name']), EventUrl($Event));
        if ($DateStarts->format('g:ia') != '12:00am')
            echo ' <span class="Event-Time MItem">'.$DateStarts->format('g:ia').'</span>';
        echo '</h3>';

        echo '<div class="Event-Location">'.Gdn_Format::Text($Event['Location']).'</div>';
        echo '<p class="Event-Description">'.SliceParagraph(Gdn_Format::Text($Event['Body']), 100).'</p>';

    }
    echo '</div>';
}
