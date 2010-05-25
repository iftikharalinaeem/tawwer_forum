<?php if (!defined('APPLICATION')) exit(); ?>
<div class="PreviewTheme">
   <p>You are previewing your custom theme revisions.</p>
   <?php
   $Form = new Gdn_Form();
   echo $Form->Open(array('action' => Url('settings/customtheme')));
   if (C('Plugins.CustomTheme.Enabled'))
      echo $Form->Button('Apply Changes', array('class' => 'PreviewButton', 'Name' => 'Form/ApplyChanges'));
      
   echo $Form->Button('Exit Preview', array('class' => 'PreviewButton', 'Name' => 'Form/ExitPreview'));
   echo $Form->Close();
   ?>
</div>