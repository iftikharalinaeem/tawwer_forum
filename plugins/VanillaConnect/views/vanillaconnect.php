<?php if (!defined('APPLICATION')) exit();

?>
<ul>
   <li>
      <div class="VanillaConnectBlock">
         <h1><?php echo T('VanillaConnect'); ?></h1>
         <div class="Detail">
            <?php echo T('This plugin enables SingleSignOn (SSO) between your forum and other authorized consumers.'); ?>
         </div>
      </div>
      <div class="VanillaConnectBlock">
         <strong><?php echo T('Remote Configuration'); ?></strong>
         <div class="Detail">
            <?php echo T("These are the settings you'll need when you configure the VanillaConnect widget/plugin on your existing website or blog."); ?>
         </div>
         <table class="VanillaConnectTable AltColumns">
            <tr>
               <th><?php echo T('Setting'); ?></th>
               <th><?php echo T('Value'); ?></th>
            </tr>
            <tr>
               <td><?php echo T('Vanilla Forum URL'); ?></td>
               <td><?php echo Gdn::Request()->Url(); ?></td>
            </tr>
            <tr>
               <td><?php echo T('VanillaConnect Key'); ?></td>
               <td><?php echo $this->ConsumerKey; ?></td>
            </tr>
            <tr>
               <td><?php echo T('VanillaConnect Secret Code'); ?></td>
               <td><?php echo $this->ConsumerSecret; ?></td>
            </tr>
         </table>
         
         <?php
            echo $this->Form->Open();
            echo $this->Form->Errors();
            echo $this->Form->Hidden('AuthenticationKey', $this->ConsumerKey);
         ?>
         <strong><?php echo T('Vanilla Configuration'); ?></strong>
         <div class="Detail">
            <?php echo T("You'll need to set these values before SSO will function correctly."); ?>
         </div>
         <table class="VanillaConnectTable AltColumns">
            <tr>
               <th><?php echo T('Setting'); ?></th>
               <th><?php echo T('Value'); ?></th>
            </tr>
            <tr>
               <td><?php echo T('Main Site URL'); ?><br/><em><?php echo T('The URL of the website or blog where you will be using VanillaConnect'); ?></em></td>
               <td><?php echo $this->Form->TextBox('URL'); ?></td>
            </tr>
            <tr>
               <td><?php echo T('Registration URL'); ?><br/><em><?php echo T('The URL that users can go to sign up for new accounts on your site'); ?></em></td>
               <td><?php echo $this->Form->TextBox('RegistrationUrl'); ?></td>
            </tr>
            <tr>
               <td><?php echo T('Sign-In URL'); ?><br/><em><?php echo T('The URL that allows users to log in on your site'); ?></em></td>
               <td><?php echo $this->Form->TextBox('SignInUrl'); ?></td>
            </tr>
            <tr>
               <td><?php echo T('Sign-Out URL'); ?><br/><em><?php echo T('The URL that logs users out of your site'); ?></em></td>
               <td><?php echo $this->Form->TextBox('SignOutUrl'); ?></td>
            </tr>
         </table>
         <?php
            echo $this->Form->Close('Save');
         ?>
      </div>
   </li>
</ul>
