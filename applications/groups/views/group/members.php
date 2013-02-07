<?php if (!defined('APPLICATION')) exit(); ?>

<div class="Group-MembersPage">
   <div class="Group-SmallHeader">
      <?php WriteGroupIcon(); ?>
      <h1 class="Group-Title"><?php 
         echo htmlspecialchars($this->Data('Group.Name'));
      ?></h1>
   </div>
   
   <div class="Box-Cards Group-Leaders">
      <h2><?php echo T('GroupLeaders', 'Leaders'); ?></h2>
      <?php WriteMemberCards($this->Data('Leaders')); ?>
   </div>
   
   <div class="Box-Cards Group-Members">
      <h2><?php echo T('GroupMembers', 'Members'); ?></h2>
      <?php WriteMemberCards($this->Data('Members')); ?>
   </div>
</div>