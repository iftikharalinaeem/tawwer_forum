<?php if (!defined('APPLICATION')) exit();

echo '<h1>'.$this->Data('Title').'</h1>';

$eventList = new EventListModule($this->Data('UpcomingEvents'), $this->Data('Group'), t('Upcoming Events'), t('GroupEmptyUpcomingEvents', "Aw snap, no events are coming up."), false);
echo $eventList;

$eventList = new EventListModule($this->Data('RecentEvents'), $this->Data('Group'), t('Recent Events'), t('GroupEmptyRecentEvents', "There aren't any recent events to show."), false);
echo $eventList;

