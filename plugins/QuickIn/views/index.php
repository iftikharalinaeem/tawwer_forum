<?php if (!defined('APPLICATION')) exit();
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php echo Gdn::Translate('QuickIn'); ?></h1>
<ul>
   <li>
      <?php
         echo $this->Form->CheckBox('EnableQuickIn', "Enabled QuickIn");
      ?>
   </li>
</ul>
<div class="Info"><?php echo Translate('Tell us where to sign in, sign out, and register in the other application:'); ?></div>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Sign-in Url', 'Garden.Authenticator.SignInUrl');
         echo $this->Form->TextBox('Garden.Authenticator.SignInUrl', array('style' => 'width: 500px;'));
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Sign-out Url', 'Garden.Authenticator.SignOutUrl');
         echo $this->Form->TextBox('Garden.Authenticator.SignOutUrl', array('style' => 'width: 500px;'));
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Registration Url', 'Garden.Authenticator.RegisterUrl');
         echo $this->Form->TextBox('Garden.Authenticator.RegisterUrl', array('style' => 'width: 500px;'));
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');