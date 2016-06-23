<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->title(); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
<ul>
    <li>
        <?php echo $this->Form->label('Exclude users from leaderboards', 'ExcludePermission'); ?>
        <div class="Info"><?php echo t('Users in selected the roles will be excluded from leaderboards.'); ?></div>
        <?php echo $this->Form->dropdown('ExcludePermission', [
            'Garden.Settings.Manage' => 'Administrator',
            'Garden.Moderation.Manage' => 'Administrator and moderator',
            'None' => 'None'
        ]); ?>
    </li>
</ul>
<?php echo $this->Form->close('Save'); ?>
