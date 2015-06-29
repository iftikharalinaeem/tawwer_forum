<?php if (!defined('APPLICATION')) exit();

if (in_array($this->Data('Filter'), array('', 'leaders'))) {
  $eventList = new MemberListModule($this->Data('Leaders'), $this->Data('Group'), t('Leaders'), t('GroupEmptyLeaders', "There are no group leaders."));
  echo $eventList;
}

if (in_array($this->Data('Filter'), array('', 'members'))) {
  $eventList = new MemberListModule($this->Data('Members'), $this->Data('Group'), t('Members'), t('GroupEmptyMembers', "There are no group members yet."));
  echo $eventList;
}

PagerModule::Write(array('Url' => GroupUrl($this->Data('Group'), 'members', '/').'/{Page}?filter=members', 'CurrentRecords' => count($this->Data('Members'))));
