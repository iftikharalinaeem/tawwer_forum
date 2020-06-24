<?php if (!defined('APPLICATION')) exit(); ?>

    <h1><?php echo t($this->Data['Title']); ?></h1>

<?php
/**
 * @var $form Gdn_Form
 */
$form = $this->Form;

echo $form->open();
echo $form->errors();
?>
<div class="form-group">
    <ul class="label-wrap">
        <li>
            <?php echo $form->checkBox('PreModeratedCategory.Discussions', t('Discussions')); ?>
        </li>
        <li>
            <?php echo $form->checkBox('PreModeratedCategory.Comments', t('Comments')); ?>
        </li>
    </ul>
</div>
<div class="form-group">
    <ul class="label-wrap">
        <li><?php
            $categories = CategoryModel::instance()->getAll()->resultArray();
            $categories = Gdn_DataSet::index($categories, 'CategoryID');
            unset($categories[-1]);

            echo '<h2>'.t('Pre-Moderated Categories').'</h2>';
            echo $form->checkBoxList('PreModeratedCategory.IDs', $categories, null, ['ValueField' => 'CategoryID', 'TextField' => 'Name'])
            ?></li>
    </ul>
</div>
<?php echo $form->close('Save');
