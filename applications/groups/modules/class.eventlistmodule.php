<?php

/**
 * Groups Application - Event List Module
 *
 */

/**
 * Class EventListModule
 *
 * Consolidates the data and renders the view for a event list. Currently, event lists appear on the group and events/group pages.
 */
class EventListModule extends Gdn_Module {

    /**
     * @var array The events to render. (An array of event arrays.)
     */
    public $events;
    /**
     * @var string The event section title (i.e., 'Upcoming Events').
     */
    public $title;
    /**
     * @var string The message to display if there are no events.
     */
    public $emptyMessage;
    /**
     * @var string The layout type, either 'modern' or 'table'.
     */
    public $layout;
    /**
     * @var bool Whether to provide a link to see all of the events.
     */
    public $showMore;
    /**
     * @var string The url for the 'show more' link.
     */
    public $showMoreUrl = '';
    /**
     * @var bool Whether to show the 'New Event' button.
     */
    public $addNewEventButton;
    /**
     * @var string The url for the 'New Event' button.
     */
    public $newEventUrl = '';
    /**
     * @var bool Whether to show the event's 'RSVP' dropdown.
     */
    public $withJoinButtons;
    /**
     * @var bool Whether to show the event edit options.
     */
    public $withOptions;

    /**
     * Construct the EventListModule object.
     *
     * @param array $events The events to render. (An array of event arrays.)
     * @param string $title The event section title (i.e., 'Upcoming Events').
     * @param string $emptyMessage The message to display if there are no events.
     * @param string $layout The layout type, either 'modern' or 'table'.
     * @param bool $withJoinButtons Whether to show the event's 'RSVP' dropdown.
     * @param bool $withOptions Whether to show the event edit options.
     * @internal param bool $showMore Whether to provide a link to see all of the events.
     * @internal param bool $withNewButton Whether to show the 'New Event' button.
     */
    public function __construct($events, $title = '', $emptyMessage = '', $layout = '', $withJoinButtons = true, $withOptions = true) {
        $this->events = $events;
        $this->title = $title;
        $this->emptyMessage = $emptyMessage;
        $this->layout = $layout ?: c('Vanilla.Discussions.Layout', 'modern');
        $this->withJoinButtons = $withJoinButtons;
        $this->withOptions = $withOptions;
        $this->_ApplicationFolder = 'groups';
    }

    /**
     * Returns an array of event options that the user has permissions for.
     *
     * @param array $event The event to get options for.
     * @return array The event options.
     */
    public function getEventOptions($event) {
        $options = array();
        if (EventPermission('Edit', $event)) {
            $options[] = array('Text' => sprintf(t('Edit %s'), t('Event')), 'Url' => EventUrl($event, 'edit'));
            $options[] = array('Text' => sprintf(t('Delete %s'), t('Event')), 'Url' => EventUrl($event, 'delete'), 'CssClass' => 'Popup');
        }
        return $options;
    }

    /**
     * Compiles data for the event button RSVP dropdown.
     *
     * @param array $event The event to get the dropdown menu for.
     * @return array The event's RSVP dropdown.
     */
    public function getEventDropdown($event) {
        if (EventPermission('Member', $event) && !EventModel::isEnded($event)) {
            $eventModel = new EventModel();
            $status = $eventModel->IsInvited(Gdn::session()->UserID, val('EventID', $event));
            $statuses = array('Yes' => t('Attending'), 'No' => t('Not Attending'), 'Maybe' => t('Maybe'));
            if (!array_key_exists($status, $statuses)) {
                $realStatus = t('RSVP');
                $status = 'rsvp';
            } else {
                $realStatus = val($status, $statuses);
                unset($statuses[$status]);
            }
            $dropdown = array();
            $trigger['text'] = '<span class="js-status" data-name="'.$status.'">'.$realStatus.'</span>';
            $trigger['cssClass'] = '';
            $dropdown['trigger'] = $trigger;
            foreach ($statuses as $i => $option) {
                $dropdown['options'][$i]['text'] = $option;
                $dropdown['options'][$i]['cssClass'] = 'EventAttending';
                $dropdown['options'][$i]['dataName'] = $i;
            }
            return $dropdown;
        }
        return array();
    }

    /**
     * Compiles the data for the buttons for an event list.
     *
     * @param string $url The url for the new event button.
     * @return array The buttons' data.
     */
    public function getEventListButtons($url) {
        $buttons = array();
        $newEventButton['text'] = t('New Event');
        $newEventButton['url'] = $url;
        $newEventButton['cssClass'] = 'Button Primary NewEventButton';
        $buttons['newEvent'] = $newEventButton;
        return $buttons;
    }

    /**
     * Enables the 'show more' link after the event list.
     *
     * @param string $url The url for the 'show more' link.
     */
    public function showMore($url) {
        $this->showMore = true;
        $this->showMoreUrl = $url;
    }

    /**
     * Enables the 'New Event' button before the event list.
     *
     * @param $id The group id to add the event to, if one exists.
     */
    public function addNewEventButton($id = '') {
        $this->addNewEventButton = true;
        $this->newEventUrl = url("/event/add/{$id}");
    }

