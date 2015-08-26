<div class="PageControls Top">
  <?php
  echo PagerModule::write();
  ?>
</div>

<?php
$groups = $this->data('Groups');
$groupModel = new GroupModel();
$groupModel->JoinRecentPosts($groups);
$list = new GroupListModule($this, $groups, 'groups', $this->Title(), t("No groups to display yet."), '', false);
echo $list;
?>

<div class="PageControls Bottom">
  <?php
  echo PagerModule::write();
  ?>
</div>
