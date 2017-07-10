<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="padded alert alert-warning">
   <?php echo sprintf(t('You must register your application with %s for this plugin to work.'), t('LinkedIn')); ?>
</div>
<div class="padded">
   <?php echo t('Linked In social sign in allows users to sign in using their LinkedIn account.'); ?>
   <?php echo ' '.anchor(sprintf(t('How to set up %s.'), t('LinkedIn social sign in')), 'http://docs.vanillaforums.com/help/addons/social/linkedin', ['target' => '_blank']); ?>
</div>
<div class="Configuration">
   <div class="ConfigurationForm">
       <h2>Authentication Keys</h2>
      <?php
      $Cf = $this->ConfigurationModule;
      $Cf->Render();
      ?>
   </div>
</div>
