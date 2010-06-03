<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T('Vanilla Connect'); ?></h1>
<div class="Info">
   <?php
      echo T('Using Vanilla Connect, you can allow users from your external application or website to be automatically registered and signed into Vanilla. For instructions on how to enable Vanilla Connect, <a href="http://vanillaforums.com/info/vanillaconnect">read our documentation</a>.');
      $ToggleName = C('Plugins.VanillaConnect.Enabled') ? T('Disable Vanilla Connect') : T('Enable Vanilla Connect');
      echo Wrap(Anchor($ToggleName, 'plugin/VanillaConnect/toggle/'.Gdn::Session()->TransientKey(), 'SmallButton'));
   ?>
</div>
<?php if (C('Plugins.VanillaConnect.Enabled')) { ?>
<div class="VanillaConfig">
   <?php
      echo $this->Form->Open();
      echo $this->Form->Errors();
      echo $this->Form->Hidden('AuthenticationKey', $this->ConsumerKey);
   ?>

   <strong><?php echo T('Vanilla Configuration'); ?></strong>
   <div class="Info"><?php echo T("Define the following values so Vanilla knows where to send your users to register, sign in, sign out, etc."); ?></div>
   <ul>
      <li><?php
         echo $this->Form->Label(T('Main Site URL'), 'Url');
         echo $this->Form->TextBox('URL');
         echo Wrap(T('The URL of your website where you will use VanillaConnect'));
      ?></li>
      <li><?php
         echo $this->Form->Label(T('Registration URL'), 'RegistrationUrl');
         echo $this->Form->TextBox('RegistrationUrl');
         echo Wrap(T('The URL where users can sign up for new accounts on your site'));
      ?></li>
      <li><?php
         echo $this->Form->Label(T('Sign-In URL'), 'SignInUrl');
         echo $this->Form->TextBox('SignInUrl');
         echo Wrap(T('The URL where users sign in on your site'));
      ?></li>
      <li><?php
         echo $this->Form->Label(T('Sign-Out URL'), 'SignOutUrl');
         echo $this->Form->TextBox('SignOutUrl');
         echo Wrap(T('The URL where users sign out of your site'));
      ?></li>
   </ul>
   <?php
      echo $this->Form->Close('Save');
   ?>
</div>
<div class="RemoteConfig">
   <strong><?php echo T('Remote Configuration'); ?></strong>
   <div class="Info"><?php echo T("These are the settings you'll need when you configure VanillaConnect on your remote website."); ?></div>
   <table class="Label AltColumns">
      <thead>
         <tr>
            <th><?php echo T('Setting'); ?></th>
            <th class="Alt"><?php echo T('Value'); ?></th>
         </tr>
      </thead>
      <tbody>
         <tr class="Alt">
            <td><?php echo T('Vanilla Forum URL'); ?></td>
            <td class="Alt"><?php echo Gdn::Request()->Url('',TRUE); ?></td>
         </tr>
         <tr>
            <td><?php echo T('VanillaConnect Key'); ?></td>
            <td class="Alt"><?php echo $this->ConsumerKey; ?></td>
         </tr>
         <tr class="Alt">
            <td><?php echo T('VanillaConnect Secret Code'); ?></td>
            <td class="Alt"><?php echo $this->ConsumerSecret; ?></td>
         </tr>
      </tbody>
   </table>
</div>
<?php }