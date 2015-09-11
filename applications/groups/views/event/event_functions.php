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

if (!function_exists('writeEventOptions')) :
  /**
   * Writes event edit options.
   *
   * @param array $event The event to write options for.
   */
function writeEventOptions($event = null) {
   if (!$event) {
      $event = Gdn::Controller()->data('Event');
   }
   if (!$event) {
      return;
   }
   $options = array();

   if (EventPermission('Edit', $event)) {
      $options['Edit'] = array('Text' => t('Edit'), 'Url' => EventUrl($event, 'edit'));
      $options['Delete'] = array('Text' => t('Delete'), 'Url' => EventUrl($event, 'delete'), 'CssClass' => 'Popup');
   }

   if (count($options)) {
      writeGroupOptions($options);
   }
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
