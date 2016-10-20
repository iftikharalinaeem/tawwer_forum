<?php if (!defined('APPLICATION')) exit(); ?>
<?php
echo heading($this->data('Title'), '', '', [], '/settings/samlsso');
$authenticationKey = $this->data('AuthenticationKey');
if ($authenticationKey) {
    ?>
    <div class="alert alert-info padded">
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
echo $this->Form->close('Save');
