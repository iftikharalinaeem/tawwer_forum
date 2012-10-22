<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Warning">
   <?php
   echo T('Warning: This is for advanced users.');
   ?>
</div>
<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo '<li>', Anchor(T('Vanilla Sphinx Help'), 'http://vanillaforums.org/docs/sphinx'), '</li>';
   echo '</ul>';
   ?>
</div>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
      echo '<div class="Info">',
         T('Enter the connection settings for your sphinx server below.'),
         '</div>';
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Server', 'Plugins.Sphinx.Server');
         echo $this->Form->TextBox('Plugins.Sphinx.Server');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Port', 'Plugins.Sphinx.Port');
         echo $this->Form->TextBox('Plugins.Sphinx.Port', array('class' => 'InputBox SmallInput'));
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->CheckBox('Plugins.Sphinx.UseDeltas', T('Use delta indexes', 'Use delta indexes (recommended for massive sites)'));
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->CheckBox('Plugins.Sphinx.ForceInnoDB', T('Change tables to InnoDB', 'Change tables to InnoDB (see help)'));
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save'); ?>
<h1><?php echo T('Tools'); ?></h1>
<div class="Info">
   <p>
      <?php
         echo T('Generate sphinx.conf'), ' ',
            Anchor(T('Generate'), '/settings/sphinx/sphinx.conf', array('class' => 'SmallButton'));
      ?>
   </p>
</div>