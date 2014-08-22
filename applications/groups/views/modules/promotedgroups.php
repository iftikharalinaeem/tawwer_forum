<?php if (!defined('APPLICATION')) exit();

    if ($this->Data('ErrorMessage')) {
        echo $this->Data('ErrorMessage');
    }

    else {
?>

    <div class="Box-Cards">
        <h2><?php echo T($this->Data('Title')); ?></h2>
        <?php
           WriteGroupCards($this->Data('Groups'), T("There aren't any groups yet."));
        ?>
        <div class="MoreWrap">
            <?php echo Anchor(sprintf(T('All %s...'), T($this->Data('Title'))), $this->Data('Url')); ?>
        </div>
    </div>

<?php } ?>