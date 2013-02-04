<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title')?></h1>

<?php
if (CheckPermission('Groups.Group.Add')) {
   echo Anchor(T('New Group'), '/group/add', 'Button Primary');
}
?>

<?php if ($this->Data('MyGroups')): ?>
<div class="Box-Cards">
<h2><?php echo T('My Groups'); ?></h2>
<?php WriteGroupCards($this->Data('MyGroups'), 
   T("You haven't joined any groups yet.")); ?>
</div>      
<?php endif; ?>

<?php if ($this->Data('NewGroups')) : ?>
<div class="Box-Cards">
<h2><?php echo T('New Groups'); ?></h2>
<?php
WriteGroupCards($this->Data('NewGroups'));
?>
</div>
<?php endif; ?>

<div class="Box-Cards">
   <h2><?php echo T('Popular Groups'); ?></h2>
<?php
WriteGroupCards($this->Data('Groups'), 
   T("There aren't any groups yet."));
?>
</div>