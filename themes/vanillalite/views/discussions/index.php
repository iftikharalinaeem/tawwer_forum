<?php if (!defined('APPLICATION')) exit();
include($this->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));
echo '<h1 class="Redundant">'.$this->Data('Title').'</h1>';

WriteFilterTabs($this);
if ($this->DiscussionData->NumRows() > 0 || (is_object($this->AnnounceData) && $this->AnnounceData->NumRows() > 0)) {
?>
<ul class="DataList Discussions">
   <?php include($this->FetchViewLocation('discussions')); ?>
</ul>
<?php
   //$Pager = $this->Pager->ToString('more');
   PagerModule::Write(array('RecordCount' => $this->Data('CountDiscussions')));
} else {
   ?>
   <div class="Empty"><?php echo T('No discussions were found.'); ?></div>
   <?php
}
