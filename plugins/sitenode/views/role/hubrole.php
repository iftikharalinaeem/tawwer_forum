<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T('Hub Role'); ?></h1>
<div class="PageInfo">
    <h2><?php echo T('Heads up!'); ?></h2>
    <p>
        This role is managed within your hub. You should edit the role there.
        If you want to override the link to the hub with the role select the option below.
    </p>
</div>
<?php
if (!CheckPermission('Garden.Settings.Manage')) {
    return;
}
/* @var Gdn_Form $Form */
$Form = $this->Form;
echo $Form->Open(['action' => url('/role/overridehub/'.$this->Data('RoleID'))]);
echo $Form->Errors();
?>
    <ul>
        <li>
            <?php
            echo $Form->Label('Override Hub', 'OverrideHub');
            echo $Form->RadioList('OverrideHub', ['0' => T('No'), '1' => T('Yes')]);
            ?>
        </li>
    </ul>
<?php echo $Form->Close('Save'); ?>
