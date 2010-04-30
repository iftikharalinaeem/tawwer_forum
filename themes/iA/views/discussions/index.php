<?php if (!defined('APPLICATION')) exit();
include($this->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));
include($this->FetchViewLocation('discussion', 'post', 'vanilla'));
	
if ($this->DiscussionData->NumRows() > 0) {
?>
<ul class="DataList Condensed HasPhoto Discussions">
   <?php
   include($this->FetchViewLocation('discussions'));
   ?>
</ul>
<?php
   echo $this->Pager->ToString('more');
} else {
   ?>
   <div class="Empty"><?php echo T('No discussions were found.'); ?></div>
   <?php
}
