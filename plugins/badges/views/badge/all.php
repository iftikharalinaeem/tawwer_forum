<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo T('Manage Badges'); ?></h1>
<div class="Info">
   <?php echo Anchor(T('Add Badge'), '/badge/manage', 'SmallButton'); ?>
</div>
<table id="Badges" class="AltColumns ManageBadges">
   <thead>
      <tr>
         <th class="BadgeNameHead"><?php echo T('Badge Name', 'Name'); ?></th>
         <?php if (CheckPermission('Reputation.Badges.Give')) : ?>
            <th></th>
         <?php endif; ?>
         <th class="Alt"><?php echo T('Description'); ?></th>
         <th><?php echo T('Class'); ?></th>
         <th><?php echo T('Level'); ?></th>
         <th><?php echo T('Given'); ?></th>
         <th class="Alt"><?php echo T('Active'); ?></th>
         <!--<th><?php echo T('Visible'); ?></th>-->
         <th class="Alt Options"><?php echo T('Options'); ?></th>
      </tr>
   </thead>
   <tbody>
      <?php
      if (count($this->Data('Badges'))) :
         include($this->FetchViewLocation('badges'));
      else :
         echo '<tr><td colspan="' . (CheckPermission('Reputation.Badges.Give') ? '7' : '6') . '">' . T('No badges yet.') . '</td></tr>';
      endif;
      ?>
   </tbody>
</table>