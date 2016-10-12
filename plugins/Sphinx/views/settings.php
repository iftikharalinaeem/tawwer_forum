<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="alert alert-warning padded">
   <?php
   echo t('Warning: This is for advanced users.');
   ?>
</div>
<?php

helpAsset(t('Need More Help?'), anchor(t('Vanilla Sphinx Help'), 'http://vanillaforums.org/docs/sphinx'));

echo $this->Form->open();
echo $this->Form->errors();
?>
<?php echo '<div class="padded">',
t('Enter the connection settings for your sphinx server below.'),
'</div>'; ?>
<ul>
   <li class="form-group">
      <?php
         echo $this->Form->labelWrap('Server', 'Plugins.Sphinx.Server');
         echo $this->Form->textBoxWrap('Plugins.Sphinx.Server');
      ?>
   </li>
   <li class="form-group">
      <?php
         echo $this->Form->labelWrap('Port', 'Plugins.Sphinx.Port');
         echo $this->Form->textBoxWrap('Plugins.Sphinx.Port', array('class' => 'InputBox SmallInput'));
      ?>
   </li>
   <li class="form-group">
      <div class="input-wrap no-label">
         <?php echo $this->Form->checkBox('Plugins.Sphinx.UseDeltas', T('Use delta indexes', 'Use delta indexes (recommended for massive sites)')); ?>
      </div>
   </li>
</ul>
<?php echo $this->Form->close('Save'); ?>
