<?php if (!defined('APPLICATION')) exit(); ?>
<div id="GroupForm" class="FormTitleWrapper">
   <h1><?php echo $this->Data('Title'); ?></h1>
   <div class="FormWrapper StructuredForm">
      <?php
      echo $this->Form->Open();
      echo $this->Form->Errors();
      ?>
      <div class="P P-Name">
         <?php
         echo $this->Form->Label('Name', 'Name', array('class' => 'B'));
         echo $this->Form->TextBox('Name', array('maxlength' => 100, 'class' => 'InputBox BigInput'));
         ?>
      </div>
      <div class="P P-Body">
         <?php
         echo $this->Form->BodyBox('Body');
         ?>
      </div>
      <div class="Buttons">
         <?php
         $Group = $this->Data('Group');
         if ($Group)
            echo Anchor(T('Cancel'), GroupUrl($Group), 'Button');
         else
            echo Anchor(T('Cancel'), '/groups', 'Button');
         
         echo ' '.$this->Form->Button('Save', array('class' => 'Button Primary'));
         ?>
      </div>
      <?php echo $this->Form->Close(); ?>
   </div>
</div>