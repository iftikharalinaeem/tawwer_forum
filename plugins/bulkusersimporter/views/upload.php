<?php if (!defined('APPLICATION')) exit(); ?>

<?php

   $results = $this->Data('results');

   $total_success = count($results['success']);
   $total_fail = count($results['fail']);
   $total_files = $total_success + $total_fail;
   $great_success = ($total_files == $total_success);

   $total_rows = 0;

?>

<div id="bulk-importer">
   <h1>
      <?php echo $this->Data('Title'); ?>
   </h1>

   <div class="Info">

      <?php if ($total_success): ?>

         <p class="P">
            The following <?php echo Plural($total_success, 'file', 'files') . ' ' .  Plural($total_success, 'was', 'were'); ?>
            uploaded successfully:
         </p>

         <ul class="List">

            <?php foreach ($results['success'] as $filename => $rows): ?>

               <?php $total_rows += $rows; ?>

               <li><strong>"<?php echo $filename; ?>"</strong> with <?php echo $rows; ?> rows.</li>

            <?php endforeach; ?>

         </ul>

      <?php endif; ?>

      <?php if ($total_fail): ?>

         <p class="P">
            The following <?php echo Plural($total_fail, 'file', 'files') . ' ' .  Plural($total_fail, 'was', 'were'); ?>
            <em>not</em> uploaded:
         </p>

         <ul class="List">

            <?php foreach ($results['fail'] as $filename => $reason): ?>

               <li><strong>"<?php echo $filename; ?>"</strong>: <?php echo $reason; ?>.</li>

            <?php endforeach; ?>

         </ul>

      <?php endif; ?>


      <?php if (!$total_success && !$total_fail): ?>

         <p class="P">
            No file was included. Return to the previous page and select a file
            from your computer or provide the URL to a file.
         </p>

      <?php endif; ?>

   </div>

   <?php if ($total_success): ?>

      <h3>Process the uploaded data</h3>

      <div class="Info">
         Click the button below to process the uploaded data. All successfully
         processed records will create a new user account, and all successfully
         created users will receive an email with instructions on signing in.
      </div>

      <div class="FilterMenu">
         <p class="P"><label for="bulk_importer_debug" title="Regardless of this setting, users who already exist do not receive an email, as they're just getting updated."><input type="checkbox" id="bulk_importer_debug" /> Do not send an email to newly created users.</label></p>

         <a id="process-csvs" class="SmallButton" href="<?php echo Url('/settings/bulkusersimporter/process'); ?>">Begin processing</a>
         <span id="import-progress-container">
            <i id="progress-animation" class="TinyProgress"></i>
            <span id="import-progress-meter" data-total-rows="<?php echo $total_rows; ?>" data-completed-rows="0">0%</span>
         </span>
      </div>

      <div class="Info">
         <strong id="bulk-error-header"></strong>
         <span id="bulk-error-many-errors"></span>
         <pre id="bulk-error-dump"></pre>
      </div>

   <?php endif; ?>

</div>