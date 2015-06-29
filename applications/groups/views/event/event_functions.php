<?php if (!defined('APPLICATION')) exit();


if (!function_exists('WriteEventButtons')) :
/**
 * Output action buttons to edit/delete an Event.
 *
 * @param array $Event Optional. Uses data array's Event if none is provided.
 */
function WriteEventButtons($Event = NULL) {
   if (!$Event)
      $Event = Gdn::Controller()->Data('Event');
   if (!$Event)
      return;

   echo '<div class="Event-Buttons">';

   $Options = array();

   if (EventPermission('Edit', $Event)) {
      $Options['Edit'] = array('Text' => T('Edit'), 'Url' => EventUrl($Event, 'edit'));
      $Options['Delete'] = array('Text' => T('Delete'), 'Url' => EventUrl($Event, 'delete'), 'CssClass' => 'Popup');
   }

   if (count($Options))
      echo ButtonDropDown($Options, 'Button DropRight Event-OptionsButton', Sprite('SpOptions', 'Sprite16'));

   echo '</div>';
}
endif;

function WriteEventOptions($event = null) {
   if (!$event) {
      $event = Gdn::Controller()->Data('Event');
   }
   if (!$event) {
      return;
   }
   $options = new DropdownModule('event-'.val('EventID', $event).'-options');
   $options->setTrigger('', 'button', 'btn-link', 'icon-cog')
      ->addLink(T('Edit'), EventUrl($event, 'edit'), EventPermission('Edit', $event), 'edit')
      ->addLink(T('Delete'), EventUrl($event, 'delete'), EventPermission('Edit', $event), 'delete', array(), '', '', '', false, 'Popup');
   $options->setView('dropdown-legacy');
   return $options->toString();
}


if (!function_exists('WriteEventCard')) :
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
endif;

if (!function_exists('HasEndDate')) :

/**
 * Check whether event ends.
 *
 * @param array $Event The event.
 * @return bool Returns true if the event has an end date or false otherwise.
 */
function HasEndDate($Event) {
   // Event has no end date if start date equals end date.
   return val('DateEnds', $Event) !== val('DateStarts', $Event);
}

endif;

if (!function_exists('WriteEventCards')) :
/**
 * Write a list of events out as cards.
 *
 * @param array $Events
 * @param string $EmptyMessage
 */
function WriteEventCards($Events, $EmptyMessage = '') {
   if (!$Events)
      WriteEmptyState($EmptyMessage);
   else {
      echo '<div class="Cards Cards-Events">';
      foreach ($Events as $Event) {
         WriteEventCard($Event);
      }
      echo '</div>';
   }
}
endif;
