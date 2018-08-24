<?php if (!defined('APPLICATION')) exit(); ?>

<div id="GroupForm" class="FormTitleWrapper">
    <h1><?php echo $this->data('Title'); ?></h1>

    <?php if ($this->data('MaxUserGroups')): ?>
        <div class="DismissMessage InfoMessage">
            <?php
            echo sprintf(t('You are allowed to create %s groups.'), $this->data('MaxUserGroups')),
                ' ',
                plural($this->data('CountRemainingGroups'), 'You have %s group remaining.', 'You have %s groups remaining.');
            ?>
        </div>
    <?php endif; ?>

    <div class="FormWrapper StructuredForm">
        <?php
        echo $this->Form->open(['enctype' => 'multipart/form-data']);
        echo $this->Form->errors();
        ?>
        <div class="P P-Name">
            <?php
            echo $this->Form->label('Name the Group', 'Name', ['class' => 'B']);
            echo $this->Form->textBox('Name', ['maxlength' => 100, 'class' => 'InputBox BigInput']);
            ?>
        </div>
        <div class="P P-Description">
            <?php
            echo $this->Form->label('Description', 'Description', ['class' => 'B']);
            echo $this->Form->bodyBox('Description');
            ?>
        </div>
        <?php
        $Categories = $this->data('Categories');
        if (count($Categories) == 1) {
            $Row = array_pop($Categories);
            echo $this->Form->hidden('CategoryID');
        } else {
            echo '<div class="P P-Category">';
            echo $this->Form->label('Category', 'CategoryID', ['class' => 'B']);
            echo ' '.$this->Form->dropDown('CategoryID', $Categories, ['IncludeNull' => true]);
            echo '</div>';
        }
        ?>
        <div class="P P-Icon">
            <?php
            $thumbnailSize = $this->data('thumbnailSize');
            $icon = $crop = false;
            if (($crop = $this->data('crop')) && !isMobile()) {
                 echo $this->Form->label('Icon', 'Icon', ['class' => 'B']);
                 echo $crop;
            } elseif ($icon = $this->data('icon')) {
                 echo $this->Form->label('Icon', 'Icon_New', ['class' => 'B']);  ?>
                 <div class="icons">
                      <div class="Padded current-icon">
                            <?php echo img($this->data('icon'), ['style' => 'width: '.$thumbnailSize.'px; height: '.$thumbnailSize.'px;']); ?>
                      </div>
                 </div>
            <?php } ?>
             <?php
             if ($icon || $crop) {
                  echo wrap(anchor(t('Remove Icon'), '/group/removegroupicon/'.val('GroupID', $this->data('Group')).'/'.Gdn::session()->transientKey().'/edit', 'Button StructuredForm P'), 'div');
                  echo $this->Form->label('New Icon', 'Icon_New', ['class' => 'B']);
             } else {
                  echo $this->Form->label('Icon', 'Icon_New', ['class' => 'B']);
             }
             echo $this->Form->input('Icon_New', 'file');
            ?>
        </div>
        <div class="P P-Banner">
            <?php
            echo $this->Form->label('Banner', 'Banner_New', ['class' => 'B']);
            echo $this->Form->imageUpload('Banner');
            ?>
        </div>
        <hr />
        <div class="P P-Privacy">
            <?php
            echo '<div><b>'.t('Privacy').'</b></div>';
            echo $this->Form->radioList('Privacy', [
                'Public' => '@'.t('Public').'. <span class="Gloss">'.t('Public group.', 'Anyone can see the group and its content. Anyone can join.').'</span>',
                'Private' => '@'.t('Private').'. <span class="Gloss">'.t('Private group.', 'Anyone can see the group, but only members can see its content. People must apply or be invited to join.').'</span>',
                'Secret' => '@'.t('Secret').'. <span class="Gloss">'.t('Secret group.', 'Only members can see the group and view its content. People must be invited to join.').'</span>',
                ],
                ['list' => true]);
            ?>
        </div>
        <div class="Buttons">
            <?php
            $Group = $this->data('Group');
            if ($Group)
                echo anchor(t('Cancel'), groupUrl($Group), 'Button');
            else
                echo anchor(t('Cancel'), '/groups', 'Button');

            echo ' '.$this->Form->button('Save', ['class' => 'Button Primary']);
            ?>
        </div>
        <?php echo $this->Form->close(); ?>
    </div>
</div>
