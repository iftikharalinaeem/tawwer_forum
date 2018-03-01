<h1><?php echo t('Watermark'); ?></h1>
<div class="PageInfo">
    <h2><?php echo t('Add a Watermark Image'); ?></h2>

    <div class="alert alert-warning padded">
        <?php
        echo t('watermark image descriptions',
            'Upload a transparent PNG that will be superimposed over any images you upload with a discussion in designated categories.');
        ?>
    </div>
</div>
<?php
echo $this->Form->open(['enctype' => 'multipart/form-data']);
echo $this->Form->errors();
?>

<div class="padded">
    <ul>
        <li>
<?php

$uploaded_watermark = $this->data('uploaded_watermark');
if ($uploaded_watermark) {
    echo wrap(
        img(Gdn_Upload::url($uploaded_watermark), ['style' =>"max-width: 200px"]),
        'div'
    );

    echo $this->Form->button('Remove Watermark', ['name' => 'delete_watermark', 'type' => 'submit']);
    echo wrap(
        t('Watermark Browse', 'Browse for a new watermark if you would like to change it:'),
        'div',
        ['class' => 'Info']
    );
} else {
    echo '<div class="padded">'.t('You have no watermark uploaded').'</div>';
}

echo $this->Form->input('watermark_upload', 'file');

echo '<div class="Buttons">'.$this->Form->button('Save').'</div>';
?>
        </li>
    </ul>
</div>
<?php
echo $this->Form->close();
