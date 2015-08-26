<?php if (!defined('APPLICATION')) exit();

echo Gdn_Theme::module('GroupUserHeaderModule');
if (GroupPermission('View')) {
    echo '<div class="Group-Content">';
    if ($this->data('Applicants')) {
        $applicantList = new ApplicantListModule($this->data('Applicants'), $this->data('Group'), t('Applicants & Invitations'));
        echo $applicantList;
    }
    writeFullAnnouncementList($this, t('GroupEmptyAnnouncements', "Important stuff will go here one day."));
    writeFullDiscussionList($this, t('GroupEmptyDiscussions', "Awfully quiet in here, isn&rsquo;t it?"), t('Discussions'));
    $eventList = new EventListModule($this->data('Events'), $this->data('Group'), t('Upcoming Events'), t('GroupEmptyEvents', "Aw snap, no events are coming up."));
    echo $eventList;
    echo '<div class="Group-Info ClearFix clearfix">';
    echo Gdn_Theme::module('GroupMembersModule');
    echo '</div>';
    echo '</div>';
} else {
    echo '<div class="Hero">'.GroupPermission('View.Reason').'</div>';
}
