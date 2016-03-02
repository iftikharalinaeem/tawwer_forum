<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo t($this->Data['Title']); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
<ul>
    <li><?php
        echo $this->Form->label(t('Popular Posts max age (Max 30 days)'), 'PopularPosts.MaxAge');
        echo $this->Form->textbox('PopularPosts.MaxAge');
        ?></li>
</ul>
<?php echo $this->Form->close('Save');
