<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div class="Tabs ConversationsTabs">
   <h1><?php echo $this->Participants; ?></h1>
   <div class="SubTab">
      <span class="BreadCrumb FirstCrumb"> &rarr; </span><?php
      echo Anchor(T('All Conversations'), '/messages/all');
      ?><span class="BreadCrumb"> &rarr; </span><?php
      echo $this->Participants;
      ?>
   </div>   
</div>
<?php
echo $this->Pager->ToString('less');
?>
<ul class="MessageList Conversation">
   <?php
   $MessagesViewLocation = $this->FetchViewLocation('messages');
   include($MessagesViewLocation);
   ?>
</ul>
<?php echo $this->Pager->ToString(); ?>
<div id="MessageForm">
   <h2><?php echo T('Add Message'); ?></h2>
   <?php
   echo $this->Form->Open(array('action' => Url('/messages/addmessage/')));
   echo $this->Form->TextBox('Body', array('MultiLine' => TRUE, 'class' => 'MessageBox'));
   echo $this->Form->Button('Send Message');
   echo $this->Form->Close();
   ?>
</div>
