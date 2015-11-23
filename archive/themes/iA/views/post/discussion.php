<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div id="DiscussionForm">
   <div class="Photo"><?php echo UserPhoto(UserBuilder($Session->User)); ?></div>
   <?php
      $this->Form->SetModel(new DiscussionModel());
      echo $this->Form->Open(array('action' => Url('/vanilla/post/discussion')));
      echo $this->Form->Errors();
      echo $this->Form->Hidden('DiscussionID', array('value' => ''));
      echo $this->Form->Hidden('Name', array('value' => 'Empty'));
      echo $this->Form->TextBox('Body', array('value' => "What's on your mind?", 'MultiLine' => TRUE));
      echo $this->Form->Button('Share', array('class' => 'Button DiscussionButton'));
      $this->FireEvent('AfterFormButtons');
      echo $this->Form->Close();
   ?>
</div>
