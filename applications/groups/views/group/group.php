<?php WriteGroupBanner($this->Data('Group')); ?>


<?php WriteGroupIcon($this->Data('Group')); ?>
<h1><?php echo htmlspecialchars($this->Data('Group.Name')); ?></h1>
<div class="Group-Description">
   <?php echo Gdn_Format::To($this->Data('Group.Description'), $this->Data('Group.Format')); ?>
</div>
