<div class="PageControls Top">
  <?php
  echo PagerModule::Write();
  ?>
</div>

<?php
$groups = $this->Data('Groups');
$groupModel = new GroupModel();
$groupModel->JoinRecentPosts($groups);
$list = new GroupListModule($groups, 'groups', $this->Title(), t("No groups to display yet."), false);
echo $list;
?>

<div class="PageControls Bottom">
  <?php
  echo PagerModule::Write();
  ?>
</div>
