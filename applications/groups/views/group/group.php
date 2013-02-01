<?php WriteGroupBanner(); ?>


<?php WriteGroupIcon(); ?>
<h1><?php echo htmlspecialchars($this->Data('Group.Name')); ?></h1>
<div class="Group-Description">
   <?php echo Gdn_Format::To($this->Data('Group.Description'), $this->Data('Group.Format')); ?>
</div>

<!-- Join/Apply Button area. -->
<?php WriteGroupButtons(); ?>

<div class="Group-Content">
   <div class="Group-Box">
   <h2><?php echo T('Upcoming Events'); ?></h2>
   <?php WriteEventList($this->Data('Events')); ?>
   </div>
   
   <div class="Group-Block">
   <h2><?php echo T('Announcements'); ?></h2>
   <?php WriteDiscussionBlog($this->Data('Announcements')); ?>
   </div>
   
   <div class="Group-Block">
   <h2><?php echo T('Discussions'); ?></h2>
   <?php WriteDiscussionList($this->Data('Discussions')); ?>
   </div>
</div>

<div class="Box">
<h2><?php echo sprintf(T('More About %s'), htmlspecialchars($this->Data('Group.Name'))); ?></h2>

<!-- Leaders -->

<!-- Members -->

<!-- Info -->

</div>