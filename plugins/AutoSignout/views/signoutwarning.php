<?php if (!defined('APPLICATION')) exit(); ?>
<div id="SignoutWarning" class="Warning Hero" style="display: none;">
   <b>Hey!</b> You will be signed out in <span id="CountDown">60</span> seconds due to inactivity.
   <?php echo Anchor(T('Click here to continue using the site.'), '#', array('id' => 'CancelSignout')); ?>
</div>