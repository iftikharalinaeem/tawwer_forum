<?php if (!defined('APPLICATION')) exit();
$Action = ($this->data('Badge.BadgeID')) ? 'Edit' : 'Add';
$this->title($Action . ' ' . t('a Badge')); ?>
<h1><?php echo $this->data('Title'); ?></h1>
<div id="BadgeForm">
    <?php
    echo $this->Form->open(['enctype' => 'multipart/form-data']);
    echo $this->Form->errors();

    echo '<ul>';

    echo wrap($this->Form->labelwrap('Name').
        $this->Form->inputwrap('Name'), 'li', ['class' => 'form-group']);

    echo wrap($this->Form->labelwrap('Slug').
        $this->Form->inputwrap('Slug'), 'li', ['class' => 'form-group']);

    echo wrap($this->Form->labelwrap('Description', 'Body').
        $this->Form->textBoxwrap('Body', ['MultiLine' => true]), 'li', ['class' => 'form-group']);

    echo wrap($this->Form->labelwrap('Points').
        $this->Form->inputwrap('Points'), 'li', ['class' => 'form-group']);

    echo wrap($this->Form->labelwrap('Badge Class', 'Class').
      $this->Form->inputwrap('Class'), 'li', ['class' => 'form-group']);

    echo wrap($this->Form->labelwrap('Badge Class Level', 'Level').
      $this->Form->inputwrap('Level'), 'li', ['class' => 'form-group']);

    if ($this->data('HasThreshold')) {
        echo wrap($this->Form->labelwrap('Threshold', 'Threshold').
            $this->Form->inputwrap('Threshold'), 'li', ['class' => 'form-group']);    
    }

    $this->fireEvent('BadgeFormFields');

    $UploadText = $this->data('Badge.Photo') ? t('Replace Image') : T('Add Image');
    echo wrap($this->Form->labelwrap($UploadText.($this->data('Badge.Photo') ? '<div class="image-wrap">'.img(
        Gdn_Upload::url($this->data('Badge.Photo')),
        ['alt' => $this->data('Badge.Name')]
    ).'</div>' : ''), 'Photo').$this->Form->fileUploadwrap('Photo'), 'li', ['class' => 'form-group']);

    echo '</ul>';

    echo $this->Form->close('Save');
    ?>
</div>
