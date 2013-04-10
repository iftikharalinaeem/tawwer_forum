<?php if (!defined('APPLICATION')) exit(); ?>

<div class="Box-Events">
   <h2><?php echo T($this->Data('Title')); ?></h2>
   <?php $EmptyMessage = T('GroupEmptyEvents', "Aw snap, no events are coming up."); ?>
   <?php WriteEventList($this->Data('Events'), $this->Data('Group'), $EmptyMessage, $this->Data('Button', TRUE)); ?>
</div>