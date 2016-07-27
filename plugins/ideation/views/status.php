<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo $this->Form->label('Name', 'Name'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Name'); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="input-wrap no-label">
                <?php echo $this->Form->radio('State', '<strong>Open</strong> '.t("An idea in this status is open to be voted on."), array('Value' => 'Open', 'Default' => 'Open')); ?>
                <?php echo $this->Form->radio('State', '<strong>Closed</strong> '.t("An idea in this status is closed for voting."), array('Value' => 'Closed', 'Default' => 'Open')); ?>
            </div>
        </li>

    <?php
    if (val('IsDefault', $this->Form->formData())) {
        echo '<li class="alert alert-success padded">'.t('Default Status').'</> '.t("This is the starting status for new ideas.").'</li>';
    } else { ?>
        <li class="form-group row">
            <div class="input-wrap no-label">
                <?php echo $this->Form->checkbox('IsDefault', '<strong>'.t('Default Status').'</strong> '.t("Make this the starting status for new ideas.")); ?>
            </div>
    <?php } ?>
    </ul>
<?php
echo '<div class="js-modal-footer form-footer">';
echo $this->Form->Button('Save');
echo '</div>';

echo $this->Form->Close();
