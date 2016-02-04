<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo T($this->Data['Title']); ?></h1>
<div class="Info">
    <?php echo t($this->Data['PluginDescription']); ?>
</div>
<h3><?php echo t('Settings'); ?></h3>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
<ul>
    <li><?php
        echo $this->Form->label('Popular posts max age (Max 30 days)', 'Plugin.PopularPosts.MaxAge');
        echo $this->Form->textbox('Plugin.PopularPosts.MaxAge');
        ?></li>
</ul>
<?php
echo $this->Form->close('Save');
?>
