<h1><?php echo $this->data('Title'); ?></h1>
<?php
$form = $this->Form;
echo $form->open([
    'enctype' => 'multipart/form-data',
]);
?>

<div class="padded">
    <div class="padded alert alert-info"><?php echo t('Uplaod a placeholder image that will display when users do not have an image in their post.'); ?></div>

    <?php if ($this->Data('PlaceholderImage'))  : ?>
        <div class="padded"><?php echo img($this->Data('PlaceholderImage')); ?></div>
    <?php endif; ?>
<?php
    echo $form->errors();
    echo $form->fileUpload('Photo');
?>
</div>

<div class="buttons form-footer">
    <?php echo $this->Form->button('Delete', ['Type' => 'submit', 'class' => 'btn btn-primary padded-left']); ?>
    <?php echo $this->Form->button('Save', ['Type' => 'submit', 'class' => 'btn btn-primary']); ?>
</div>

<?php
echo $form->close();
