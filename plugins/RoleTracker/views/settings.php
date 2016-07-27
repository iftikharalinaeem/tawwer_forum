<h1><?php echo t($this->Data['Title']); ?></h1>
<?php
/* @var SettingsController $this */
echo $this->Form->open(['class' => 'RoleTracker Settings', 'enctype' => 'multipart/form-data']);
echo $this->Form->errors();
echo '<div class="padded">'.t('Choose which roles to track.').'</div>';
foreach($this->data('Roles') as $roleID => $role) { ?>
    <div class="form-group row">
        <div class="label-wrap-wide">
            <?php echo $this->Form->label($this->Form->formData()[$roleID.'_Name'], $roleID.'_Name'); ?>
        </div>
        <div class="input-wrap-right">
            <?php echo $this->Form->toggle($roleID.'_IsTracked'); ?>
        </div>
    </div>
<?php
}
?>
<div class="form-footer">
    <?php echo $this->Form->close('Save'); ?>
</div>
