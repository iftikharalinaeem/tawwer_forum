<?php if (!defined('APPLICATION')) exit(); ?>
<div class="PreviewTheme">
   <p>You are previewing your custom CSS revisions.</p>
   <?php
   $Form = new Gdn_Form();
   echo $Form->open(['action' => url('plugin/customcss')]);
   if (Gdn::config('Plugins.CustomCSS.Enabled'))
      echo $Form->button('Apply Changes', ['class' => 'PreviewButton', 'Name' => 'Form/ApplyChanges']);
      
   echo $Form->button('Exit Preview', ['class' => 'PreviewButton', 'Name' => 'Form/ExitPreview']);
   echo $Form->close();
   ?>
</div>