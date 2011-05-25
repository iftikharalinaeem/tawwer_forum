<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box GuestBox">
   <h4><?php echo 'Greetings, lanista!'; //T('Welcome, Stranger!'); ?></h4>
   <p><?php echo 'You are unknown to this ludus. Please introduce yourself.'; //T($this->MessageCode, $this->MessageDefault); ?></p>
   <?php //$this->FireEvent('BeforeSignInButton'); ?>
   <p class="Center">
      <?php
      echo SpartacusThemeHooks::FacebookButton();
      ?>
   </p>
   <?php //$this->FireEvent('AfterSignInButton'); ?>
</div>