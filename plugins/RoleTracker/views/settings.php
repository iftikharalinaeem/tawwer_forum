<h1><?php echo t($this->Data['Title']); ?></h1>
<?php
/* @var SettingsController $this */
echo $this->Form->open(['class' => 'RoleTracker Settings', 'enctype' => 'multipart/form-data']);
echo $this->Form->errors();
?>
<div class="padded">
    <p><?php echo t('Tracked roles get extra CSS classes.', 'Discussions and comments made by users with a <b>tracked role</b> will be tagged with that role, and will receive a CSS class to allow visual customization.'); ?></p>
    <p><?php echo t('Tracked roles make content more visible.', 'This is useful for letting regular members know that a staff member has posted in a discussion, for example. <b>This functionality is not retro-active.</b>'); ?></p>
    <p><?php echo t('Choose which roles to track below.'); ?></p>
</div>
<?php
foreach($this->data('Roles') as $roleID => $role) {
?>
    <div class="form-group">
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
