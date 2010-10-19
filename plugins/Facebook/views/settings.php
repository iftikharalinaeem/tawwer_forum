<?php if (!defined('APPLICATION')) exit();
?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo '<p>', T('You have to set up a Facebook application to use this plugin.', 'You have to set up a Facebook application to use this plugin. You can set up an application at <a href="http://www.facebook.com/developers/apps.php">http://www.facebook.com/developers/apps.php</a>.'), '</p>';

         echo '<p>', T('Make sure you enter the following information for your application.', 'When you register your application you can enter what you want for most fields, but make sure you enter the following information.'), '</p>';

         echo '<p>';
         echo '<b>Site URL</b>: ', rtrim(Gdn::Request()->Domain(), '/').'/';
         echo '</p>';
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Application ID', 'ApplicationID');
         echo $this->Form->TextBox('ApplicationID');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Application Secret', 'Secret');
         echo $this->Form->TextBox('Secret');
      ?>
   </li>
</ul>
<?php 
   echo $this->Form->Button('Save', array('class' => 'Button SliceSubmit'));
   echo $this->Form->Close();
