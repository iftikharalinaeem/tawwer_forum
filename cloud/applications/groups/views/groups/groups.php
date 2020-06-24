<?php if (!defined('APPLICATION')) exit(); ?>

<div class="pageHeading">
    <div class="pageHeading-main">
        <h1 class="pageTitle pageHeading-title">
            <?php echo $this->data('Title')?>
        </h1>
    </div>
    <div class="pageHeading-actions">
        <?php writeGroupSearch(); ?>
    </div>
</div>


<?php
if (checkPermission('Groups.Group.Add')) {
    echo '<div class="groupToolbar">';
    echo anchor(t('New Group'), '/group/add', 'Button Primary groupToolbar-newGroup');
    echo '</div>';
}

$layout = c('Vanilla.Discussions.Layout', 'modern');
$showMore = true;

// Let plugins and themes set the layout of a group list.
$this->EventArguments['layout'] = &$layout;
$this->EventArguments['showMore'] = &$showMore;
$this->fireEvent('beforeGroupLists');

$groupModel = new GroupModel();

if (!empty($groups = $this->data('Invites'))) {
    $groupModel->joinRecentPosts($groups);
    $cssClass = 'group-invites';
    $list = new GroupListModule($groups, 'invites', t('Group Invites'), '', $cssClass, false, $layout);
    echo $list;
}

if ($groups = $this->data('MyGroups')) {
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
