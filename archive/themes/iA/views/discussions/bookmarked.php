<?php if (!defined('APPLICATION')) exit();
include($this->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));
$ViewLocation = $this->FetchViewLocation('discussions');
if ($this->DiscussionData->NumRows() > 0) {
?>
<ul class="DataList Discussions Bookmarks">
   <?php include($ViewLocation); ?>
</ul>
<?php
} else {
?>
<div class="Empty"><?php echo T('You do not have any bookmarks.'); ?></div>
<?php
}
echo $this->Pager->ToString('more');