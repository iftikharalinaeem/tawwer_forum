<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box GuestBox">
   <h4><?php echo 'Greetings, lanista!'; //T('Welcome, Stranger!'); ?></h4>
   <p><?php echo 'You are unknown to this ludus. Please introduce yourself.'; //T($this->MessageCode, $this->MessageDefault); ?></p>
   <?php //$this->FireEvent('BeforeSignInButton'); ?>
   <p class="Center">
      <?php
      echo SpartacusThemeHooks::FacebookButton();
      
//      echo Anchor(T('Sign In'), Gdn::Authenticator()->SignInUrl($this->_Sender->SelfUrl), 'Button'.(SignInPopup() ? ' SignInPopup' : ''));
//      $Url = Gdn::Authenticator()->RegisterUrl($this->_Sender->SelfUrl);
//      if(!empty($Url))
//         echo ' '.Anchor(T('Apply for Membership'), $Url, 'Button ApplyButton');
      ?>
   </p>
   <?php //$this->FireEvent('AfterSignInButton'); ?>
</div>