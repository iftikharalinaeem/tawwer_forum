<?php if (!defined('APPLICATION')) exit();
$Action = ($this->Data('Badge.BadgeID')) ? 'Edit' : 'Add';
$this->Title($Action . ' ' . T('a Badge')); ?>
<div id="BadgeForm">
    <h1><?php echo $this->Data('Title'); ?></h1>
    <?php
    echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
    echo $this->Form->Errors();

    echo '<ul>';

    echo Wrap($this->Form->Label('Name').
        $this->Form->Input('Name'), 'li');

    echo Wrap($this->Form->Label('Slug').
        $this->Form->Input('Slug'), 'li');

    echo Wrap($this->Form->Label('Description', 'Body').
        $this->Form->TextBox('Body', array('MultiLine' => TRUE)), 'li');

    if ($this->Data('Badge.Photo')) {
        echo Img(Gdn_Upload::Url($this->Data('Badge.Photo')));
    }

    echo Wrap($this->Form->Label('Points').
        $this->Form->Input('Points'), 'li');

    echo Wrap($this->Form->Label('Badge Class', 'Class').
      $this->Form->Input('Class'), 'li');

    echo Wrap($this->Form->Label('Badge Class Level', 'Level').
      $this->Form->Input('Level'), 'li');

    $UploadText = $this->Data('Badge.Photo') ? T('Replace Image') : T('Add Image');
    echo Wrap($this->Form->Label($UploadText, 'Photo').
        $this->Form->Input('Photo', 'file'), 'li');

    echo '</ul>';

    echo $this->Form->Close('Save');
    ?>
</div>
