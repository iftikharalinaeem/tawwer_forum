<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title')?></h1>

<div class="UpcomingEvents">
   <h2><?php echo T('Upcoming Events'); ?></h2>
   <?php WriteEventCards($this->Data('UpcomingEvents')); ?>
</div>

<div class="RecentEvents">
   <h2><?php echo T('Recent Events'); ?></h2>
   <?php WriteEventCards($this->Data('RecentEvents')); ?>
</div>