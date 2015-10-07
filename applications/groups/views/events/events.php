<?php if (!defined('APPLICATION')) exit();
echo Gdn_Theme::module('GroupHeaderModule');

echo '<h1>'.$this->Data('Title').'</h1>';

$eventList = new EventListModule($this->Data('UpcomingEvents'), t('Upcoming Events'), t('GroupEmptyUpcomingEvents', "Aw snap, no events are coming up."));
if ($this->Data('Group') && GroupPermission('Member', val('GroupID', $this->Data('Group')))) {
  $eventList->addNewEventButton($this->Data('NewButtonId'));
}
echo $eventList;

$eventList = new EventListModule($this->Data('RecentEvents'), t('Recent Events'), t('GroupEmptyRecentEvents', "There aren't any recent events to show."));
echo $eventList;

