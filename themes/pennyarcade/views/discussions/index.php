<?php if (!defined('APPLICATION')) exit();
$this->Title(T('All Discussions'));
include($this->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));
$Unempty = $this->DiscussionData->NumRows() > 0 || (is_object($this->AnnounceData) && $this->AnnounceData->NumRows() > 0);
$NewThreadButton = Gdn_Theme::Module('NewDiscussionModule');

if ($Unempty)
   echo $this->Pager->ToString('less');

echo $NewThreadButton;   
WriteFilterTabs($this);
if ($Unempty) {
?>
<ul class="DataList Discussions">
   <?php include($this->FetchViewLocation('discussions')); ?>
</ul>
<?php
   echo $this->Pager->ToString('more');
   echo $NewThreadButton;   
} else {
   ?>
   <div class="Empty"><?php echo T('No discussions were found.'); ?></div>
   <?php
}
