<?php if (!defined('APPLICATION')) exit(); ?>

<div class="Group-Leaders">
   <h2><?php echo T('GroupLeaders', 'Leaders'); ?></h2>
   <?php WriteMemberCards($this->Data('Leaders')); ?>
</div>

<h2><?php echo T('GroupMembers', 'Members'); ?></h2>
<?php WriteMemberCards($this->Data('Members')); ?>
