<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title')?></h1>

<?php
if (CheckPermission('Groups.Group.Add')) {
   echo Anchor(T('New Group'), '/group/add', 'Button Primary');
}
?>

<?php WriteGroupCards($this->Data('Groups'), 
   T('GroupsEmpty', "No groups were found. What a lonesome world you must live in.")); ?>

<!-- All of the panel stuff goes here. -->

<?php Gdn_Theme::AssetBegin('Panel'); ?>

<?php if ($this->Data('MyGroups')): ?>
<div class="Box">
   <h4><?php echo T('My Groups'); ?></h4>
   <?php WriteGroupList($this->Data('MyGroups'), 
      T('MyGroupsEmpty', "You haven&rsquo;t joined any groups yet!")); ?>
</div>
<?php endif; ?>

<?php if ($this->Data('NewGroups')) : ?>
<div class="Box">
   <h4><?php echo T('New Groups'); ?></h4>
   <?php
   WriteGroupList($this->Data('NewGroups'));
   ?>
</div>
<?php endif; ?>

<?php Gdn_Theme::AssetEnd(); ?>
