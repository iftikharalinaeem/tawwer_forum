<?php if (!defined('APPLICATION')) exit();

if ($this->Data('ErrorMessage')) {
  echo '<h2 class="Groups H">'.T($this->Data('Title')).'</h2>';
  echo $this->Data('ErrorMessage');
}

else {
  $Layout = 'modern';

  // if we don't have a layout variable sent by the module, we default to the categories layout.
  if ($this->Data('Layout')) {
    $Layout = $this->Data('Layout');
  }
  else if (C('Vanilla.Categories.Layout')) {
    $Layout = C('Vanilla.Categories.Layout');
  }

  if ($Layout === 'table') {
    ?>

    <div class="GroupList">
      <h2 class="Groups H"><?php echo T($this->Data('Title')); ?></h2>
      <?php
      WriteGroupTable($this->Data('Groups'));
      ?>
      <div class="MoreWrap">
        <?php echo Anchor(sprintf(T('All %s...'), T($this->Data('Title'))), $this->Data('Url')); ?>
      </div>
    </div>

  <?php } else { ?>

    <div class="GroupList">
      <h2 class="Groups H"><?php echo T($this->Data('Title')); ?></h2>
      <?php
      WriteGroupItems($this->Data('Groups'));
      ?>
      <div class="MoreWrap">
        <?php echo Anchor(sprintf(T('All %s...'), T($this->Data('Title'))), $this->Data('Url')); ?>
      </div>
    </div>

  <?php } } ?>