<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->data('Title')?></h1>

<div class="groupsToolbar">
<?php
if (checkPermission('Groups.Group.Add')) {
    echo anchor(t('New Group'), '/group/add', 'Button Primary');
}
echo '    <div class="group-search">';
            echo Gdn_Theme::Module('GroupSearchModule');
echo '    </div>';
echo '</div>';

$layout = c('Vanilla.Discussions.Layout', 'modern');
$showMore = true;

// Let plugins and themes set the layout of a group list.
$this->EventArguments['layout'] = &$layout;
$this->EventArguments['showMore'] = &$showMore;
$this->fireEvent('beforeGroupLists');

if ($groups = $this->data('MyGroups')) {
    $groupModel = new GroupModel();
    $groupModel->joinRecentPosts($groups);
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
    $groupModel->joinRecentPosts($groups);
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
    $groupModel->joinRecentPosts($groups);
    $cssClass = 'popular-groups';
    $this->EventArguments['layout'] = &$layout;
    $this->EventArguments['showMore'] = &$showMore;
    $this->EventArguments['cssClass'] = &$cssClass;
    $this->fireEvent('beforePopularGroups');
    $list = new GroupListModule($groups, 'popular', t('Popular Groups'), t("There aren't any groups yet."), $cssClass, $showMore, $layout);
    echo $list;
}
?>
