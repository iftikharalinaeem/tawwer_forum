<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t('Hub Role'); ?></h1>
<div class="PageInfo">
    <h2><?php echo t('Heads up!'); ?></h2>
    <p>
        This role is managed within your hub. You should edit the role there.
        If you want to override the link to the hub with the role select the option below.
    </p>
</div>
<?php
if (!checkPermission('Garden.Settings.Manage')) {
    return;
}
/* @var Gdn_Form $Form */
$Form = $this->Form;
echo $Form->open(['action' => url('/role/overridehub/'.$this->data('RoleID'))]);
echo $Form->errors();
?>
    <ul>
        <li>
            <?php
            echo $Form->label('Override Hub', 'OverrideHub');
            echo $Form->radioList('OverrideHub', ['0' => t('No'), '1' => t('Yes')]);
            ?>
        </li>
    </ul>
<?php echo $Form->close('Save'); ?>
