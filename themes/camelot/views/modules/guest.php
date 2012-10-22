<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box GuestBox">
   <h4><?php echo 'How now, ye fans of Camelot!'; //T('Welcome, Stranger!'); ?></h4>
   <p><?php echo "Verily, we're pleased to see you! We bid you to introduce ye self!"; //T($this->MessageCode, $this->MessageDefault); ?></p>
   <?php //$this->FireEvent('BeforeSignInButton'); ?>
   <p class="Center">
      <?php
      echo CamelotThemeHooks::FacebookButton();
      ?>
   </p>
   <?php //$this->FireEvent('AfterSignInButton'); ?>
</div>