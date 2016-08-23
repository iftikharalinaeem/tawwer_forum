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
    <ul>
        <li><?php
            $categories = CategoryModel::instance()->getAll()->resultArray();
            $categories = Gdn_DataSet::index($categories, 'CategoryID');
            unset($categories[-1]);

            echo $form->label(t('Pre-Moderated Category'), 'PreModeratedCategory.CategoryID');
            echo $form->checkBoxList('PreModeratedCategory.IDs', $categories, null, ['ValueField' => 'CategoryID', 'TextField' => 'Name'])
            ?></li>
    </ul>
<?php echo $form->close('Save');
