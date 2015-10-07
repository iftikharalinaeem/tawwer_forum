<?php if (!defined('APPLICATION')) exit();
$header = new GroupHeaderModule($this->data('Group'), true, true, true, true);
echo $header;
echo Gdn_Theme::module('GroupUserHeaderModule');
if (GroupPermission('View')) {
    echo '<div class="Group-Content">';
    if ($this->data('Applicants')) {
        $applicantList = new ApplicantListModule($this->data('Applicants'), $this->data('Group'), t('Applicants & Invitations'));
        echo $applicantList;
    }
    writeAnnouncementList($this, t('GroupEmptyAnnouncements', "Important stuff will go here one day."));
    writeDiscussionList($this, t('GroupEmptyDiscussions', "Awfully quiet in here, isn&rsquo;t it?"), t('Discussions'));
    $eventList = new EventListModule($this->data('Events'), t('Upcoming Events'), t('GroupEmptyEvents', "Aw snap, no events are coming up."));
    if (GroupPermission('Member', $this->data('Group'))) {
        $eventList->addNewEventButton(val('GroupID', $this->data('Group')));
        $eventList->showMore(url(combinePaths(array("/events/group/", GroupSlug($this->data('Group'))))));
    }
    echo $eventList;
    echo '<div class="Group-Info ClearFix clearfix">';
    echo Gdn_Theme::module('GroupMembersModule');
    echo '</div>';
    echo '</div>';
} else {
    echo '<div class="Hero">'.GroupPermission('View.Reason').'</div>';
}
