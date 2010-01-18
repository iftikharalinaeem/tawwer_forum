<?php if (!defined('APPLICATION')) exit(); ?>
<div class="PreviewTheme">
   <p>You are previewing your custom CSS revisions.</p>
   <?php
   $Form = new Gdn_Form();
   echo $Form->Open(array('action' => 'plugin/customcss'));
   echo $Form->Button('Apply Changes', array('class' => 'PreviewButton'));
   echo $Form->Button('Exit Preview', array('class' => 'PreviewButton'));
   echo $Form->Close();
   ?>
</div>