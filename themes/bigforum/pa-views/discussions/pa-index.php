<?php if (!defined('APPLICATION')) exit();
$this->Title(T('All Discussions'));
include($this->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));
$Unempty = $this->DiscussionData->NumRows() > 0 || (is_object($this->AnnounceData) && $this->AnnounceData->NumRows() > 0);
$NewThreadButton = Gdn_Theme::Module('NewDiscussionModule');

echo '<table class="PageNavigation Top"><tr><td>';
echo $NewThreadButton;   
echo '</td><td>';
if ($Unempty)
   echo $this->Pager->ToString('less');
echo '</td></tr></table>';
WriteFilterTabs($this);
if ($Unempty) {
?>
<ul class="DataList Discussions">
   <?php include($this->FetchViewLocation('discussions')); ?>
</ul>
<?php
   echo '<table class="PageNavigation Bottom"><tr><td>';
   echo $NewThreadButton;   
   echo '</td><td>';
   echo $this->Pager->ToString('more');
   echo '</td></tr></table>';
} else {
   ?>
   <div class="Empty"><?php echo T('No discussions were found.'); ?></div>
   <?php
}
