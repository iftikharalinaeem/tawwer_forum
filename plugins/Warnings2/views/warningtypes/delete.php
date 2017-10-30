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
<div class="padded alert alert-danger">
    <?php echo t('Delete warning type message', 'Deleting this warning will have no effect on existing users.') ?>
</div>
<?php
echo $form->open(['class' => 'WarningTypeDelete']);
echo '<div class="Buttons Buttons-Confirm">';
echo anchor(t('Cancel'), '/settings/warnings', 'Button CancelButton');
echo $form->button(t('Confirm'));
echo '</div>';
echo $form->close();


