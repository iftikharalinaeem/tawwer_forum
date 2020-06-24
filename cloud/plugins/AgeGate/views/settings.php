<h1><?php echo t('Age Gate Settings'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>

<div class="padded">
    This plugin adds 'Date of Birth' to the registration forms. Users must be at least the age below to complete the
    registration process. Alternatively, you can allow underage users to register with a confirmation of consent.
</div>

<ul>
    <li class="form-group">
        <div class="label-wrap">
            <?php echo $this->Form->label('Minimum Age', 'MinimumAge');  ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->textBox('MinimumAge'); ?>
        </div>
    </li>
    <li class="form-group">
        <div class="input-wrap no-label">
            <?php echo $this->Form->checkBox('AddConfirmation', 'Allow underage users to register with a confirmation of consent.');  ?>
        </div>
    </li>
</ul>


<?php echo $this->Form->close('Save');
