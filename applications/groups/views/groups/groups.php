<h1><?php echo $this->Data('Title')?></h1>

<?php
if (CheckPermission('Groups.Group.Add')) {
   echo Anchor(T('New Group'), '/group/add', 'Button Primary');
}
?>

<?php
WriteGroupCards($this->Data('Groups'));
?>

<!-- All of the panel stuff goes here. -->

<?php Gdn_Theme::AssetBegin('Panel'); ?>

<?php if ($this->Data('MyGroups')): ?>
<div class="Box">
   <h4><?php echo T('My Groups'); ?></h4>
   <?php
   WriteGroupList($this->Data('MyGroups'));
   ?>
</div>
<?php endif; ?>

<div class="Box">
   <h4><?php echo T('New Groups'); ?></h4>
   <?php
   WriteGroupList($this->Data('NewGroups'));
   ?>
</div>

<?php Gdn_Theme::AssetEnd(); ?>
