<?php use Vanilla\Groups\Models\EventPermissions;

if (!defined('APPLICATION')) exit();

if (!function_exists('WriteEventButtons')) :
    /**
     * Output action buttons to edit/delete an Event.
     *
     * @param array $event Optional. Uses data array's Event if none is provided.
     */
    function writeEventButtons($event = null) {
        if (!$event)
            $event = Gdn::controller()->data('Event');
        if (!$event)
            return;

        echo '<div class="Event-Buttons">';

        $options = [];

        if (eventPermission('Edit', $event)) {
            $options['Edit'] = ['Text' => t('Edit'), 'Url' => eventUrl($event, 'edit')];
            $options['Delete'] = ['Text' => t('Delete'), 'Url' => eventUrl($event, 'delete'), 'CssClass' => 'Popup'];
        }

        if (count($options))
            echo buttonDropDown($options, 'Button DropRight Event-OptionsButton', sprite('SpOptions', 'Sprite16'));

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
        deprecated('Implicit event passing');
        $event = Gdn::controller()->data('Event');
    }
    if (!$event) {
        return;
    }
    $options = [];

    /** @var EventModel $eventModel */
    $eventModel = \Gdn::getContainer()->get(EventModel::class);

    if ($eventModel->hasEventPermission(EventPermissions::EDIT, $event['EventID'])) {
        $options['Edit'] = ['Text' => t('Edit'), 'Url' => eventUrl($event, 'edit')];
        $options['Delete'] = ['Text' => t('Delete'), 'Url' => eventUrl($event, 'delete'), 'CssClass' => 'Popup'];
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
function hasEndDate($event) {
    // Event has no end date if start date equals end date.
    return val('DateEnds', $event) !== val('DateStarts', $event);
}

endif;
