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


   var incremental_job = function(url) {
      var $progress_meter = $('#import-progress-meter');
      var total_rows = parseInt($progress_meter.attr('data-total-rows'));
      var $progress_container = $('#import-progress-container');
      var $progress_animation = $('#progress-animation');
      var progress_fail_message = 'Import could not be completed.';

      // Set work in progress
      $progress_container.addClass('working');

      $.post(url, {
         DeliveryMethod: 'JSON',
         DeliveryType: 'View',
         TransientKey: gdn.definition('TransientKey', '')
      }, null, 'json')
      .done(function(data) {
         var rows_completed_job = parseInt(data.import_id);
         var progress = Math.ceil((rows_completed_job / total_rows) * 100);
         if (progress > 100) {
            progress = 100;
         }
         $progress_meter.attr('data-completed-rows', rows_completed_job);
         var progress_message = '<span title="'+ data.feedback +'">'+ progress + '% processed</span>'
         $progress_meter.html(progress_message);

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
      // Disable button after click
      $(this).addClass('disable-option');
      cancel_import = false;
      incremental_job(e.target.href);
   });

});