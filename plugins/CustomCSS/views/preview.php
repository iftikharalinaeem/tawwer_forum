<?php if (!defined('APPLICATION')) exit(); ?>
<div class="PreviewTheme">
   <p>You are previewing your custom CSS revisions.</p>
   <?php
   $Form = new Gdn_Form();
   echo $Form->Open(['action' => Url('plugin/customcss')]);
   if (Gdn::Config('Plugins.CustomCSS.Enabled'))
      echo $Form->Button('Apply Changes', ['class' => 'PreviewButton', 'Name' => 'Form/ApplyChanges']);
      
   echo $Form->Button('Exit Preview', ['class' => 'PreviewButton', 'Name' => 'Form/ExitPreview']);
   echo $Form->Close();
   ?>
</div>