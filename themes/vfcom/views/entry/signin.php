<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Info"><h2>Existing Customer Sign In</h2></div>
<div class="SignInForm Center">
   <div class="EntryErrors"><?php
         echo $this->Form->Errors();
   ?></div>
   <div class="Section FinePrint">
      <strong>Who Signs In Here?</strong>
      <p>This sign in form is for customers who have created a forum at
      VanillaForums.com and want to manage their account.</p>
      <p>If you want to create your own forum, check out <?php echo Anchor('Plans &amp; Pricing', 'plans'); ?>.</p>
   </div>
   <div class="Section DashboardSignIn">
      <?php
      // Make sure to force this form to post to the correct place in case the view is
      // rendered within another view (ie. /dashboard/entry/index/):
      echo $this->Form->Open(array('Action' => Url('/entry/signin'), 'id' => 'Form_User_SignIn'));
      ?>
      <ul>
         <li>
            <?php
               echo $this->Form->Label('Email', 'Email');
               echo $this->Form->TextBox('Email');
            ?>
         </li>
         <li>
            <?php
               echo $this->Form->Label('Password', 'Password');
               echo $this->Form->Input('Password', 'password');
            ?>
         </li>
         <li>
            <?php
               echo $this->Form->CheckBox('RememberMe', T('Remember me on this computer'), array('value' => '1', 'id' => 'SignInRememberMe'));
            ?>
         </li>
         <li class="Buttons">
            <?php
               echo $this->Form->Button('Sign In Now', array('class' => 'GreenButton'));
            ?>
         </li>
         <li class="ForgetPassword">
            <?php
               echo Anchor(T('Forget your password?'), '/entry/passwordrequest', 'ForgotPassword');
            ?>
         </li>
      </ul>
      <?php
      echo $this->Form->Close();
      echo $this->Form->Open(array('Action' => Url('/entry/passwordrequest'), 'id' => 'Form_User_Password', 'style' => 'display: none;'));
      ?>
      <ul>
         <li>
            <?php
               echo $this->Form->Label('Enter your Email address', 'Email');
               echo $this->Form->TextBox('Email');
            ?>
         </li>
         <li>
            <?php
               echo $this->Form->Button('Request a new password', array('class' => 'BlueButton'));
            ?>
         </li>
      </ul>
      <?php echo $this->Form->Close(); ?>
   </div>
</div>