<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
if (val('IsDefault', $this->Form->formData())) {
    echo '<div class="alert alert-info padded"><span class="label">'.t('Default Status').'</span> '.t("This is the starting status for new ideas.").'</div>';
}
?>
    <ul>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Name', 'Name'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Name'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="input-wrap no-label">
                <?php echo $this->Form->radio('State', '<strong>Open</strong> '.t("An idea in this status is open to be voted on."), ['Value' => 'Open', 'Default' => 'Open']); ?>
                <?php echo $this->Form->radio('State', '<strong>Closed</strong> '.t("An idea in this status is closed for voting."), ['Value' => 'Closed', 'Default' => 'Open']); ?>
            </div>
        </li>

    <?php
    if (!val('IsDefault', $this->Form->formData())) { ?>
        <li class="form-group">
            <div class="input-wrap no-label">
                <?php echo $this->Form->checkbox('IsDefault', '<strong>'.t('Default Status').'</strong> '.t("Make this the starting status for new ideas.")); ?>
            </div>
    <?php } ?>
    </ul>
<?php
echo $this->Form->close('Save');