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

<div class="PageControls Top">
<?php
    echo PagerModule::write();
?>
</div>

<?php
// Let plugins and themes set the layout of a group list.
$layout = c('Vanilla.Discussions.Layout', 'modern');
$showMore = false;
$cssClass = '';
$this->EventArguments['layout'] = &$layout;
$this->EventArguments['showMore'] = &$showMore;
$this->EventArguments['cssClass'] = &$cssClass;
$this->fireEvent('beforeBrowseGroupList');

$groups = $this->data('Groups');
$groupModel = new GroupModel();
$groupModel->joinRecentPosts($groups);

$groupSearch = htmlspecialchars($this->data('GroupSearch', false));
if ($groupSearch && count($groups) === 0) {
    echo '<p class="NoResults">', sprintf(t('No results for %s.', 'No results for <b>%s</b>.'), $groupSearch), '</p>';
} else {
    $list = new GroupListModule($groups, 'groups', $this->title(), t("No groups to display."), $cssClass, $showMore, $layout);
    echo $list;
}
?>

<div class="PageControls Bottom">
<?php
    echo PagerModule::write();
?>
</div>
