<?php if (!defined('APPLICATION')) exit();
echo Gdn_Theme::module('GroupHeaderModule');

if (in_array($this->data('Filter'), array('', 'leaders'))) {
  $memberList = new MemberListModule($this->data('Leaders'), $this->data('Group'), t('Leaders'), t('GroupEmptyLeaders', "There are no group leaders."));
  echo $memberList;
}

if (in_array($this->data('Filter'), array('', 'members'))) {
  $memberList = new MemberListModule($this->data('Members'), $this->data('Group'), t('Members'), t('GroupEmptyMembers', "There are no group members yet."));
  echo $memberList;
}

PagerModule::write(array('Url' => GroupUrl($this->data('Group'), 'members', '/').'/{Page}?filter=members', 'CurrentRecords' => count($this->data('Members'))));
