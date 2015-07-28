<?php

/**
 * Groups Application - Group List Module
 *
 */

class EventListModule extends Gdn_Module {

  public $events;
  public $group;
  public $title;
  public $emptyMessage;
  public $view;
  public $showMore;
  public $withButtons;

  public function __construct($events, $group, $title = '', $emptyMessage = '', $showMore = true, $view = '', $withButtons = true) {
    $this->events = $events;
    $this->group = $group;
    $this->title = $title;
    $this->emptyMessage = $emptyMessage;
    $this->showMore = $showMore;
    $this->view = $view ?: c('Vanilla.Discussions.Layout', 'modern');
    $this->withButtons = $withButtons;
    $this->_ApplicationFolder = 'groups';
  }

  public function getEventOptions($event) {
    $options = array();
    if (EventPermission('Edit', $event)) {
      $options[] = array('Text' => T('Edit'), 'Url' => EventUrl($event, 'edit'));
      $options[] = array('Text' => T('Delete'), 'Url' => EventUrl($event, 'delete'), 'CssClass' => 'Popup');
    }
    return $options;
  }

  public function getEventListButtons($group) {
    $groupID = GetValue('GroupID', $group, '');
    $buttons = array();
    if (GroupPermission('Member')) {
      $newEventButton['text'] = t('New Event');
      $newEventButton['url'] = Url("/event/add/{$groupID}");
      $newEventButton['cssClass'] = 'Button Primary Group-NewEventButton';
      $buttons[] = $newEventButton;
    }
    return $buttons;
  }

  public function getEventsInfo($view, $events, $group, $heading, $emptyMessage = '', $withButtons = true) {

    $eventList['view'] = $view;
    $eventList['emptyMessage'] = $emptyMessage;
    $eventList['title'] = $heading;
    $eventList['cssClass'] = 'EventList';

    if ($this->showMore) {
      $eventList['moreLink'] = sprintf(T('All %s...'), T('Events'));
      $eventList['moreUrl'] = Url(CombinePaths(array("/events/group/", GroupSlug($group))));
      $eventList['moreCssClass'] = 'More';
    }

    if ($withButtons) {
      $eventList['buttons'] = $this->getEventListButtons($group);
    }

    if ($view == 'table') {
      $eventList['columns'][0]['columnLabel'] = t('Event');
      $eventList['columns'][0]['columnCssClass'] = 'EventTitle';
      $eventList['columns'][1]['columnLabel'] = t('Location');
      $eventList['columns'][1]['columnCssClass'] = 'EventLocation';
      $eventList['columns'][2]['columnLabel'] = t('Date');
      $eventList['columns'][2]['columnCssClass'] = 'EventDate';
    }

    foreach ($events as $event) {
      $eventList['items'][] = $this->getEventInfo($event, $view, true);
    }

    return $eventList;
  }

  public function getEventInfo($event, $view, $withOptions) {

    $utc = new DateTimeZone('UTC');
    $dateStarts = new DateTime($event['DateStarts'], $utc);
    if (Gdn::Session()->IsValid() && $hourOffset = Gdn::Session()->User->HourOffset) {
      $dateStarts->modify("{$hourOffset} hours");
    }

    $item['dateTile'] = true;
    $item['monthTile'] = strftime('%b', $dateStarts->getTimestamp());
    $item['dayTile'] = $dateStarts->format('j');
    $item['text'] = SliceParagraph(Gdn_Format::plainText(val('Body', $event), val('Format', $event)), 100);
    $item['textCssClass'] = 'EventDescription';
    $item['heading'] = Gdn_Format::text(val('Name', $event));
    $item['url'] = EventUrl($event);
    $item['metaCssClass'] = '';

    if ($view != 'table') {
      $startTime = $dateStarts->format('g:ia') == '12:00am' ? '' : ' '.$dateStarts->format('g:ia');
      $item['meta']['location']['text'] = Gdn_Format::text($event['Location']);
      $item['meta']['date']['text'] = $dateStarts->format("F j, Y").$startTime;
    }

    if ($withOptions) {
      $item['options'] = $this->getEventOptions($event);
    }

    if ($view == 'table') {
      $this->getEventTableItem($item, $event, $dateStarts);
    }

    return $item;
  }


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
   * Render groups
   *
   * @return type
   */
  public function toString() {
    $this->events = $this->getEventsInfo($this->view, $this->events, $this->group, $this->title, $this->emptyMessage, $this->withButtons);
    $controller = new Gdn_Controller();
    $controller->setData('list', $this->events);
    return $controller->fetchView('eventlist', 'modules', 'groups');
  }
}
