<?php if (!defined('APPLICATION')) exit(); ?>
<div class="header-block">
    <h1><?php echo $this->data('Title'); ?></h1>
    <?php echo anchor(t('Return to settings'), '/settings/samlsso', 'btn btn-primary'); ?>
</div>
<?php
$authenticationKey = $this->data('AuthenticationKey');
if ($authenticationKey) {
    ?>
    <div class="full-border alert alert-info">
    <?php
        echo $this->Form->label('Metadata');
    ?>
        <div>
            You can get the metadata for this service provider here:
            <?php echo anchor('get metadata', '/settings/samlsso/'.$authenticationKey.'/metadata.xml', '', ['target' => '_blank'])?>.
        </div>
    </div>
    <?php
}
echo $this->Form->open();
echo $this->Form->errors();
echo $this->Form->simple($this->data('FormStructure'));
?>
<div class="form-footer">
    <?php echo $this->Form->button('Save'); ?>
</div>
<?php
echo $this->Form->close();
