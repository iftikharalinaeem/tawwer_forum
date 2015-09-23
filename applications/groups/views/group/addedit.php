<?php if (!defined('APPLICATION')) exit(); ?>
<div id="GroupForm" class="FormTitleWrapper">
   <h1><?php echo $this->Data('Title'); ?></h1>

   <?php if ($this->Data('MaxUserGroups')): ?>
      <div class="DismissMessage InfoMessage">
         <?php
         echo sprintf(T('You are allowed to create %s groups.'), $this->Data('MaxUserGroups')),
            ' ',
            Plural($this->Data('CountRemainingGroups'), 'You have %s group remaining.', 'You have %s groups remaining.');
         ?>
      </div>
   <?php endif; ?>

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
      <?php
      $Categories = $this->Data('Categories');
      if (count($Categories) == 1) {
         $Row = array_pop($Categories);
         echo $this->Form->Hidden('CategoryID');
      } else {
         echo '<div class="P P-Category">';
         echo $this->Form->Label('Category', 'CategoryID', array('class' => 'B'));
         echo ' '.$this->Form->DropDown('CategoryID', $Categories, array('IncludeNull' => TRUE));
         echo '</div>';
      }
      ?>
      <div class="P P-Icon">
         <?php
         $thumbnailSize = $this->data('thumbnailSize');
         $icon = $crop = false;
         if (($crop = $this->data('crop')) && !isMobile()) {
             echo $this->Form->Label('Icon', 'Icon', array('class' => 'B'));
             echo $crop;
         } elseif ($icon = $this->data('icon')) {
             echo $this->Form->Label('Icon', 'Icon_New', array('class' => 'B'));  ?>
             <div class="icons">
                 <div class="Padded current-icon">
                     <?php echo img($this->data('icon'), array('style' => 'width: '.$thumbnailSize.'px; height: '.$thumbnailSize.'px;')); ?>
                 </div>
             </div>
         <?php } ?>
          <?php
          if ($icon || $crop) {
              echo wrap(anchor(t('Remove Icon'), '/group/removegroupicon/'.val('GroupID', $this->data('Group')).'/'.Gdn::session()->transientKey(), 'Button StructuredForm P'), 'div');
              echo $this->Form->Label('New Icon', 'Icon_New', array('class' => 'B'));
          } else {
              echo $this->Form->Label('Icon', 'Icon_New', array('class' => 'B'));
          }
          echo $this->Form->input('Icon_New', 'file');
         ?>
      </div>
      <div class="P P-Banner">
         <?php
         echo $this->Form->Label('Banner', 'Banner_New', array('class' => 'B'));
         echo $this->Form->ImageUpload('Banner');
         ?>
      </div>
      <hr />
      <div class="P P-Privacy">
         <?php
         echo '<div><b>'.T('Privacy').'</b></div>';
         echo $this->Form->RadioList('Privacy', array(
            'Public' => '@'.T('Public').'. <span class="Gloss">'.T('Public group.', 'Anyone can see the group and its content. Anyone can join.').'</span>',
            'Private' => '@'.T('Private').'. <span class="Gloss">'.T('Private group.', 'Anyone can see the group, but only members can see its content. People must apply or be invited to join.').'</span>',
            ),
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
