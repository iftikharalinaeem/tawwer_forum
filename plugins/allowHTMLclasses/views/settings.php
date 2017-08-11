<?php if (!defined('APPLICATION')) exit();?>
<h1><?php echo t('Trusted HTML Classes'); ?></h1>
<?php
/** @var Gdn_Form $form */
$form = $this->Form;
echo $form->open();
echo $form->errors();
?>
</li>
<li class="form-group">
    <?php echo $form->toggle('Garden.AllowTrustedClasses', 'Allow Certain HTML Classes to be Input by Users.', ['id' => 'filterClassSource']); ?>
</li>
<li class="form-group js-foggy" data-is-foggy="<?php echo (c('Garden.AllowTrustedClasses')) ? 'false' : 'true'; ?>">
    <div class="label-wrap">
        <?php echo $form->label('Approved CSS Class Names', 'Garden.TrustedHTMLClasses'); ?>
        <div class="info">
            <p>
                <?php
                echo t(
                    'Create a list of specific classes that will be allowed in user-supplied content. No wildcards (e.g. "class*") will be allowed.'
                );
                ?>
            </p>
            <p><strong><?php echo t('Note'); ?>:</strong> <?php echo t('Specify one HTML class per line.'); ?></p>
        </div>
    </div>
    <div class="input-wrap">
        <?php echo $form->textBox('Garden.TrustedHTMLClasses', ['MultiLine' => true]); ?>
    </div>
</li>
<?php echo $form->close('Save'); ?>
