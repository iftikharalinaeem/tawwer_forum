<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title')?></h1>

<?php
if (CheckPermission('Groups.Group.Add')) {
    echo Anchor(T('New Group'), '/group/add', 'Button Primary');
}

$layout = c('Vanilla.Discussions.Layout', 'modern');
$showMore = true;

// Let plugins and themes set the layout of a group list.
$this->EventArguments['layout'] = &$layout;
$this->EventArguments['showMore'] = &$showMore;
$this->fireEvent('beforeGroupLists');

if ($groups = $this->data('MyGroups')) {
    $groupModel = new GroupModel();
    $groupModel->JoinRecentPosts($groups);
    $cssClass = 'my-groups';
    $this->EventArguments['layout'] = &$layout;
    $this->EventArguments['showMore'] = &$showMore;
    $this->EventArguments['cssClass'] = &$cssClass;
    $this->fireEvent('beforeMyGroups');
    $list = new GroupListModule($groups, 'mine', t('My Groups'), t("You haven't joined any groups yet."), $cssClass, $showMore, $layout);
    echo $list;
}


if ($groups = $this->data('NewGroups')) {
    $groupModel = new GroupModel();
    $groupModel->JoinRecentPosts($groups);
    $cssClass = 'new-groups';
    $this->EventArguments['layout'] = &$layout;
    $this->EventArguments['showMore'] = &$showMore;
    $this->EventArguments['cssClass'] = &$cssClass;
    $this->fireEvent('beforeNewGroups');
    $list = new GroupListModule($groups, 'new', t('New Groups'), t("There aren't any groups yet."), $cssClass, $showMore, $layout);
    echo $list;
}

if ($groups = $this->data('Groups')) {
    $groupModel = new GroupModel();
    $groupModel->JoinRecentPosts($groups);
    $cssClass = 'popular-groups';
    $this->EventArguments['layout'] = &$layout;
    $this->EventArguments['showMore'] = &$showMore;
    $this->EventArguments['cssClass'] = &$cssClass;
    $this->fireEvent('beforePopularGroups');
    $list = new GroupListModule($groups, 'popular', t('Popular Groups'), t("There aren't any groups yet."), $cssClass, $showMore, $layout);
    echo $list;
}
?>
