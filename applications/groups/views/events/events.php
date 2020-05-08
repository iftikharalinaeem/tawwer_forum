<?php if (!defined('APPLICATION')) exit();

if ($this->data('Group')) {
    $header = new GroupHeaderModule($this->data('Group'));
    echo $header;
}
echo '<h1>'.$this->data('Title').'</h1>';

$eventList = new EventListModule($this->data('UpcomingEvents'), t('Upcoming Events'), t('GroupEmptyUpcomingEvents', "Aw snap, no events are coming up."));
if ($this->data('Group') && groupPermission('Member', val('GroupID', $this->data('Group')))) {
    $eventList->addNewEventButton($this->data('NewButtonId'));
}
echo $eventList;

$eventList = new EventListModule($this->data('RecentEvents'), t('Recent Events'), t('GroupEmptyRecentEvents', "There aren't any recent events to show."));
echo $eventList;

