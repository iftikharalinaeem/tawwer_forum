<?php if (!defined('APPLICATION')) exit();

if (!function_exists('WriteEventButtons')) :
    /**
     * Output action buttons to edit/delete an Event.
     *
     * @param array $event Optional. Uses data array's Event if none is provided.
     */
    function WriteEventButtons($event = null) {
        if (!$event)
            $event = Gdn::Controller()->Data('Event');
        if (!$event)
            return;

        echo '<div class="Event-Buttons">';

        $options = [];

        if (EventPermission('Edit', $event)) {
            $options['Edit'] = ['Text' => T('Edit'), 'Url' => EventUrl($event, 'edit')];
            $options['Delete'] = ['Text' => T('Delete'), 'Url' => EventUrl($event, 'delete'), 'CssClass' => 'Popup'];
        }

        if (count($options))
            echo ButtonDropDown($options, 'Button DropRight Event-OptionsButton', Sprite('SpOptions', 'Sprite16'));

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
    $options = [];

    if (EventPermission('Edit', $event)) {
        $options['Edit'] = ['Text' => t('Edit'), 'Url' => EventUrl($event, 'edit')];
        $options['Delete'] = ['Text' => t('Delete'), 'Url' => EventUrl($event, 'delete'), 'CssClass' => 'Popup'];
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
 * @param array $event The event.
 * @return bool Returns true if the event has an end date or false otherwise.
 */
function HasEndDate($event) {
    // Event has no end date if start date equals end date.
    return val('DateEnds', $event) !== val('DateStarts', $event);
}

endif;
