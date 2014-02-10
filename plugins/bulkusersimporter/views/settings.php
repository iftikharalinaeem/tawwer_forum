<?php if (!defined('APPLICATION')) exit(); ?>

<div id="bulk-importer">
   <h1>
      <?php echo $this->Data('Title'); ?>
   </h1>

   <div class="Info">
      Use this importer to bulk import CSV files. There is a max filesize of <?php echo C('Garden.Upload.MaxFileSize'); ?>.
   </div>

   <div class="Info"><strong>Note:</strong> The format of a CSV file should be
      email,username,status per line, in that order. There should be exactly one comma
      between each, no trailing comma, with a maximum of three values. Each
      grouping of three are on their own line. If the CSV file has a header
      line, check the option below to let the bulk users importer skip it.
   </div>

   <?php
      echo $this->Form->Open(array('enctype' => 'multipart/form-data', 'action' => '/settings/bulkusersimporter/upload'));
      echo $this->Form->Errors();
   ?>


   <ul>
      <li>
         <?php
            echo $this->Form->Input('import_files[]', 'file', array('multiple' => 'multiple'));
         ?>
      </li>

      <li>
         <?php
            echo $this->Form->CheckBox('has_headers', "Check this option if the CSV file's first line contains headers.");
         ?>
      </li>
   </ul>

   <?php echo $this->Form->Close('Start'); ?>


   <?php //$this->Cf->Render(); ?>
</div>
