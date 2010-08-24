<?php if (!defined('APPLICATION')) exit(); ?>
<div class="AuthenticationConfigure Slice" rel="dashboard/settings/vanillaconnect">
   <div class="SliceConfig"><?php echo $this->SliceConfig; ?></div>
   
   <h3><?php echo T('Vanilla Connect'); ?></h3>
   <div class="Info">
      <?php
         echo T('Using Vanilla Connect, you can allow users from your external application or website to be automatically registered and signed into Vanilla. For instructions on how to enable Vanilla Connect, <a href="http://vanillaforums.com/info/vanillaconnect">read our documentation</a>.');
      ?>
   </div>
   
   <table class="SplitConfiguration">
      <thead>
         <th><?php echo T('Vanilla Configuration'); ?></th>
         <th><?php echo T('Remote Configuration'); ?></th>
      </thead>
      <tbody>
         <td class="VanillaConfig">
            <?php
               echo $this->Form->Open();
               echo $this->Form->Errors();
               echo $this->Form->Hidden('AuthenticationKey', $this->ConsumerKey);
            ?>
            <div><?php echo T("Define the following values so Vanilla knows where to send your users to register, sign in, sign out, etc."); ?><br/><br/></div>
            <ul>
               <li><?php
                  echo $this->Form->Label(T('Main Site URL'), 'Url');
                  echo $this->Form->TextBox('URL');
                  echo Wrap(T('The URL of your website where you will use VanillaConnect'));
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
               echo $this->Form->Close('Save', '', array(
                                 'class' => 'SliceSubmit Button'
                              ));
            ?>
         </td>

         <td class="RemoteConfig">
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
                     <td class="Alt"><?php echo Gdn::Request()->Url('/',TRUE); ?></td>
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
         </td>
      </tbody>
   </table>
</div>
