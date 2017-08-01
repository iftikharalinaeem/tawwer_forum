<?php if (!defined('APPLICATION')) exit(); ?>

<?php

   $results = $this->data('results');

   $total_success = count($results['success']);
   $total_fail = count($results['fail']);
   $total_files = $total_success + $total_fail;
   $great_success = ($total_files == $total_success);
   $total_rows = 0;

   // Get total records (for all files).
   $total_records = 0;
   foreach ($results['success'] as $file => $records) {
      $total_records += $records;
   }

   // Default expiry date for invitations
   $default_invite_expiration = c('Garden.Registration.InviteExpiration', '1 week');

   // Available invites
   $available_invites = $this->data('_available_invites');

   // Generate proper notice.
   $bulk_invite_notice = 'Invites available: ';
   if ($available_invites == -1) {
      $bulk_invite_notice .= '<strong>unlimited</strong>.';
      $enough_invites = true;
   } else {
      $bulk_invite_notice .= '<strong>' . $available_invites . '</strong>.';
   }

   // If total records are less than available invites, let them know.
   if ($available_invites != -1
   && $total_records > $available_invites) {
      $enough_invites = false;
      $bulk_invite_notice .= " You do not have enough invites to send to every user in the import. It is not recommended you choose the invitation import mode. If you are an administrator, you can set the invitation limit yourself.";
   } else {
      $enough_invites = true;
   }

?>

<div id="bulk-importer">
   <h1>
      <?php echo $this->data('Title'); ?>
   </h1>

   <div class="Info">

      <?php if ($total_success): ?>

         <p class="P">
            The following <?php echo plural($total_success, 'file', 'files') . ' ' .  plural($total_success, 'was', 'were'); ?>
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
            The following <?php echo plural($total_fail, 'file', 'files') . ' ' .  plural($total_fail, 'was', 'were'); ?>
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

      <div class="Info bulk-invite-notice">
         <?php echo $bulk_invite_notice; ?>
      </div>

      <div class="Info">

         <p class="P">
            What do you want to do with the user import data?
            <ul id="bulk-radio-options">
               <li><label><input type="radio" name="userin" id="bulk-invite" value="invite" <?php if ($enough_invites): echo 'checked="checked"'; endif; ?> /> Invite new users <span class="bulk-note">(new users will be emailed with a link to register their username and other account information)</span></label></li>
               <li id="bulk-expires" class="shlide" title="Regular English like 'tomorrow', '5 days', 'next week', '2 weeks', or a date like YYYY/MM/DD"><label>Expires: <input type="text" name="expires" value="<?php echo $default_invite_expiration; ?>" placeholder="Examples: tomorrow, 5 days, next week, 2 weeks, YYYY/MM/DD" /></label> <span class="bulk-note">If no expiry specified, the invite will expire in <?php echo $default_invite_expiration; ?>.</span></li>
               <li><label><input type="radio" name="userin" id="bulk-insert" value="insert" <?php if (!$enough_invites): echo 'checked="checked"'; endif; ?> /> Insert new users <span class="bulk-note">(new user accounts will be created immediately; an email will be sent out to those users with instructions on logging in)</span></label></li>
               <li><label><input type="radio" name="userin" id="bulk-insert" value="update" /> Update current users <span class="bulk-note">(based on email, which must already exist in database)</span></label></li>
            </ul>
         </p>

         <p class="P">
            <label id="bulk-importer-checkbox-email" for="bulk_importer_debug" title="Regardless of this setting, users who already exist do not receive an email, as they're just getting updated.">
               <input type="checkbox" id="bulk_importer_debug" /> Do not send an email to newly created users <span class="bulk-note">(this is mostly for debugging purposes)</span>
            </label>
         </p>

         <a id="process-csvs" class="SmallButton" href="<?php echo url('/settings/bulkusersimporter/process'); ?>">Begin processing</a>
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