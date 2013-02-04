<?php if (!defined('APPLICATION')) exit(); ?>
<div id="GroupForm" class="FormTitleWrapper">
   <h1><?php echo $this->Data('Title'); ?></h1>
   <div class="FormWrapper StructuredForm">
      <?php
      echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
      echo $this->Form->Errors();
      ?>
      <div class="P P-Name">
         <?php
         echo $this->Form->Label('Name the Group', 'Name', array('class' => 'B'));
         echo $this->Form->TextBox('Name', array('maxlength' => 100, 'class' => 'InputBox BigInput'));
         ?>
      </div>
      <div class="P P-Description">
         <?php
         echo $this->Form->Label('Description', 'Description', array('class' => 'B'));
         echo $this->Form->BodyBox('Description');
         ?>
      </div>
      <div class="P P-Category">
         <?php
         echo $this->Form->Label('Category', 'CategoryID', array('class' => 'B'));
         echo ' '.$this->Form->CategoryDropDown('CategoryID', array('IncludeNull' => TRUE));
         ?>
      </div>
      <div class="P P-Icon">
         <?php
         echo $this->Form->Label('Icon', 'Icon_New', array('class' => 'B'));
         echo $this->Form->ImageUpload('Icon');
         ?>
      </div>
      <div class="P P-Banner">
         <?php
         echo $this->Form->Label('Banner', 'Banner_New', array('class' => 'B'));
         echo $this->Form->ImageUpload('Banner');
         ?>
      </div>
      <hr />
      <div class="P P-Registration">
         <?php
         echo '<div><b>'.T('How can people join this group?').'</b></div>';
         echo $this->Form->RadioList('Registration', array(
            'Public' => 'Public. Anyone can join.',
            'Approval' => 'Approval. People have to apply and be approved.'),
            array('list' => TRUE));
         ?>
      </div>
      <div class="P P-Visibility">
         <?php
         echo '<div><b>'.T('Content Visibility').'</b></div>';
         echo $this->Form->RadioList('Visibility', array(
            'Public' => "Anyone can view this group's content.",
            'Members' => 'Users must join this group to view its content.'),
            array('list' => TRUE));
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