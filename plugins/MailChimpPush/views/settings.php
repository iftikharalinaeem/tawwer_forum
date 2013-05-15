<style class="text/css">
   /* Settings */
   .MailChimpSettings {
      padding-bottom: 15px;
   }
   .MailChimpSettings ul {
      padding-bottom: 15px !important;
   }
   
   /* Base */
   #Content .MailChimpSync form ul {
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
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="InfoRow MailChimpSettings">
   
   <?php if (!defined('APPLICATION')) exit();
   echo $this->Form->Open();
   echo $this->Form->Errors();
   ?>
   
   <div class="Info">
      <?php echo T('About MailChimpPush', "MailChimp Push synchronizes your users' email addresses with a MailChimp
      mailing list of your choice. When a new user signs up, or when an existing
      user changed their email, Vanilla will send a notification to MailChimp to
      add or update the user."); ?>
   </div>
   
   <div class="Warning"><?php echo T('API Authentication settings'); ?></div>
   <ul>
      <li><?php
         echo $this->Form->Label("API Key", "ApiKey");
         echo $this->Form->TextBox('ApiKey');
      ?></li>
   </ul>
   
   <?php if ($this->Data('Configured')): ?>
   <div class="Warning"><?php echo T('Mailing List settings'); ?></div>
   <ul>
      <li><?php
         echo $this->Form->Label("Mailing List", "ListID");
         echo $this->Form->DropDown('ListID', $this->Data('Lists'), array('IncludeNull' => TRUE));
      ?></li>
      
      <li><?php
         echo $this->Form->CheckBox('ConfirmJoin', 'Send confirmation email?');
      ?></li>
   </ul>
   <?php endif; ?>
   
   <?php echo $this->Form->Close('Save'); ?>
</div>

<?php if ($this->Data('Configured')): ?>
<div class="InfoRow MailChimpSync">
   
   <?php if (!defined('APPLICATION')) exit();
   echo $this->Form->Open();
   echo $this->Form->Errors();
   ?>
   
   <div class="Warning"><?php echo T('Mass Synchronization'); ?></div>
   <div class="Info">
      <?php echo T('About MailChimpPush Synchronization', "By default, Vanilla only sends <b>changes</b> to MailChimp. Synchronization
      is a one-time action that allows an entire forum's worth of users email 
      addresses to be pushed to MailChimp to populate a list."); ?>
   </div>
   
   <div class="Synchronization">
      <div class="SyncProgressTitle">Progress <span></span></div>
      <div class="SyncBar"><div class="SyncProgress"></div></div>
   </div>
   
   <ul>
      <li><?php
         echo $this->Form->Label("Sync to List", "SyncListID");
         echo $this->Form->DropDown('SyncListID', $this->Data('Lists'), array('IncludeNull' => TRUE));
      ?></li>
      <li><?php
         echo $this->Form->CheckBox('SyncConfirmJoin', 'Send confirmation email?');
      ?></li>
      <li><?php
         echo $this->Form->Label("User Selection");
         echo $this->Form->CheckBox('SyncBanned', 'Sync banned users');
         echo $this->Form->CheckBox('SyncDeleted', 'Sync deleted users');
         
         if ($this->Data('ConfirmEmail', false))
            echo $this->Form->CheckBox('SyncUnconfirmed', 'Sync users with unconfirmed email addreses');
      ?></li>
   </ul>
   
   <div class="Buttons">
      <?php echo $this->Form->Button('Synchronize', array(
          'class' => 'Button',
          'type' => 'button',
          'id' => 'MailChimp-Synchronize'
      )); ?>
   </div>
   
   <?php echo $this->Form->Close(); ?>
</div>
<?php endif; ?>
