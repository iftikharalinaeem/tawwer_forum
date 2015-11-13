<?php 

if (!defined('APPLICATION')) {
    exit(); 
}

?>

<h1><?php echo t($this->title()); ?></h1>

<div class="Info">
    <p><?php echo t('CategoryExport.Description', 'Export discussions and comments for any category.') ?></p>
    <p><?php echo sprintf(t('CategoryExport.limits', 'Data may be exported once every <b>%d</b> hours (and every <b>%d</b> hours per category), last export was <b>%s</b>.'), $this->data('cooldown'), $this->data('categorycooldown'), $this->data('lastexport')); ?></p>
</div>

<?php if ($this->data('canexport')): ?>

<h3><?php echo t('Export Options'); ?></h3>

<?php
echo $this->form->Open();
echo $this->form->Errors();
?>

<ul>

    <li>
        <?php
        echo $this->form->label('Choose a category');
        echo $this->form->categoryDropDown('CategoryID', [
            'IncludeNull' => true
        ]);
        ?>
    </li>

    <li>
        <?php echo $this->form->label('Included data'); ?>
        <ul style="padding-top: 0px;">
            <li><?php echo $this->form->checkbox('Contents', 'Discussions', ['value' => 'discussions', 'disabled' => true, 'checked' => true]); ?></li>
            <li><?php echo $this->form->checkbox('Contents', 'Comments', ['value' => 'comments']); ?></li>
        </ul>
    </li>
    
    <li>
        <?php
        echo $this->form->label('Choose a format');
        echo $this->form->dropDown('Format', [
            CategoryExportPlugin::FORMAT_CSV => t('Comma Separated (csv)'),
            CategoryExportPlugin::FORMAT_JSON => t('Javascript Object Notation (json)')
        ], [
            'default' => CategoryExportPlugin::FORMAT_CSV,
            'IncludeNull' => false
        ]);
        ?>
    </li>

</ul>

<div class="Buttons">
    <?php echo $this->form->close('Export'); ?>
</div>

<?php else: ?>

<div class="Info Alert">
    <?php echo sprintf(t('CategoryExport.CoolingDown', 'Last export was too recent, please wait <b>%s</b> and try again.'), $this->data('delay')); ?>
</div>

<?php endif; ?>