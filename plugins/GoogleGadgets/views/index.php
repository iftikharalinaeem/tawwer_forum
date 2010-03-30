<?php if (!defined('APPLICATION')) exit();
$DiscussionData = $this->DiscussionData;
$this->DiscussionData = $this->AnnounceData;
$HasAnnouncements = $this->AnnounceData && $this->AnnounceData->NumRows() > 0;
if ($HasAnnouncements) {
?>
<h1 id="AnnouncementsHeading"><?php echo Gdn::Translate('Announcements'); ?></h1>
<div class="DataList Announcements">
   <?php include(PATH_PLUGINS . DS . 'GoogleGadgets' . DS . 'views' . DS . 'discussions.php'); ?>
</div>
<?php
}
$this->DiscussionData = $DiscussionData;
if ($this->DiscussionData->NumRows() > 0) {
?>
<div><?php
if (is_object($this->Category))
   echo sprintf(Gdn::Translate('Discussions <span>&bull;</span> %s'), $this->Category->Name);
else
   echo Gdn::Translate('Discussions');

?></div>
<div class="DataList Discussions">
   <?php include(PATH_PLUGINS . DS . 'GoogleGadgets' . DS . 'views' . DS . 'discussions.php'); ?>
</div>
<?php echo $this->Pager->ToString('more');
} else if (!$HasAnnouncements) {
   ?>
   <h1><?php echo Gdn::Translate('Discussions'); ?></h1>
   <div class="Info EmptyInfo"><?php echo Gdn::Translate('No discussions up in here...'); ?></div>
<?php
}
