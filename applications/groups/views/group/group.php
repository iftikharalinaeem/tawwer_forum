<?php if (!defined('APPLICATION')) exit(); ?>

<div class="Group-Header">
   <?php WriteGroupBanner(); ?>
   <!-- Join/Apply Buttons -->
   <?php WriteGroupButtons(); ?>
   <?php WriteGroupIcon(); ?>
   <h1 class="Group-Title"><?php echo htmlspecialchars($this->Data('Group.Name')); ?></h1>
   <div class="Group-Description">
      <?php echo Gdn_Format::To($this->Data('Group.Description'), $this->Data('Group.Format')); ?>
   </div>
</div>

<?php if (GroupPermission('View')): ?>

<div class="Group-Content">
   <?php
   WriteGroupApplicants($this->Data('Applicants'));
   ?>
   
   <div class="Group-Box Box-Events Group-Events">
      <h2><?php echo T('Upcoming Events'); ?></h2>
      <?php $EmptyMessage = T('GroupEmptyEvents', "Aw snap, no events are coming up."); ?>
      <?php WriteEventList($this->Data('Events'), $this->Data('Group'), $EmptyMessage); ?>
   </div>
   
   <div class="Group-Box Group-Announcements">
      <h2><?php echo T('Announcements'); ?></h2>
      <?php $EmptyMessage = T('GroupEmptyAnnouncements', "Important stuff will go here one day."); ?>
      
      <?php
      if (GroupPermission('Moderate')) {
         echo '<div class="Button-Controls">';
         echo Anchor(T('New Announcement'), GroupUrl($this->Data('Group'), 'announcement'), 'Button');
         echo '</div>';
      }
      ?>
      
      <?php WriteDiscussionBlogList($this->Data('Announcements'), $EmptyMessage); ?>
   </div>
   
   <div class="Group-Box Group-Discussions Section-DiscussionList">
      <h2><?php echo T('Discussions'); ?></h2>
      
      <?php
      if (GroupPermission('Member')) {
         echo '<div class="Button-Controls">';
         echo Gdn_Theme::Module('NewDiscussionModule', array('CssClass' => 'Button Action Primary', 'QueryString' => 'groupid='.$this->Data('Group.GroupID')));
         echo '</div>';
      }
      ?>
      
      <?php $EmptyMessage = T('GroupEmptyDiscussions', "Awfully quiet in here, isn&rsquo;t it?"); ?>
      <?php WriteDiscussionList($this->Data('Discussions'), $EmptyMessage); ?>
      <?php
      if ($this->Data('Discussions')) {
         echo '<div class="MoreWrap">'.
            Anchor(T('All Discussions'), GroupUrl($this->Data('Group'), 'discussions')).
            '</div>';
      }
      ?>
   </div>
</div>

<div class="Group-Footer Box">
   <h2><?php echo sprintf(T('More About %s'), htmlspecialchars($this->Data('Group.Name'))); ?></h2>
   
   <!-- Leaders -->
   <div class="Group-Box Group-Leaders">
      <h3><?php echo Anchor(T('Group Leaders', 'Leaders'), GroupUrl($this->Data('Group'), 'members')); ?></h3>
      <?php WriteMemberSimpleList($this->Data('Leaders')); ?>
   </div>
   
   <!-- Info -->
   <div class="Group-Box Group-Info">
      <h3><?php echo T('Group Info'); ?></h3>
      <?php
      WriteGroupInfo();
      ?>
   </div>
    
   <!-- Members -->
   <div class="Group-Box Group-MembersPreview">
      <h3><?php echo Anchor(T('Group Members', 'Members'), GroupUrl($this->Data('Group'), 'members'));?></h3>
      <?php WriteMemberGrid($this->Data('Members'), Anchor(T('All Members'), GroupUrl($this->Data('Group'), 'members'), 'MoreWrap')); ?>
   </div>
</div>

<?php else: ?>
<div class="Hero">
   <?php
   echo GroupPermission('View.Reason');
   ?>
</div>
<?php endif; ?>