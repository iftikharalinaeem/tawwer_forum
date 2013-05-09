<?php if (!defined('APPLICATION')) exit();
?>
<style type="text/css">
.Configuration {
   margin: 0 20px 20px;
   background: #f5f5f5;
   float: left;
}
.ConfigurationForm {
   padding: 20px;
   float: left;
}
#Content form .ConfigurationForm ul {
   padding: 0;
}
#Content form .ConfigurationForm input.Button {
   margin: 0;
}
.ConfigurationHelp {
   border-left: 1px solid #aaa;
   margin-left: 340px;
   padding: 20px;
}
.ConfigurationHelp strong {
    display: block;
}
.ConfigurationHelp img {
   width: 99%;
}
.ConfigurationHelp a img {
    border: 1px solid #aaa;
}
.ConfigurationHelp a:hover img {
    border: 1px solid #777;
}
input.CopyInput {
   font-family: monospace;
   color: #000;
   width: 240px;
   font-size: 12px;
   padding: 4px 3px;
}

.ConfigurationHelp ol {
   margin: 1em 0 1em 3em;
}
.ConfigurationHelp ol li {
  list-style: decimal !important;
  margin: 10px 0;
}
</style>
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Info">
   Linked In social sign in allows users to sign in using their LinkedIn account.
   <b>You must register your application with LinkedIn for this addon to work.</b>
</div>
<div class="Configuration">
   <div class="ConfigurationForm">
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
            Under <b>Website URL</b> enter <input type="text" class="CopyInput" value="<?php echo rtrim(Gdn::Request()->Domain(), '/').'/'; ?>" />.
         </li>
         <li>
            Under <b>Default Scope</b> make sure you've selected at least <b>r_basicprofile</b> and <b>r_emailaddress</b>.
         </li>
         <li>
            Once your application has been set up, you must copy the <b>API Key</b> and <b>Secret Key</b> into the form on this page.
         </li>
         <li>
            Don't forget to hit save!
         </li>
      </ol>
      <p><?php echo Anchor(Img('/plugins/LinkedIn/design/linkedin-help.png', array('style' => 'max-width: 961px;')), '/plugins/LinkedIn/design/linkedin-help.png', array('target' => '_blank')); ?></p>
   </div>
</div>