<?php if (!defined('APPLICATION')) exit();

echo Gdn_Theme::module('GroupUserHeaderModule');
if (GroupPermission('View')) {
    echo '<div class="Group-Content">';
    if ($this->Data('Applicants')) {
        $applicantList = new ApplicantListModule($this->Data('Applicants'), $this->Data('Group'), t('Applicants & Invitations'));
        echo $applicantList;
    }
    writeFullAnnouncementList($this, T('GroupEmptyAnnouncements', "Important stuff will go here one day."));
    writeFullDiscussionList($this, T('GroupEmptyDiscussions', "Awfully quiet in here, isn&rsquo;t it?"));
    $eventList = new EventListModule($this->Data('Events'), $this->Data('Group'), t('Upcoming Events'), t('GroupEmptyEvents', "Aw snap, no events are coming up."));
    echo $eventList;
    echo '<div class="Group-Info ClearFix clearfix">';
    echo Gdn_Theme::module('GroupMembersModule');
    echo '</div>';
    echo '</div>';
} else {
    echo '<div class="Hero">'.GroupPermission('View.Reason').'</div>';
}
