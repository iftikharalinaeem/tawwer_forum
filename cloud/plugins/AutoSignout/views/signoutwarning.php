<?php if (!defined('APPLICATION')) exit(); ?>
<div id="SignoutWarning" class="alert alert-warning" style="display: none;">
   <b>Hey!</b> You will be signed out in <span id="CountDown">60</span> seconds due to inactivity.
   <?php echo anchor(t('Click here to continue using the site.'), '#', ['id' => 'CancelSignout']); ?>
</div>