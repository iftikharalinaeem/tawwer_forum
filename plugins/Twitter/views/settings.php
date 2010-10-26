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
         echo T('You have to register your application with Twitter to use this plugin.', 'You have to register your application with Twitter to use this plugin. You can set up an application at <a href="http://dev.twitter.com/apps/new">http://dev.twitter.com/apps/new</a>.');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Consumer Key', 'ConsumerKey');
         echo $this->Form->TextBox('ConsumerKey');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Consumer Secret', 'Secret');
         echo $this->Form->TextBox('Secret');
      ?>
   </li>
</ul>
<?php
   echo $this->Form->Button('Save', array('class' => 'Button SliceSubmit'));
   echo $this->Form->Close();