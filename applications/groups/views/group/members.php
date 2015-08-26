<?php if (!defined('APPLICATION')) exit();

if (in_array($this->data('Filter'), array('', 'leaders'))) {
  $eventList = new MemberListModule($this->data('Leaders'), $this->data('Group'), t('Leaders'), t('GroupEmptyLeaders', "There are no group leaders."));
  echo $eventList;
}

if (in_array($this->data('Filter'), array('', 'members'))) {
  $eventList = new MemberListModule($this->data('Members'), $this->data('Group'), t('Members'), t('GroupEmptyMembers', "There are no group members yet."));
  echo $eventList;
}

PagerModule::write(array('Url' => GroupUrl($this->data('Group'), 'members', '/').'/{Page}?filter=members', 'CurrentRecords' => count($this->data('Members'))));
