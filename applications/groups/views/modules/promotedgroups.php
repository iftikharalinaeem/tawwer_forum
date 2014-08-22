<?php if (!defined('APPLICATION')) exit(); ?>

<div class="Box-Cards">
    <h2><?php echo T($this->Data('GroupsType')); ?></h2>
    <?php
       WriteGroupCards($this->Data('Groups'), T("There aren't any groups yet."));
    ?>
    <div class="MoreWrap">
        <?php echo Anchor(sprintf(T('All %s...'), T($this->Data('GroupsType'))), $this->Data('GroupsUrl')); ?>
    </div>
</div>