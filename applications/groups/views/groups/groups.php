<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title')?></h1>

<?php
if (CheckPermission('Groups.Group.Add')) {
   echo Anchor(T('New Group'), '/group/add', 'Button Primary');
}

if ($groups = $this->Data('MyGroups')) {
  $groupModel = new GroupModel();
  $groupModel->JoinRecentPosts($groups);
  $list = new GroupListModule($this, $groups, 'mine', t('My Groups'), t("You haven't joined any groups yet."), 'my-groups');
  echo $list;
}


if ($groups = $this->Data('NewGroups')) {
  $groupModel = new GroupModel();
  $groupModel->JoinRecentPosts($groups);
  $list = new GroupListModule($this, $groups, 'new', t('New Groups'), t("There aren't any groups yet."), 'new-groups');
  echo $list;
}

if ($groups = $this->Data('Groups')) {
  $groupModel = new GroupModel();
  $groupModel->JoinRecentPosts($groups);
  $list = new GroupListModule($this, $groups, 'popular', t('Popular Groups'), t("There aren't any groups yet."), 'popular-groups');
  echo $list;
}
?>
