<?php if (!defined('APPLICATION')){exit();} ?>

<h1><?php echo t($this->title()); ?></h1>

<?php helpAsset(sprintf(t('About %s'), $this->title()), t('CategoryExport.Description', 'Export discussions and comments for any category.')); ?>

<div class="padded alert alert-info">
    <?php echo sprintf(t('CategoryExport.limits', 'Data may be exported once every <b>%d</b> hours (and every <b>%d</b> hours per category), last export was <b>%s</b>.'), $this->data('cooldown'), $this->data('categorycooldown'), $this->data('lastexport')); ?>
</div>

<?php if ($this->data('canexport')): ?>

<?php
$this->form->setStyles('bootstrap'); // For some reason, this endpoint isn't hitting the base_render_before in dashboard hooks where this get set normally
echo $this->form->Open();
echo $this->form->Errors();
?>

<ul>

    <li class="form-group">
        <div class="label-wrap">
            <?php echo $this->form->label('Choose a category'); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->form->categoryDropDown('CategoryID', [
                'IncludeNull' => true
            ]); ?>
        </div>
    </li>

    <li class="form-group">
        <div class="label-wrap">
            <?php echo $this->form->label('Included data'); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->form->checkbox('Contents', 'Discussions', ['value' => 'discussions', 'disabled' => true, 'checked' => true]); ?>
            <?php echo $this->form->checkbox('Contents', 'Comments', ['value' => 'comments']); ?>
        </div>
    </li>

    <li class="form-group">
        <div class="label-wrap">
            <?php echo $this->form->label('Choose a format'); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->form->dropDown('Format', [
                CategoryExportPlugin::FORMAT_CSV => t('Comma Separated (csv)'),
                CategoryExportPlugin::FORMAT_JSON => t('Javascript Object Notation (json)')
            ], [
                'default' => CategoryExportPlugin::FORMAT_CSV,
                'IncludeNull' => false
            ]);
            ?>
        </div>
    </li>

</ul>

<?php echo $this->form->close('Export'); ?>

<?php else: ?>

<div class="alert alert-warning padded">
    <?php echo sprintf(t('CategoryExport.CoolingDown', 'Last export was too recent, please wait <b>%s</b> and try again.'), $this->data('delay')); ?>
</div>

<?php endif; ?>
