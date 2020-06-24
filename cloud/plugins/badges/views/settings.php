<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->title(); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
<ul>
    <li class="form-group">
        <div class="label-wrap">
            <?php echo $this->Form->label('Exclude users from leaderboards', 'ExcludePermission'); ?>
            <div class="info"><?php echo t('Users in selected the roles will be excluded from leaderboards.'); ?></div>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->dropdown('ExcludePermission', [
            'Garden.Settings.Manage' => 'Administrator',
            'Garden.Moderation.Manage' => 'Administrator and moderator',
            'None' => 'None'
        ]); ?>
        </div>
    </li>
</ul>
<?php echo $this->Form->close('Save'); ?>
