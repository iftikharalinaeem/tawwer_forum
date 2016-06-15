<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="message alert-danger danger">
   Linked In social sign in allows users to sign in using their LinkedIn account.
   <b>You must register your application with LinkedIn for this addon to work.</b>
</div>
<div class="Configuration">
   <div class="ConfigurationForm">
       <h2>Authentication Keys</h2>
      <?php
      $Cf = $this->ConfigurationModule;
      $Cf->Render();
      ?>
   </div>
   <div class="ConfigurationHelp">
      <strong>How to set up LinkedIn Social Sign in</strong>
      <ol>
         <li>
            Go to the LinkedIn Developer Network at <a href="https://www.linkedin.com/secure/developer">https://www.linkedin.com/secure/developer</a>.
         </li>
         <li>
            Click <b>Add New Application</b>.
         </li>
         <li>
            When you create the application, you can choose what to enter in most fields, but you have to make sure you enter specific information for some fields.
         </li>
         <li>
            Under <b>Website URL</b> enter <code class="prettyprint"><?php echo url('/', true); ?></code>.
         </li>
         <li>
            Under <b>Default Scope</b> make sure you've selected at least <b>r_basicprofile</b> and <b>r_emailaddress</b>.
         </li>
          <li>
              Under <b>OAuth 2.0 Redirect URLs</b> add <code class="prettyprint"><?php echo url('/entry/connect/linkedin', true); ?></code>
              <b>and</b> <code class="prettyprint"><?php echo url('/profile/linkedinconnect', true); ?></code>
          </li>
         <li>
            Once your application has been set up, you must copy the <b>Client ID</b> and <b>Client Secret</b> into the form on this page.
         </li>
         <li>
            Don't forget to hit update!
         </li>
      </ol>
      <p><?php echo Anchor(Img('/plugins/LinkedIn/design/linkedinscreenshot.jpg', array('style' => 'max-width: 837px;')), '/plugins/LinkedIn/design/linkedinscreenshot.jpg', array('target' => '_blank')); ?></p>
   </div>
</div>
