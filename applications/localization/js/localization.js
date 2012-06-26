jQuery(document).ready(function($) {
   
   var setForm = function ($tr, nosubmit) {
      if (!nosubmit)
         $('#TranslationForm').submit();
      
      var codeID = $tr.attr('codeid');
      var english = $('.EnglishTranslation', $tr).html();
      var translation = $('.Translation', $tr).text();
      
      $('#TranslationsTable tr').removeClass('Selected');
      $tr.addClass('Selected');
      
      $('#Form_CodeID').val(codeID);
      $('#EnglishTranslation').html(english);
      $('#Form_Translation').val(translation);
      $('#Form_TranslationBak').val(translation);
      if (nosubmit) {
         $('#Form_Translation').focus();
      }
   };

   var refreshTable = function() {
      $.get(gdn.url('/localization/table'),
         { locale: gdn.definition('Locale'), DeliveryType: 'VIEW', filter: gdn.definition('Filter', '') },
         function (data) {
            $('#TranslationsTable').html(data);
            setForm($('#TranslationsTable tbody tr:first-child'), true);
         });
   }
   
   $('#TranslationForm').ajaxForm({
      beforeSubmit: function(arr, $form, options) {
         // Consolidate the form.
         var data = {};
         for(var i in arr) {
            var row = arr[i];
            data[row['name']] = row['value'];
         }
         
         var $lastTr = null;
         if (data['CodeID']) {
            $lastTr = $('#CodeID_'+data['CodeID']);
            if ($lastTr.length == 0)
               $lastTr = null;
         }
         
         var next = true;
         if (data['Previous'])
            next = false;
         
         var $newTr = null;
         if (next) {
            if (!$lastTr) {
               $newTr = $('#TranslationsTable tbody tr').first();
            } else {
               $newTr = $lastTr.next();
               
               if ($newTr.length == 0) {
                  refreshTable();
               }
            }
         } else if (data['Previous']) {
            if ($lastTr != null)
               $newTr = $lastTr.prev();
         }
         
         if ($newTr) {
            setForm($newTr, true);
         }
         
         if (!data['CodeID']) {
            return false; // no data to save.
         }
         if (!data['Approve'] && !data['Reject']) {
            if (data['Translation'] == data['TranslationBak'])
               return false; // no change.
         }
         
         if ($lastTr)
            $('.TranslationColumn', $lastTr).addClass('InProgress');
         
         lastData = data;
         
      },
      success: function(data) {
         if (typeof(data) != 'object')
            return;
         
         var t = data['Translation'];
         
         if (!t['Translation'])
            t['Translation'] = '';
         
         // Set the translation of the row.
         var codeID = t['CodeID'];
         var $lastTr = $('#CodeID_'+codeID);
         $('.Translation', $lastTr).text(t['Translation']);
         
         var approved = t['Approved'];
         $('.Approved-Icon', $lastTr)
            .removeClass('Approved-New Approved-Translated Approved-Approved Approved-Rejected')
            .addClass('Approved-'+approved);
         
         if ($lastTr)
            $('.TranslationColumn', $lastTr).removeClass('InProgress');
      }
   });

   // Select the first item.
   setForm($('#TranslationsTable tbody tr:first-child'), true);
   $(document).delegate('#TranslationsTable tbody tr', 'click', function() {
      setForm($(this));
   });
});