<?php if (!defined('APPLICATION')) exit(); ?>

<div class="Group-MembersPage">
   <div class="Group-SmallHeader">
      <?php WriteGroupIcon(); ?>
      <h1 class="Group-Title"><?php 
         echo htmlspecialchars($this->Data('Group.Name'));
      ?></h1>
   </div>

   <?php if (in_array($this->Data('Filter'), array('', 'leaders'))): ?>
   <div class="Box-Cards Group-Leaders">
      <h2><?php echo T('GroupLeaders', 'Leaders'); ?></h2>
      <?php
      WriteMemberCards($this->Data('Leaders'));
//      PagerModule::Write(array('Url' => GroupUrl($this->Data('Group'), 'members', '/').'/{Page}?filter=leaders', 'CurrentRecords' => count($this->Data('Leaders'))));
      ?>
   </div>
   <?php endif ?>

   <?php if (in_array($this->Data('Filter'), array('', 'members'))): ?>
   <div class="Box-Cards Group-Members">
      <h2><?php echo T('GroupMembers', 'Members'); ?></h2>
      <?php
      WriteMemberCards($this->Data('Members'));
      PagerModule::Write(array('Url' => GroupUrl($this->Data('Group'), 'members', '/').'/{Page}?filter=members', 'CurrentRecords' => count($this->Data('Members'))));
      ?>
   </div>
   <?php endif; ?>
</div>