jQuery(document).ready(function($) {

   var max_file_size = parseInt(gdn.definition('maxfilesizebytes', 52428800));
   var $file_download_option = $('#bulk-importer-file-download');
   var $file_url_option = $('#bulk-importer-file-url');
   var $feedback_placeholder = $('#bulk-importer-validation-feedback');
   var $submit_button = $('#Form_Start');
   var $import_files = $('#Form_import_files');
   var $import_url = $('#Form_import_url');

   // Validate upload
   $import_files.on('change', function(e) {
      var filename = e.target.value.toLowerCase();
      if (filename != '') {
         var extension = filename.split('.').pop();
         var errors = [];

         // Loop through files, check if too large
         if (Window.File) {
            var files = this.files;
            for (var i = 0, l = files.length; i < l; i++) {
               if (files[i].size > max_file_size) {
                  errors.push('<strong>"' + filename + '"</strong> is too large.');
                  // Break on any error, cancel rest.
                  break;
               }
            }
         }

         // Fade out the URL option if CSV.
         if (extension == 'csv') {
            $file_url_option.addClass('disable-option');
         } else {
            $file_url_option.removeClass('disable-option');
            errors.push('<strong>"' + filename + '"</strong> is not a CSV.');
         }

         // If any errors, loop through and dislay them.
         $feedback_placeholder.html('');
         $feedback_placeholder.removeClass('import-errors');
         $submit_button.removeClass('disable-option');
         $import_url.removeClass('disable-option');

         if (errors.length) {
            var feedback = '';
            for (var i = 0, l = errors.length; i < l; i++) {
               feedback += errors[i] + ' ';
            }
            $feedback_placeholder.html(feedback);
            $feedback_placeholder.addClass('import-errors');

            // Disable submit button.
            $submit_button.addClass('disable-option');
            // Clear any file that might be in the input
            $(this).val('');
         } else {
            // No errors, so disable the text input
            $import_url.addClass('disable-option');
            $import_url.val('');
         }
      }
   });

   // validate URL
   $import_url
      .on('focus', function(e) {
         $feedback_placeholder.html('');
         $feedback_placeholder.removeClass('import-errors');
         $submit_button.removeClass('disable-option');
      })
      .on('blur', function(e) {
         var filename = e.target.value;
         if (filename != '') {
            var extension = filename.split('.').pop();
            var errors = [];

            // Fade out the URL option if CSV.
            if (extension == 'csv') {
               $file_download_option.addClass('disable-option');
            } else {
               $file_download_option.removeClass('disable-option');
               errors.push('<strong>"' + filename + '"</strong> is not a CSV.');
            }

            $feedback_placeholder.html('');
            $feedback_placeholder.removeClass('import-errors');
            $submit_button.removeClass('disable-option');
            $import_files.removeClass('disable-option');
            if (errors.length) {
               var feedback = '';
               for (var i = 0, l = errors.length; i < l; i++) {
                  feedback += errors[i] + ' ';
               }
               $feedback_placeholder.html(feedback);
               $feedback_placeholder.addClass('import-errors');

               // Disable submit button.
               $submit_button.addClass('disable-option');
            } else {
               // No errors, so disable the file input
               $import_files.addClass('disable-option');
               $import_files.val('');
            }
         }
      });

   // Make sure there is something to submit
   $('#bulk-importer-form').on('submit', function(e) {
      if ($import_files.val() == '' && $import_url.val() == '') {
         $feedback_placeholder.html('A file must be included.');
         $feedback_placeholder.addClass('import-errors');
         e.preventDefault();
         e.stopPropagation();
         return false;
      }
   });

   // Running the bulk importer in debug mode. This is actually just for
   // disabling email, so the checkbox will ask about that.
   bulk_importer_debug = 0;
   $('#bulk_importer_debug').on('change', function(e) {
      bulk_importer_debug = parseInt(+e.target.checked);
   });

   // Save original title for progress meter title percentages.
   documentTitle = document.title;

   // To calculate time remaining
   bulk_start_time = 0;
   var bulk_rows_after_job = []; // Collect how many jobs done.
   var bulk_time_after_job = []; // Collect average time per job.

   // Call job every n.
   bulk_importer_errors = 0;
   var incremental_job = function(url) {
      var bulk_job_start = Math.ceil(+new Date / 1000);
      var $progress_meter = $('#import-progress-meter');
      var total_rows = parseInt($progress_meter.attr('data-total-rows'));
      var $progress_container = $('#import-progress-container');
      var $progress_animation = $('#progress-animation');
      var $bulk_error_header = $('#bulk-error-header');
      var $bulk_error_many_errors = $('#bulk-error-many-errors');
      var $bulk_error_dump = $('#bulk-error-dump');
      var progress_fail_message = 'Import could not be completed.';

      // Max errors before importer stops. If there is no ceiling to this, the
      // browser may crash trying to display this many errors. Optionally,
      // consider suppressing the display of error messages after this point,
      // while continuing to process the import silently, but still displaying
      // the progress.
      var max_errors = 10000;

      // Set work in progress
      $progress_container.addClass('working');

      $.post(url, {
         DeliveryMethod: 'JSON',
         DeliveryType: 'View',
         TransientKey: gdn.definition('TransientKey', ''),
         debug: bulk_importer_debug
      }, null, 'json')
      .done(function(data) {
         var bulk_job_end = Math.ceil(+new Date / 1000);
         var rows_completed_job = parseInt(data.import_id);
         var progress = Math.ceil((rows_completed_job / total_rows) * 100);
         if (progress > 100) {
            progress = 100;
         }

         // Calculate average rows processed per job.
         var rows_remaining = total_rows - rows_completed_job;
         bulk_rows_after_job.push(rows_completed_job);
         var average_rows_per_job = Math.ceil(rows_completed_job / bulk_rows_after_job.length);

         // Calculate average time per job.
         var total_elapsed_time = Math.round((bulk_job_end - bulk_start_time) / 60);
         var job_elapsed_time = bulk_job_end - bulk_job_start;
         bulk_time_after_job.push(job_elapsed_time);
         var average_time_per_job = 0;
         for (var i = 0, l = bulk_time_after_job.length; i < l; i++) {
            average_time_per_job += bulk_time_after_job[i];
         }
         average_time_per_job = Math.ceil(average_time_per_job / l);

         // Calculate average time per row, in seconds
         var average_time_per_row = average_time_per_job / average_rows_per_job;

         // Calculate average time remaining in whole import. In minutes.
         var import_time_remaining = Math.round((rows_remaining * average_time_per_row) / 60);

         // TODO consider smarter time handling, to adjust for hours
         // and seconds.
         var time_estimation_string =  '&middot; '+ total_elapsed_time +' minute(s) elapsed';
         if (progress != 100) {
            time_estimation_string += ' &middot; about <strong>'+ import_time_remaining +' minute(s) left</strong>';
         }

         // Insert data for display.
         $progress_meter.attr('data-completed-rows', rows_completed_job);
         var progress_message = '<span title="'+ data.feedback +'">'+ progress + '% processed (' + rows_completed_job + ' rows) ' + time_estimation_string + '</span>';
         $progress_meter.html(progress_message);
         document.title = '('+ progress + '%) · ' + import_time_remaining + ' minute(s) left - ' + documentTitle;

         // If there were errors in the processing, output them.
         if (data.bulk_error_dump) {
            var error_messages = $.parseJSON(data.bulk_error_dump);
            var previous_error_count = bulk_importer_errors;
            bulk_importer_errors = parseInt(bulk_importer_errors + error_messages.length);

            // Always show the number of errors.
            $bulk_error_header.html('Errors (' + bulk_importer_errors + ')');

            // If there were more than max_errors, stop outputting the specific
            // errors, as there can be tens of thousands, so cap it, while
            // continuing to show import progress. Need to check against
            // previous error count so the latest dump of errors can be
            // displayed, in the likely chance that this pushes the error
            // count above the max, and suppresses entirely the latest dump.
            if (previous_error_count <= max_errors) {
               for (var i = 0, l = error_messages.length; i < l; i++) {
                  $bulk_error_dump.append(error_messages[i] +'\n');
               }
            }
         }

         // Let user know that after n number of errors, any future errors
         // will be suppressed, but the error count will continue to keep track.
         if (bulk_importer_errors >= max_errors) {
            $bulk_error_many_errors.html('The importer has reached its reporting cap of <strong>'+ max_errors + ' errors</strong>. The importer will continue to import users, and the error count will continue to record, but the display of any other error messages will be suppressed from this moment. This limit has been placed so that browsers do not become unstable if there happen to be a very large number of errors. Odds are the errors listed below are duplicated in the remaining data set, and are the likely cause of any further errors counted.');
         }

         // If import_id is 0, then there was no role.
         if (rows_completed_job == 0) {
            cancel_import = true;
            progress_fail_message = data.error_message;
         }

         // If done, call again and continue the process.
         if (rows_completed_job != total_rows) {
            if (!cancel_import) {
               incremental_job(url);
            }
         } else if (rows_completed_job == total_rows) {
            $progress_animation.addClass('removed');
         }
      })
      .fail(function(data) {
         cancel_import = true;
          if (cancel_import) {
            progress_fail_message = 'Internal error processing the file.';
         }
      })
      .always(function(data) {
         if (cancel_import) {
            $progress_meter.html(progress_fail_message);
            $progress_animation.addClass('removed');
         }
      });
   };

   // When processing the CSV in increments
   $('#process-csvs').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      // Disable button and checkbox after click
      $(this).addClass('disable-option');
      $('#bulk-importer-checkbox-email').addClass('disable-option');
      cancel_import = false;
      incremental_job(e.target.href);
      bulk_start_time = Math.ceil(+new Date / 1000);
   });

});