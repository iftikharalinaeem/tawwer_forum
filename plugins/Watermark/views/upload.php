<h1><?php echo t('Watermark'); ?></h1>
<div class="PageInfo">
    <h2><?php echo t('Add a Watermark Image'); ?></h2>

    <p>
        <?php
        echo t('watermark image descriptions',
            'Upload a transparent PNG that will be superimposed over any images you upload with a discussion in designated categories.');
        ?>
    </p>
</div>
<?php
echo $this->Form->open(array('enctype' => 'multipart/form-data'));
echo $this->Form->errors();
?>

<div class="Row">
    <div class="Column Grid_100">
        <ul>
            <li>
<?php

$watermark = $this->data('watermark');
if ($watermark) {
    echo wrap(
        img(Gdn_Upload::url($watermark), array('style' =>"max-width: 200px")),
        'div'
    );

    echo $this->Form->button('Remove Watermark', array('name' => 'delete_watermark', 'type' => 'submit'));
    echo wrap(
        t('Watermark Browse', 'Browse for a new watermark if you would like to change it:'),
        'div',
        array('class' => 'Info')
    );
}

echo $this->Form->Input('watermark', 'file');

echo '<div class="Buttons">'.$this->Form->button('Save').'</div>';
?>
            </li>
        </ul>
    </div>
</div>
<?php
echo $this->Form->close();
