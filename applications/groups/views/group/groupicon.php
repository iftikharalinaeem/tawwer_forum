<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t('Group Icon'); ?></h1>
<?php
$thumbnailSize = $this->data('thumbnailSize');
echo $this->Form->open(array('enctype' => 'multipart/form-data'));
echo $this->Form->errors();
?>
<?php
if ($crop = $this->data('crop')) {
    echo $crop;
}
elseif ($icon = $this->data('icon')) { ?>
    <div class="icons">
        <div class="Padded current-icon">
            <?php echo img($this->data('icon'), array('style' => 'width: '.$thumbnailSize.'px; height: '.$thumbnailSize.'px;')); ?>
        </div>
    </div>
<?php } ?>
<div class="ButtonGroup">
<div class="js-new-group-icon Button ButtonGroup">Upload New Icon</div>
<?php
echo $this->Form->input('Icon', 'file', array('class' => 'js-new-group-icon-upload Hidden'));
if ($icon || $crop) {
    echo anchor(t('Remove Group Icon'), '/group/removegroupicon/'.val('GroupID', $this->data('Group')).'/'.Gdn::session()->transientKey(), 'Button ButtonGroup');
}
?>
<?php echo $this->Form->close();
echo anchor(t('Return to group'), GroupUrl($this->data('Group')), 'Button ButtonGroup');
echo '</div>';

