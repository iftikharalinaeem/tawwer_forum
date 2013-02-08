<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title')?></h1>

<div class="UpcomingEvents">
   <?php WriteDetailedEventCards($this->Data('UpcomingEvents')); ?>
</div>