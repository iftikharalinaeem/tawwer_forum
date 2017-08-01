<?php if (!defined('APPLICATION')) exit(); ?>
<style type="text/css">
   .Col {
      vertical-align: middle;
      display: inline-block;
      font-weight: bold;
      width: auto;
      float: none;
   }
   .Col strong {
      display: block;
   }
   textarea {
      width: 200px !important;
   }
</style>
<h1><?php echo $this->data('Title'); ?></h1>
<div class="Info">
   <h2>Google Analytics Account IDs & Related Domains</h2>
   <p>Specify the accounts used to track pageviews on this forum. You can also (optionally) specify the domain that GA uses to track the pageview.</p>
   <p><small>
      <strong>Notes:</strong>
      Specify one account per line, and one domain per line. Put the matching account & domain on the same line.
      <br />Leaving these inputs blank will disable all tracking.
   </small></p>
   <?php
   echo $this->Form->open();
   echo $this->Form->errors();
   echo '<div class="Col"><strong>Account</strong>';
      echo $this->Form->textBox('Plugins.GoogleAnalytics.Account', ['MultiLine' => TRUE]);
   echo '</div>';
   echo '<div class="Col">-&gt;</div>';
   echo '<div class="Col"><strong>Domain</strong>';
      echo $this->Form->textBox('Plugins.GoogleAnalytics.TrackerDomain', ['MultiLine' => TRUE]);
   echo '</div>';
   echo $this->Form->close('Save', '', ['style' => 'margin: 0;']); 
   ?>
</div>