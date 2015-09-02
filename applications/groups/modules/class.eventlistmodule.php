<?php

/**
 * Groups Application - Group List Module
 *
 */

/**
 * Class EventListModule
 *
 * Consolidates the data and renders the view for a event list. Event lists appear on the group and events/group pages.
 */
class EventListModule extends Gdn_Module {

    /**
     * @var array The events to render. (An array of event arrays.)
     */
    public $events;
    /**
     * @var array The group the events belong to.
     */
    public $group;
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
     * @var bool Whether to provide a link to see all of the events (a link to events/group).
     */
    public $showMore;
    /**
     * @var bool Whether to show the 'New Event' button.
     */
    public $withNewButton;
    /**
     * @var bool Whether to show the 'New Event' button.
     */
    public $withJoinButtons;
    /**
     * @var bool Whether to show the 'New Event' button.
     */
    public $withOptions;

    /**
     * Construct the EventListModule object.
     *
     * @param array $events The events to render. (An array of event arrays.)
     * @param array $group The group the events belong to.
     * @param string $title The event section title (i.e., 'Upcoming Events').
     * @param string $emptyMessage The message to display if there are no events.
     * @param bool $showMore Whether to provide a link to see all of the events (a link to events/group).
     * @param string $layout The layout type, either 'modern' or 'table'.
     * @param bool $withNewButton Whether to show the 'New Event' button.
     */
    public function __construct($events, $group, $title = '', $emptyMessage = '', $showMore = true, $layout = '', $withNewButton = true, $withJoinButtons = true, $withOptions = true) {
        $this->events = $events;
        $this->group = $group;
        $this->title = $title;
        $this->emptyMessage = $emptyMessage;
        $this->showMore = $showMore;
        $this->layout = $layout ?: c('Vanilla.Discussions.Layout', 'modern');
        $this->withNewButton = $withNewButton;
        $this->withJoinButtons = $withJoinButtons;
        $this->withOptions = $withOptions;
        $this->_ApplicationFolder = 'groups';
    }

    /**
     * Returns an array of event options that the user has permissions for.
     *
     * @param $event The event to get options for.
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
     * Returns the event button dropdown.
     *
     * @param $event The event to get buttons for.
     * @return array The event buttons.
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
    }

    /**
     * @param $group
     * @return array
     */
    public function getEventListButtons($group) {
        $groupID = val('GroupID', $group, '');
        $buttons = array();
        if (GroupPermission('Member')) {
            $newEventButton['text'] = t('New Event');
            $newEventButton['url'] = url("/event/add/{$groupID}");
            $newEventButton['cssClass'] = 'Button Primary Group-NewEventButton';
            $buttons[] = $newEventButton;
        }
        return $buttons;
    }

    /**
     * @param $layout
     * @param $events
     * @param $group
     * @param $heading
     * @param string $emptyMessage
     * @param bool $withButtons
     * @return mixed
     */
    public function getEventsInfo($layout, $events, $group, $heading, $emptyMessage = '', $withButtons = true) {

        $eventList['layout'] = $layout;
        $eventList['emptyMessage'] = $emptyMessage;
        $eventList['title'] = $heading;
        $eventList['cssClass'] = 'EventList';

        if ($this->showMore) {
            $eventList['moreLink'] = sprintf(T('All %s...'), T('Events'));
            $eventList['moreUrl'] = url(combinePaths(array("/events/group/", GroupSlug($group))));
            $eventList['moreCssClass'] = 'More';
        }

        if ($withButtons) {
            $eventList['buttons'] = $this->getEventListButtons($group);
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
     * @param $event
     * @param $layout
     * @param $withOptions
     * @return mixed
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
     * @param $item
     * @param $event
     * @param $dateStarts
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
     * Render events.
     *
     * @return string
     */
    public function toString() {
        $this->events = $this->getEventsInfo($this->layout, $this->events, $this->group, $this->title, $this->emptyMessage, $this->withNewButton);
        $controller = new Gdn_Controller();
        $controller->setData('list', $this->events);
        return $controller->fetchView('eventlist', 'modules', 'groups');
    }
}
