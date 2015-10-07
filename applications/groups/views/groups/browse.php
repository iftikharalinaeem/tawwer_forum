<div class="PageControls Top">
  <?php
  echo PagerModule::write();
  ?>
</div>

<?php
$groups = $this->data('Groups');
$groupModel = new GroupModel();
$groupModel->JoinRecentPosts($groups);
// Let plugins and themes set the layout of a group list.
$layout = c('Vanilla.Discussions.Layout', 'modern');
$showMore = false;
$cssClass = '';
$this->EventArguments['layout'] = &$layout;
$this->EventArguments['showMore'] = &$showMore;
$this->EventArguments['cssClass'] = &$cssClass;
$this->fireEvent('beforeBrowseGroupList');
$list = new GroupListModule($groups, 'groups', $this->Title(), t("No groups to display yet."), $cssClass, $showMore, $layout);
echo $list;
?>

<div class="PageControls Bottom">
  <?php
  echo PagerModule::write();
  ?>
</div>
