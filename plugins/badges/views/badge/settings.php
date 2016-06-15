<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?echo t('Leaderboards'); ?></h1>
<?php echo $this->Form->open(); ?>
<ul>
    <li>
        <?php echo $this->Form->label('Exclude users from leaderboards', 'ExcludePermission'); ?>
        <div class="Info"><?php echo t('Users in selected roles will be excluded from leaderboards.'); ?></div>
        <?php echo $this->Form->dropdown('ExcludePermission', [
            'Garden.Settings.Manage' => 'Administrators',
            'Garden.Moderation.Manage' => 'Administrators and moderators',
            'None' => 'No exclusions'
        ]); ?>
    </li>
</ul>
<?php echo $this->Form->close('Save'); ?>
