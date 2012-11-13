<?php if (!defined('APPLICATION')) exit; ?>
<div id="ModList" class="ModList Box">
   <h4><?php echo T('Moderators'); ?></h4>
   <ul>
      <?php foreach ($this->Data('Moderators') as $Row): ?>
      <li>
         <?php
         echo UserAnchor($Row);
         ?>
      </li>
      <?php endforeach; ?>
   </ul>
</div>