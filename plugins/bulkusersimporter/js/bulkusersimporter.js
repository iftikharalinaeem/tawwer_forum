jQuery(document).ready(function($) {

   var max_file_size = parseInt(gdn.definition('maxfilesizebytes', 52428800));
   var threads = parseInt(gdn.definition('threads', 5));
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
   var bulk_importer_debug = 0;
   $('#bulk_importer_debug').on('change', function(e) {
      bulk_importer_debug = parseInt(+e.target.checked);
   });

   // Handle radio options for invite/insert
   var $bulk_radio_input = $('#bulk-radio-options input[name=userin]');
   var bulk_radio_userin = $bulk_radio_input.filter(':checked').val();
   var display_expires_input = function(userin) {
      $bulk_expires = $('#bulk-expires');
      $bulk_expires.removeClass('show');
      if (userin == 'invite') {
         $bulk_expires.addClass('show');
         $bulk_expires.find('input').focus();
      }
   };
   display_expires_input(bulk_radio_userin);
   $bulk_radio_input.on('change', function(e) {
      bulk_radio_userin = $(e.target).val();
      display_expires_input(bulk_radio_userin);
   });

   // Save original title for progress meter title percentages.
   var documentTitle = document.title;

   // To calculate time remaining
   var bulk_start_time = 0;
   var bulk_rows_after_job = []; // Collect how many jobs done.
   var bulk_time_after_job = []; // Collect average time per job.

   // Keep track of total rows processed.
   var total_rows_processed = 0;

   // Call job every n.
   var bulk_importer_errors = 0;
   var incremental_job = function(url, thread_id) {
      var bulk_job_start = Math.ceil(+new Date / 1000);
      var $progress_meter = $('#import-progress-meter');
      var total_rows = parseInt($progress_meter.attr('data-total-rows'));
      var $progress_container = $('#import-progress-container');
      var $progress_animation = $('#progress-animation');
      var $bulk_error_header = $('#bulk-error-header');
      var $bulk_error_many_errors = $('#bulk-error-many-errors');
      var $bulk_error_dump = $('#bulk-error-dump');
      var progress_fail_message = 'Import could not be completed.';

      if (typeof thread_id == 'undefined') {
        thread_id = '';
      }

      // Get expires for invitation mode
      var bulk_invite_expires = '';
      var bulk_invite_expires_value = $('#bulk-expires').find('input').val().trim();
      if (bulk_radio_userin == 'invite' && bulk_invite_expires_value != '') {
         bulk_invite_expires = bulk_invite_expires_value;
      }

      // Max errors before importer suppresses any further error messages.
      // If there is no ceiling to this, the browser may crash trying to
      // display this many errors.
      var max_errors = 10000;

      // Set work in progress
      $progress_container.addClass('working');

      $.post(url, {
         DeliveryMethod: 'JSON',
         DeliveryType: 'View',
         TransientKey: gdn.definition('TransientKey', ''),
         debug: bulk_importer_debug,
         userin: bulk_radio_userin,
         expires: bulk_invite_expires,
         thread_id: thread_id
      }, null, 'json')
      .done(function(data) {

         // If expiry provided, but not parseable.
         if (data.bad_expires) {
            cancel_import = true;
         }

         var bulk_job_end = Math.ceil(+new Date / 1000);
         //var first_import_id = parseInt(data.first_import_id);

         // Get number of rows processed in the last job. If it's empty,
         // due to the import being complete, parsing the empty value will
         // return NaN. Check if it's NaN, and if true, assign it the total_rows
         // so that the progress and all other calculations work with an
         // actual number, and then quits.
         var job_rows_processed = parseInt(data.job_rows_processed);
         if (isNaN(job_rows_processed)) {
            job_rows_processed = 0;
         } else {
            total_rows_processed += job_rows_processed;
         }

         // If total rows processed is 0, then skip all this stuff, especially
         // if threading is enabled.
         if (job_rows_processed) {

            var progress = Math.ceil((total_rows_processed / total_rows) * 100);
            if (progress > 100 || isNaN(progress)) {
               progress = 100;
            }

            // Calculate average rows processed per job.
            var rows_remaining = total_rows - total_rows_processed;
            bulk_rows_after_job.push(total_rows_processed);
            var average_rows_per_job = Math.ceil(total_rows_processed / bulk_rows_after_job.length);

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
            if (isNaN(import_time_remaining)) {
               import_time_remaining = 0;
            }

            // TODO consider smarter time handling, to adjust for hours
            // and seconds.
            var time_estimation_string =  '&middot; '+ total_elapsed_time +' minute(s) elapsed';
            var new_page_title = '('+ progress + '%) ' + documentTitle;
            if (progress != 100) {
               time_estimation_string += ' &middot; about <strong>'+ import_time_remaining +' minute(s) left</strong>';
               new_page_title = '('+ progress + '%) · ' + import_time_remaining + ' minute(s) left - ' + documentTitle;
            }

            // Insert data for display.
            $progress_meter.attr('data-completed-rows', total_rows_processed);
            var progress_message = '<span>'+ progress + '% processed (' + total_rows_processed + ' rows) ' + time_estimation_string + '</span>';
            $progress_meter.html(progress_message);
            document.title = new_page_title;

            // If there were errors in the processing, output them.
            if (data.bulk_error_dump) {
               var error_messages = $.parseJSON(data.bulk_error_dump);

               if (error_messages && error_messages.length > 0) {
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
            }

            // Let user know that after n number of errors, any future errors
            // will be suppressed, but the error count will continue to keep track.
            if (bulk_importer_errors >= max_errors) {
               $bulk_error_many_errors.html('The importer has reached its reporting cap of <strong>'+ max_errors + ' errors</strong>. The importer will continue to import users, and the error count will continue to record, but the display of any other error messages will be suppressed from this moment. This limit has been placed so that browsers do not become unstable if there happen to be a very large number of errors. Odds are the errors listed below are duplicated in the remaining data set, and are the likely cause of any further errors counted.');
            }

         }

         // If done, call again and continue the process.
         if (total_rows_processed != total_rows) {
            // If import has not been cancelled, or if the last job had some rows
            // processed, send out another job. If the last thread_id returned
            // 0 processed rows, any further requests from this thread can
            // be stopped, as there no more left for that thread.
            if (!cancel_import && job_rows_processed) {
               incremental_job(url, thread_id);
            }
         } else if (total_rows_processed == total_rows || progress == 100) {
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
      $('#bulk-radio-options').addClass('disable-option');

      bulk_start_time = Math.ceil(+new Date / 1000);

      cancel_import = false;
      for (var thread_id = 1; thread_id <= threads; thread_id++) {
         incremental_job(e.target.href, thread_id);
      }
   });

});