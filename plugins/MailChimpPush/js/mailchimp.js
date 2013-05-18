jQuery(document).ready(function($) {
   
   
   $('.MailChimpSync').on('click', '#MailChimp-Synchronize', function(e){
      
      // Start processing users
      process(0);
      
   });
   
   /**
    * Reveal progress bar
    * 
    * @returns {undefined}
    */
   var progressBar = function() {
      $('.Synchronization').css('display', 'block');
      $('.Synchronization').removeClass('Finished');
   }
   
   /**
    * Update progress bar
    * 
    * @param {type} syncprogress
    * @returns {undefined}
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
         
         $('.Synchronization').addClass('Error');
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
         $('.Synchronization').removeClass('Error');
         
         if (data.hasOwnProperty('ETAMinutes'))
            $('.Synchronization .SyncProgressTitle span').html(data.ETAMinutes+' minutes left');
         
         // Check for rerun
         if (auto) {
            if (data.Progress < 100)
               rerun = true;
            else
               finish(true);
         
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
   
   var process = function() {
      $('.MailChimpSync #MailChimp-Synchronize').prop('disabled', 'disabled');
      
      // Start at 0
      var offset = $('.Synchronization .SyncProgress').data('offset');
      var url = '/plugin/mailchimp/sync';
      var syncListID = $('.MailChimpSync #Form_SyncListID').val();
      var syncConfirmJoin = $('.MailChimpSync #Form_SyncConfirmJoin').prop('checked') ? 1 : 0;
      var syncBanned = $('.MailChimpSync #Form_SyncBanned').prop('checked') ? 1 : 0;
      var syncDeleted = $('.MailChimpSync #Form_SyncDeleted').prop('checked') ? 1 : 0;
      var syncUnconfirmed = $('.MailChimpSync #Form_SyncUnconfirmed').prop('checked') ? 1 : 0;
      
      var send = {
            Postback: true, 
            TransientKey: gdn.definition('TransientKey', ''),
            Offset: offset,
            SyncListID: syncListID,
            SyncConfirmJoin: syncConfirmJoin,
            SyncBanned: syncBanned,
            SyncDeleted: syncDeleted
         };
         
      if (syncUnconfirmed != undefined)
         send.SyncUnconfirmed = syncUnconfirmed;
      
      // AJAX
      progress();
      $.ajax({
         url: url,
         type: 'POST',
         data: send,
         success: function(data) {
            var syncprogress = data.Progress;
            var syncoffset = data.Offset;
            
            // Some kind of error
            if (syncprogress == undefined) {
               if (!data.Error) {
                  data = {
                     Error: 'Sync error',
                     Fatal: false
                  };
               }
            } else {
               if (isNaN(syncprogress))
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
   
   var finish = function(success) {
      $('.Synchronization').addClass('Finished');
      if (success) {
         $('.Synchronization .SyncProgress').css('width', '100%');
         $('.Synchronization').removeClass('Error');
         $('.Synchronization .SyncProgress').html("Completed");
      } 
      
      $('.MailChimpSync #MailChimp-Synchronize').prop('disabled', '');
   }
   
});