<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box GuestBox">
   <h4><?php echo 'How now, ye fans of Camelot!'; //T('Welcome, Stranger!'); ?></h4>
   <p><?php echo "Verily, we're pleased to see you! We bid you to introduce ye self!"; //T($this->MessageCode, $this->MessageDefault); ?></p>
   <?php //$this->FireEvent('BeforeSignInButton'); ?>
   <p class="Center">
      <?php
      echo CamelotThemeHooks::FacebookButton();
      
//      echo Anchor(T('Sign In'), Gdn::Authenticator()->SignInUrl($this->_Sender->SelfUrl), 'Button'.(SignInPopup() ? ' SignInPopup' : ''));
//      $Url = Gdn::Authenticator()->RegisterUrl($this->_Sender->SelfUrl);
//      if(!empty($Url))
//         echo ' '.Anchor(T('Apply for Membership'), $Url, 'Button ApplyButton');
      ?>
   </p>
   <?php //$this->FireEvent('AfterSignInButton'); ?>
</div>