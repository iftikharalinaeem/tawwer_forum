<?php if (!defined('APPLICATION')) exit();
if ($this->DiscussionData->NumRows() > 0) {
?>
<h1><?php
if (is_object($this->Category))
   echo sprintf(Gdn::Translate('Discussions <span>&bull;</span> %s'), $this->Category->Name);
else
   echo Gdn::Translate('Discussions');

?></h1>
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
