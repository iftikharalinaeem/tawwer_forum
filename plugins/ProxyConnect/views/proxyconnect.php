<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T('Proxy Connect'); ?></h1>
<div class="Info">
   <?php
      echo T('Using Proxy Connect, you can allow users from your external application or website to be automatically registered and signed into Vanilla. For instructions on how to enable Proxy Connect, <a href="http://vanillaforums.com/info/proxyconnect">read our documentation</a>.');
      $ToggleName = C('Plugins.ProxyConnect.Enabled') ? T('Disable Proxy Connect') : T('Enable Proxy Connect');
      echo Wrap(Anchor($ToggleName, 'settings/proxyconnect/toggle/'.Gdn::Session()->TransientKey(), 'SmallButton'));
   ?>
</div>
<?php if (C('Plugins.ProxyConnect.Enabled')) { ?>
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
         echo Wrap(T('The URL of your website where you will use ProxyConnect'));
      ?></li>
      <li><?php
         echo $this->Form->Label(T('Authenticate URL'), 'AuthenticateURL');
         echo $this->Form->TextBox('AuthenticateURL');
         echo Wrap(T('The behind-the-scenes URL that shares identity information with Vanilla'));
      ?></li>
      <li><?php
         echo $this->Form->Label(T('Registration URL'), 'RegisterUrl');
         echo $this->Form->TextBox('RegisterUrl');
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
   <div class="Info"><?php echo T("These are the settings you might need when you configure VanillaConnect on your remote website."); ?></div>
   <div>
      You will probably need to configure Vanilla and your remote application to use a shared Cookie Domain that they can both access. We've
      tried to guess what that might be, based on your hostname, but you'll need to check this and make sure that it works. This can 
      be achieved on Vanilla by adding/modifying the <code>$Configuration['Garden']['Cookie']['Domain']</code> setting in your <code>conf/config.php</code> file.
   </div>
   <table class="Label AltColumns">
      <thead>
         <tr>
            <th><?php echo T('Setting'); ?></th>
            <th class="Alt"><?php echo T('Value'); ?></th>
         </tr>
      </thead>
      <tbody>
         <tr class="Alt">
            <?php
               $ExplodedDomain = explode('.',Gdn::Request()->RequestHost());
               $CookieDomain = '.'.implode('.',array_slice($ExplodedDomain,-2,2));
            ?>
            <td><?php echo T('Vanilla Cookie Domain'); ?></td>
            <td class="Alt"><?php echo $CookieDomain; ?></td>
         </tr>      
      </tbody>
   </table>
</div>
<?php }