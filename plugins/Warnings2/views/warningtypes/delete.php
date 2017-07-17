<?php if (!defined('APPLICATION')) exit; ?>
<h1>
<?php
    /**
     * @var $this WarningTypesController
     * @var $form Gdn_Form
     */
    $form = $this->Form;
    echo t('Are you sure?'); ?>
</h1>
<div class="Info">
    <?php echo t('Delete warning type message', 'Deleting this warning will have no effect on existing users.') ?>
</div>
<?php
echo $form->open(['class' => 'WarningTypeDelete']);
echo '<div class="Buttons Buttons-Confirm">';
echo anchor(t('Cancel'), '/settings/warnings', 'Button CancelButton');
echo anchor(t('Confirm'), '/warningtypes/delete/'.$this->data('WarningTypeID').'/delete', 'Button ConfirmButton');
echo '</div>';
echo $form->close();


