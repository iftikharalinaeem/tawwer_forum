<?php if (!defined('APPLICATION')) exit();
$Action = ($this->Data('Badge.BadgeID')) ? 'Edit' : 'Add';
$this->Title($Action . ' ' . T('a Badge')); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div id="BadgeForm">
    <?php
    echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
    echo $this->Form->Errors();

    echo '<ul>';

    echo Wrap($this->Form->labelWrap('Name').
        $this->Form->inputWrap('Name'), 'li', ['class' => 'form-group row']);

    echo Wrap($this->Form->labelWrap('Slug').
        $this->Form->inputWrap('Slug'), 'li', ['class' => 'form-group row']);

    echo Wrap($this->Form->labelWrap('Description', 'Body').
        $this->Form->textBoxWrap('Body', array('MultiLine' => TRUE)), 'li', ['class' => 'form-group row']);

    echo Wrap($this->Form->labelWrap('Points').
        $this->Form->inputWrap('Points'), 'li', ['class' => 'form-group row']);

    echo Wrap($this->Form->labelWrap('Badge Class', 'Class').
      $this->Form->inputWrap('Class'), 'li', ['class' => 'form-group row']);

    echo Wrap($this->Form->labelWrap('Badge Class Level', 'Level').
      $this->Form->inputWrap('Level'), 'li', ['class' => 'form-group row']);

    $UploadText = $this->Data('Badge.Photo') ? T('Replace Image') : T('Add Image');
    echo Wrap($this->Form->labelWrap($UploadText.($this->Data('Badge.Photo') ? '<div class="image-wrap">'.img(Gdn_Upload::Url($this->Data('Badge.Photo'))).'</div>' : ''), 'Photo').
        $this->Form->fileUploadWrap('Photo'), 'li', ['class' => 'form-group row']);

    echo '</ul>';

    echo $this->Form->Close('Save');
    ?>
</div>