    /**
     * Collect and organize the data for the event list.
     *
     * @param string $layout The layout type, either 'modern' or 'table'.
     * @param array $events The events to render. (An array of event arrays.)
     * @param string $heading
     * @param string $emptyMessage The message to display if there are no events.
     * @param string $showMoreUrl The url for the show more link, if one exists.
     * @param string $newEventUrl The url for the new event button, if one exists.
     * @return array An event list data array.
     */
    public function getEventsInfo($layout, $events, $heading, $emptyMessage, $showMoreUrl, $newEventUrl) {

        $eventList['layout'] = $layout;
        $eventList['emptyMessage'] = $emptyMessage;
        $eventList['title'] = $heading;
        $eventList['cssClass'] = 'EventList';

        if ($showMoreUrl) {
            $eventList['moreLink'] = sprintf(T('All %s...'), T('Events'));
            $eventList['moreCssClass'] = 'More';
            $eventList['moreUrl'] =  $showMoreUrl;
        }

        if ($newEventUrl) {
            $eventList['buttons'] = $this->getEventListButtons($newEventUrl);
        }

        if ($layout == 'table') {
            $eventList['columns'][0]['columnLabel'] = t('Event');
            $eventList['columns'][0]['columnCssClass'] = 'EventTitle';
            $eventList['columns'][1]['columnLabel'] = t('Location');
            $eventList['columns'][1]['columnCssClass'] = 'EventLocation';
            $eventList['columns'][2]['columnLabel'] = t('Date');
            $eventList['columns'][2]['columnCssClass'] = 'EventDate';
        }

        foreach ($events as $event) {
            $eventList['items'][] = $this->getEventInfo($event, $layout, $this->withJoinButtons, $this->withOptions);
        }

        return $eventList;
    }

    /**
     * Collect and organize the data for an event item in the event list.
     *
     * @param array $event The event item.
     * @param string $layout The layout type, either 'modern' or 'table'.
     * @param bool $withJoinButtons Whether to show the event's 'RSVP' dropdown.
     * @param bool $withOptions Whether to show the event edit options.
     * @return array A data array representing an event item in an event list.
     */
    public function getEventInfo($event, $layout, $withJoinButtons = true, $withOptions = true) {

        $utc = new DateTimeZone('UTC');
        $dateStarts = new DateTime($event['DateStarts'], $utc);
        if (Gdn::session()->isValid() && $hourOffset = Gdn::session()->User->HourOffset) {
            $dateStarts->modify("{$hourOffset} hours");
        }

        $item['id'] = val('EventID', $event);
        $item['dateTile'] = true;
        $item['monthTile'] = strftime('%b', $dateStarts->getTimestamp());
        $item['dayTile'] = $dateStarts->format('j');
        $item['text'] = sliceParagraph(Gdn_Format::plainText(val('Body', $event), val('Format', $event)), 100);
        $item['textCssClass'] = 'EventDescription';
        $item['heading'] = Gdn_Format::text(val('Name', $event));
        $item['url'] = EventUrl($event);
        $item['metaCssClass'] = '';
        $item['cssClass'] = 'Event event js-event';
        if ($withOptions) {
            $item['options'] = $this->getEventOptions($event);
        }
        if ($withJoinButtons) {
            $item['buttonDropdown'] = $this->getEventDropdown($event);
        }
        if ($layout == 'table') {
            $this->getEventTableItem($item, $event, $dateStarts);
        }
        if ($layout != 'table') {
            $startTime = $dateStarts->format('g:ia') == '12:00am' ? '' : ' '.$dateStarts->format('g:ia');
            $item['meta']['location']['text'] = Gdn_Format::text($event['Location']);
            $item['meta']['date']['text'] = $dateStarts->format("F j, Y").$startTime;
        }
        return $item;
    }


    /**
     * Adds the row data for an event item in a table layout group list.
     *
     * @param array $item The working event item for an event list.
     * @param array $event The event array we're parsing.
     * @param DateTime $dateStarts The starting date of the event.
     */
    public function getEventTableItem(&$item, $event, $dateStarts) {
        $item['rows']['main']['type'] = 'main';
        $item['rows']['main']['cssClass'] = 'EventTitle';

        $item['rows']['location']['type'] = 'default';
        $item['rows']['location']['text'] = Gdn_Format::text($event['Location']);
        $item['rows']['location']['cssClass'] = 'EventLocation';

        $startTime = $dateStarts->format('g:ia') == '12:00am' ? '' : ' '.$dateStarts->format('g:ia');
        $item['rows']['date']['type'] = 'default';
        $item['rows']['date']['text'] = $dateStarts->format("F j, Y").' '.$startTime;
        $item['rows']['date']['cssClass'] = 'EventDate';
    }

    /**
     * Renders the event list.
     *
     * @return string HTML view
     */
    public function toString() {
        $this->events = $this->getEventsInfo($this->layout, $this->events, $this->title, $this->emptyMessage, $this->showMoreUrl, $this->newEventUrl);
        $controller = new Gdn_Controller();
        $controller->setData('list', $this->events);
        return $controller->fetchView('eventlist', 'modules', 'groups');
    }
}
