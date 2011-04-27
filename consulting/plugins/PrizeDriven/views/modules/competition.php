<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box PrizeDriven-BoxCompetition">
   <h4>Competition Information</h4>
   <dl>
      <dt>Starts</dt>
      <dd><?php echo PrizeDrivenPlugin::FormatDate(GetValueR('Discussion.DateCompetitionStarts', $this), 'display'); ?></dd>
      <dt>Finishes</dt>
      <dd><?php echo PrizeDrivenPlugin::FormatDate(GetValueR('Discussion.DateCompetitionFinishes', $this), 'display'); ?></dd>
      <dt>Downloads</dt>
      <dd>
         <?php
         echo GetValueR('Discussion.CanDownloadFiles', $this) ? 'Allowed' : 'Designers Only';
         ?>
      </dd>
   </dl>
</div>