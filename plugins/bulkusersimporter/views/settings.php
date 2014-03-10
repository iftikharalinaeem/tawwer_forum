<?php if (!defined('APPLICATION')) exit(); ?>

<div id="bulk-importer">
   <h1>
      <?php echo $this->Data('Title'); ?>
   </h1>

   <div class="Info">
      Use this importer to bulk import CSV files.
   </div>

   <div class="Info">The format of a CSV file must be
      <code>Email,Username,Status</code> per line, in that order. There must be exactly one comma
      between each, no trailing comma, with a maximum of three values. Each
      grouping of three are on their own line. If the CSV file has a header
      line, check the option below to let the importer skip it. <strong>All
      three parameters are required.</strong>
   </div>

   <div class="Info">The third parameter, <code>Status</code>, can have
      multiple items. These items must be separated by a colon <code>:</code>. Valid
      statuses are: <em class="valid-statuses"><?php echo implode(', ', $this->Data('allowed_roles')); ?></em>.
   </div>

   <div class="Info">
      <strong>Note:</strong> If the CSV file will contain usernames with
      non-Latin characters, it is important that its default encoding
      be UTF-8. Not doing this can result in corrupted characters in usernames.
   </div>

   <h3>Example contents of CSV file:</h3>

   <div class="Info">
      This example contains five lines. The first line has headers. Headers
      do not need to be present. If they are, check the option below so the
      importer can skip that line. The importer will fail if the syntax of the
      CSV file does not match the syntax in the example.

   <pre>
   Email,Username,Status
   john@example.com,johnny,Member
   paul@example2.com,paul,Member:Moderator
   kenny@example3.com,ken,Member:Banned
   tywin@example4.com,lanman,Member:Administrator:Moderator</pre>
   </div>


   <h3>Upload CSV file or link to CSV file:</h3>

   <?php
      echo $this->Form->Open(array('enctype' => 'multipart/form-data', 'action' => '/settings/bulkusersimporter/upload', 'id' => 'bulk-importer-form'));
      echo $this->Form->Errors();
   ?>

   <div class="Info">
      Upload a CSV file or provide the URL to a CSV file. If both inputs are filled, the
      uploaded file takes precedence. There is a max filesize of
      <?php echo C('Garden.Upload.MaxFileSize'); ?> for the upload component.
      The file size limit for a URL is higher.
   </div>

   <ul id="bulk-importer-list">
      <li id="bulk-importer-validation-feedback">
         yoooo
      </li>
      <li id="bulk-importer-file-download">
         <?php
            echo $this->Form->Label('Upload CSV:', 'import_files');
            echo $this->Form->Input('import_files[]', 'file', array('multiple' => 'multiple'));
         ?>
      </li>

      <li id="bulk-importer-file-url">
         <?php
            echo $this->Form->Label('Or download CSV from URL:', 'import_url');
            echo $this->Form->Input('import_url', 'text');
         ?>
      </li>

      <li>
         <?php
            echo $this->Form->CheckBox('has_headers', "Check this option if the CSV file's first line contains headers.");
         ?>
      </li>
   </ul>

   <?php echo $this->Form->Close('Start'); ?>
</div>
