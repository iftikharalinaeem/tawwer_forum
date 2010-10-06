<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T($this->Data['Title']); ?></h1>
<div class="Info">
   <?php echo T('View and manage the statistics for your forum.'); ?>
</div>
<div class="FilterMenu">
   <?php
      $ToggleName = C('Plugins.Statistics.Enabled') ? T('Disable Forum Statistics') : T('Enable Forum Statistics');
      echo Anchor($ToggleName, 'plugin/statistics/toggle/'.Gdn::Session()->TransientKey(), 'SmallButton');
   ?>
</div>
<?php 
   if ($this->Plugin->IsEnabled()) {
      echo $this->Plugin->Slice('catchup');
   }
?>