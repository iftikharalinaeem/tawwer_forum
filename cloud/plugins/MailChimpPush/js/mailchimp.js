$(document).on('contentLoad', function(e) {

   // Don't show until we're ready.
   $('.Synchronization').hide();
   $('#SynchronizationMessages').hide();

   /**
    * InterestDropdowns are select elements of "interests" that have been
    * created created on MailChimp. Each list of interests is associated with a list.
    * When the list is chosen, show the interests select and hide and disable
    * any other interest selects.
    */
   var showInterestOptions = function () {
      var activeList = $('#Form_ListID').val();
      $(".InterestDropdowns select").prop('disabled', true);
      $(".InterestDropdowns").addClass("Hidden");
      $("#Form_InterestID"+activeList).prop('disabled', false);
      $("#InterestDropdown"+activeList).removeClass("Hidden");
   }

   /**
    * Show or hide interests select boxes to be to assign interests users being added
    * to MailChimp in bulk.
    */
   var showSyncInterestOptions = function () {
      var activeList = $('#Form_SyncListID').val();
      $(".SyncInterestDropdowns select").prop('disabled', true);
      $(".SyncInterestDropdowns").addClass("Hidden");
      $("#Form_SyncInterestID"+activeList).prop('disabled', false);
      $("#SyncInterestDropdown"+activeList).removeClass("Hidden");
   }

   // On load, show the active interest select.
   showInterestOptions();
   showSyncInterestOptions();

   // When changing selection of list as the default list for users signing up, present interest choices.
   $('#Form_ListID').on('change', this, function(e){
      showInterestOptions();
   });

   // When changing selection of list where users will be synchronized to on MailChimp, present the interest choices.
   $('#Form_SyncListID').on('change', this, function(e){
      showSyncInterestOptions();
   });

   $('.MailChimpSync').on('click', '#MailChimp-Synchronize', function(e) {
      $('#SynchronizationMessages').hide();
      // Start processing users
      process(0);
   });

   /**
    * Reveal progress bar
    */
   var progressBar = function() {
      $('.Synchronization').show();
      $('.Synchronization').removeClass('Finished');
   }

   /**
    * Update progress bar
    *
    * @param {object} data - Data object returned from ajax call
    * @returns {bool}
    */
   var progress = function(data) {
      progressBar();

      var auto = true;
      var rerun = false;
      var reseterrors = true;
      // Clear mode
      if (data == undefined) {
         data = {
            Progress: $('.Synchronization .SyncProgress').data('progress'),
            Offset: null
         }
         if (isNaN(data.Progress))
            data.Progress = 0;

         auto = false;
      }

      var isError = data.hasOwnProperty('Error');
      var isError = (isError || isNaN(data.Progress));

      if (isError) {
         var error = data.hasOwnProperty('Error') ? data.Error : data.Progress;
         var fatal = data.hasOwnProperty('Fatal') ? data.Fatal : false;

         $('.Synchronization').addClass('alert-danger');
         $('.Synchronization .SyncProgress').css('width', '100%');
         $('.Synchronization .SyncProgress').html(error);

         var cErrors = $('.Synchronization .SyncProgress').data('errors');
         if (isNaN(cErrors)) cErrors = 0;
         cErrors++;
         $('.Synchronization .SyncProgress').data('errors', cErrors);

         if (cErrors > 3)
            rerun = false;
         else
            rerun = true;

         if (fatal)
            rerun = false;

      } else {

         $('.Synchronization .SyncProgress').data('progress', data.Progress);

         if (!isNaN(data.Offset) && data.Offset != null)
            $('.Synchronization .SyncProgress').data('offset', data.Offset);

         // Upgrade progress bar width
         $('.Synchronization .SyncProgress').css('width', data.Progress+'%');
         $('.Synchronization .SyncProgress').html('<span>'+data.Progress+'%</span>');
         $('.Synchronization').removeClass('alert-danger');

         // Check for rerun
         if (auto) {
            if (data.Progress <= 100) {
               rerun = true;
            } else {
               finish(true);
            }

            // Reset errors on success
            if (reseterrors)
               $('.Synchronization .SyncProgress').data('errors', 0);
         }
      }


      // Schedule rerun
      if (rerun && auto) {
         var waitTime = isError ? 5000 : 50;
         setTimeout(process, waitTime);
      }

      if (!rerun && isError)
         finish(false);
   }

   /**
    * Send data to MailChimp in batches, track the progress.
    */
   var process = function() {
      $('.MailChimpSync #MailChimp-Synchronize').prop('disabled', 'disabled');

      // Start at 0
      var offset = $('.Synchronization .SyncProgress').data('offset') || 0;
      var url = $('#Form_SyncURL').val();
      var syncListID = $('.MailChimpSync #Form_SyncListID').val();
      var syncConfirmJoin = $('.MailChimpSync #Form_SyncConfirmJoin').prop('checked') ? 1 : 0;
      var syncBanned = $('.MailChimpSync #Form_SyncBanned').prop('checked') ? 1 : 0;
      var syncDeleted = $('.MailChimpSync #Form_SyncDeleted').prop('checked') ? 1 : 0;
      var syncUnconfirmed = $('.MailChimpSync #Form_SyncUnconfirmed').prop('checked') ? 1 : 0;
      var syncInterestID = $('.MailChimpSync #Form_SyncInterestID'+syncListID).val();

      var send = {
            Postback: true,
            TransientKey: gdn.definition('TransientKey', ''),
            Offset: offset,
            SyncListID: syncListID,
            SyncConfirmJoin: syncConfirmJoin,
            SyncBanned: syncBanned,
            SyncDeleted: syncDeleted,
            SyncInterestID: syncInterestID
         };

      if (syncUnconfirmed != undefined) {
         send.SyncUnconfirmed = syncUnconfirmed;
      }

      // AJAX
      $.ajax({
         url: url,
         type: 'POST',
         data: send,
         success: function(data) {
            var syncprogress = data.Progress;
            var syncoffset = data.Offset;
            var status = data.Status;
            // Some kind of error
            if (syncprogress == undefined) {
               if (!data.Error) {
                  data = {
                     Error: 'Sync error',
                     Fatal: false
                  };
               }
            } else if (isNaN(syncprogress)) {
                  data.Error = syncprogress;
            }

            progress(data);
         },
         error: function(xhr) {
            var data = {
               Error: 'XHR error',
               Fatal: true
            };
            progress(data);
         }
      });

   };

   /**
    * Once the batches have been transfered show success status.
    * @param {object} success - Success object returned from ajax call.
    */
   var finish = function(success) {
      $('.Synchronization').addClass('Finished');
      if (success) {
         $('.Synchronization .SyncProgress').css('width', '100%');
         $('.Synchronization').removeClass('alert-danger');
         $('.Synchronization .SyncProgress').html('Completed');
         setTimeout(reset, 3000);
      }
      $('.MailChimpSync #MailChimp-Synchronize').prop('disabled', '');
   }

   /**
    * Remove the progress bar and add a warning message that informs users that the batch process has been uploaded but
    * has not necessarily been processed on MailChimp
    */
   var reset = function() {
      $('.Synchronization').hide();
      var message = 'Success! MailChimp will now process the list you have uploaded. Check your MailChimp Dashboard later.';
      var successMessage = gdn.getMeta('MailChimpUploadSuccessMessage', message);
      $('#SynchronizationMessages').show().html(successMessage);
   }
});
