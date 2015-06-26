<?php if (!defined('APPLICATION')) exit(); ?>

  <div class="Group-Header">
    <?php
WriteGroupBanner();
WriteGroupIcon(false, 'Group-Icon Group-Icon-Big');
// Join/Apply Buttons
$buttons = getGroupButtons($this->Data('Group'));
if ($options = getGroupOptions($this->Data('Group'))) {
  echo $options->toString();
}
if ($buttons) { ?>
  <div class="Buttons Button-Controls Group-Buttons">
    <?php foreach ($buttons as $button) { ?>
      <a class="Button <?php echo val('cssClass', $button); ?>" href="<?php echo val('url', $button) ?>" role="button"><?php echo val('text', $button); ?></a>
    <?php } ?>
  </div>
<?php } ?>
    <div class="Group-Header-Info">
      <h1 class="Group-Title"><?php echo htmlspecialchars($this->Data('Group.Name')); ?></h1>
      <div class="Group-Description">
        <?php echo Gdn_Format::To($this->Data('Group.Description'), $this->Data('Group.Format')); ?>
      </div>
      <?php echo Gdn_Theme::Module('GroupMetaModule'); ?>
    </div>
  </div>

<?php echo Gdn_Theme::Module('GroupUserHeaderModule');
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
  echo Gdn_Theme::Module('GroupMembersModule');
  echo '</div>';
  echo '</div>';
} else {
  echo '<div class="Hero">'.GroupPermission('View.Reason').'</div>';
}
