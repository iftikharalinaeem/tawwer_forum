<?php if (!defined('APPLICATION')) exit();

$header = new GroupHeaderModule($this->data('Group'), true, true, true, true);
echo $header;
echo Gdn_Theme::module('GroupUserHeaderModule');
if (groupPermission('View')) {
    echo '<div class="Group-Content">';
    if ($this->data('Applicants')) {
        $applicantList = new ApplicantListModule($this->data('Applicants'), $this->data('Group'), t('Applicants & Invitations'));
        echo $applicantList;
    }
    writeAnnouncementList($this, t('GroupEmptyAnnouncements', "Important stuff will go here one day."));
    writeDiscussionList($this, 'discussions', t('GroupEmptyDiscussions', "Awfully quiet in here, isn&rsquo;t it?"), t('Discussions'));
    $groupID = val('GroupID', $this->data('Group'));
    $eventList = new EventListModule($this->data('Events'), t('Upcoming Events'), t('GroupEmptyEvents', "Aw snap, no events are coming up."));
    if (groupPermission('Member', $this->data('Group')) || groupPermission('Moderate', $this->data('Group'))) {
        if (c('Groups.Members.CanAddEvents', true) || groupPermission('Leader', $groupID)) {
            $eventList->addNewEventButton($groupID);
            $eventList->showMore(url(combinePaths(["/events/group/", groupSlug($this->data('Group'))])));
        }
    }
    echo $eventList;
    echo '<div class="Group-Info ClearFix clearfix">';
    echo Gdn_Theme::module('GroupMembersModule');
    echo '</div>';
    echo '</div>';
} else {
    echo '<div class="Hero">'.groupPermission('View.Reason').'</div>';
}