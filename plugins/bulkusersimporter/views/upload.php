<?php if (!defined('APPLICATION')) exit(); ?>

<?php

   $results = $this->Data('results');

   $total_success = count($results['success']);
   $total_fail = count($results['fail']);
   $total_files = $total_success + $total_fail;
   $great_success = ($total_files == $total_success);

?>

<div id="bulk-importer">
   <h1>
      <?php echo $this->Data('Title'); ?>
   </h1>

   <div class="Info">

      <p class="P">
         The following <?php echo Plural($total_success, 'file', 'files') . ' ' .  Plural($total_success, 'was', 'were'); ?>
         uploaded successfully:
      </p>

      <ul class="List">

         <?php foreach ($results['success'] as $filename => $rows): ?>

            <li><strong>"<?php echo $filename; ?>"</strong> with <?php echo $rows; ?> rows.</li>

         <?php endforeach; ?>

      </ul>

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

   </div>

   <h3>Process the uploaded data</h3>

   <div class="Info">
      Click the button below to process the uploaded data. All successfully
      processed records will create a new user account.
   </div>

   <div class="FilterMenu">
      <a id="process-csvs" class="SmallButton Hijack" href="<?php echo Url('/settings/bulkusersimporter/process'); ?>">Begin processing</a>
   </div>
</div>