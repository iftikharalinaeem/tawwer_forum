<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo t($this->Data['Title']); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
<ul>
    <li class="form-group"><?php
        echo $this->Form->labelWrap(t('Popular Posts max age (Max 30 days)'), 'PopularPosts.MaxAge');
        echo $this->Form->textboxWrap('PopularPosts.MaxAge');
        ?></li>
</ul>
<div class="form-footer js-modal-footer">
<?php echo $this->Form->close('Save'); ?>
</div>
