<style class="text/css">
   /* Settings */
   .MailChimpSettings {
      padding-bottom: 15px;
   }
   .MailChimpSettings form ul {
      padding-bottom: 10px !important;
   }
   
   #Content form ul.MailingList,
   #Content form ul.SyncList {
      padding-top: 0px;
   }
   
   .MailChimpSync .Synchronization {
      padding: 15px;
      margin: 15px 20px;
      border-radius: 3px;
      background: #C0E1FF;
      display: none;
   }
   .MailChimpSync .SyncProgressTitle {
      margin-top: -5px;
      font-size: 12px;
      text-transform: uppercase;
   }
   .MailChimpSync .Synchronization .SyncProgressTitle span {
      color: #1C7EC0;
      text-transform: lowercase;
      font-size: 10px;
   }
   .MailChimpSync .SyncBar {
      height: 15px;
      background: #8BC8FF;
      border-radius: 4px;
      border: 1px solid #489CF7;
      width: 100%;
   }
   .MailChimpSync .SyncBar .SyncProgress {
      height: 15px;
      background: #489CF7;
      border-radius: 3px;
      width: 0%;
      font-size: 11px;
      text-align: right;
      line-height: 15px;
   }
   .MailChimpSync .SyncBar .SyncProgress span {
      color: white;
      margin-right: 5px;
   }
   
   /* Error */
   .MailChimpSync .Synchronization.Error {
      background: #FFC0C0;
   }
   .MailChimpSync .Synchronization.Error .SyncProgressTitle span {
      display: none;
   }
   .MailChimpSync .Synchronization.Error .SyncBar {
      border-color: #FA5A5A;
   }
   .MailChimpSync .Synchronization.Error .SyncBar .SyncProgress {
      width: 100%;
      text-align: center;
      background: #FA5A5A;
      color: white;
   }
   
   /* Finished */
   .MailChimpSync .Synchronization.Finished .SyncBar .SyncProgress {
      width: 100%;
      text-align: center;
      color: white;
   }
   .MailChimpSync .Synchronization.Finished .SyncProgressTitle span {
      display: none;
   }
   
   .MailChimpSync #MailChimp-Synchronize:disabled {
      opacity: 0.5;
   }
</style>
<h1><?php echo $this->data('Title'); ?></h1>
<div class="InfoRow MailChimpSettings">
   
   <?php if (!defined('APPLICATION')) exit();
   echo $this->Form->open();
   echo $this->Form->errors();
   ?>
   
   <div class="Info">
      <?php echo t('About MailChimpPush', "MailChimp Push synchronizes your users'
      email addresses with a MailChimp mailing list of your choice. When a new 
      user signs up, or when an existing user changed their email, Vanilla will 
      send a notification to MailChimp to add or update the user."); ?>
   </div>
   
   <div class="Warning"><?php echo t('API Authentication settings'); ?></div>
   <div class="Info">
      <?php echo Anchor(t('How to find your MailChimp API key'),
         'http://kb.mailchimp.com/article/where-can-i-find-my-api-key'); ?>
   </div>
   <ul>
      <li><?php
         echo $this->Form->label("API Key", "ApiKey");
         echo $this->Form->textBox('ApiKey');
      ?></li>
   </ul>
   
   <?php if ($this->data('Configured')): ?>
   <div class="Warning"><?php echo t('Mailing List settings'); ?></div>
   <div class="Info">
      <?php echo t('MailChimpPush List Settings', "Choose which list MailChimp
         will synchronize to when new users register, or existing ones change 
         their email address."); ?>
   </div>
   <ul class="MailingList">
      <li><?php
         echo $this->Form->label("Mailing List", "ListID");
         echo $this->Form->dropDown('ListID', $this->data('Lists'), array('IncludeNull' => TRUE));
      ?></li>
      
      <li><?php
         echo $this->Form->checkBox('ConfirmJoin', 'Send confirmation email?');
      ?></li>
   </ul>
   <?php endif; ?>
   
   <?php echo $this->Form->close('Save'); ?>
</div>

<?php if ($this->data('Configured')): ?>
<div class="InfoRow MailChimpSync">
   
   <?php if (!defined('APPLICATION')) exit();
   echo $this->Sync->open();
   echo $this->Sync->errors();
   ?>
   
   <div class="Warning"><?php echo t('Mass Synchronization'); ?></div>
   <div class="Info">
      <?php echo t('About MailChimpPush Synchronization', "By default, Vanilla only sends <b>changes</b> to MailChimp. Synchronization
      is a one-time action that allows an entire forum's worth of users email 
      addresses to be pushed to MailChimp to populate a list."); ?>
   </div>
   
   <div class="Synchronization">
      <div class="SyncProgressTitle">Progress <span></span></div>
      <div class="SyncBar"><div class="SyncProgress"></div></div>
   </div>
   
   <ul class="SyncList">
      <li><?php
         echo $this->Sync->label("Sync to List", "SyncListID");
         echo $this->Sync->dropDown('SyncListID', $this->data('Lists'), array('IncludeNull' => TRUE));
      ?></li>
      <li><?php
         echo $this->Sync->checkBox('SyncConfirmJoin', 'Send confirmation email?');
      ?></li>
      <li><?php
         echo $this->Sync->label("User Selection");
         echo $this->Sync->checkBox('SyncBanned', 'Sync banned users');
         echo $this->Sync->checkBox('SyncDeleted', 'Sync deleted users');
         
         if ($this->data('ConfirmEmail', false))
            echo $this->Sync->checkBox('SyncUnconfirmed', 'Sync users with unconfirmed email addreses');
      ?></li>
   </ul>
   
   <div class="Buttons">
      <?php echo $this->Sync->button('Synchronize', array(
          'class' => 'Button',
          'type' => 'button',
          'id' => 'MailChimp-Synchronize'
      )); ?>
   </div>
   
   <?php echo $this->Sync->close(); ?>
</div>
<?php endif; ?>
