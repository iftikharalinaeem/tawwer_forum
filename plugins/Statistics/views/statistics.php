<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T($this->Data['Title']); ?></h1>
<div class="Info">
   <?php
      echo T('View and manage the statistics for your forum.');
      $ToggleName = C('Plugins.Statistics.Enabled') ? T('Disable Forum Statistics') : T('Enable Forum Statistics');
      echo "<div>".Wrap(Anchor($ToggleName, 'plugin/statistics/toggle/'.Gdn::Session()->TransientKey(), 'SmallButton'))."</div>";
   ?>
</div>
<?php if (C('Plugins.Statistics.Enabled')) { ?>
<div><?php echo Wrap(Anchor(T('Catchup'), 'plugin/statistics/catchup', 'SmallButton')); ?></div>
<?php } ?>