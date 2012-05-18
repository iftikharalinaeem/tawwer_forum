jQuery(document).ready(function(){
   if (!gdn.definition('StartCleanSpam'))
      return;
   
   var pingClean = function (type) {
      var $row = $('#Form_Type_'+type).closest('div');
      
      // Add the progress bar.
      if ($('.TinyProgress', $row).length == 0)
         $('.Count', $row).after('<span class="TinyProgress"> </span>');
      
      $.ajax({
         url: gdn.url('/log/cleanspamtick.json?type='+type),
         type: 'POST',
         data: {One: true},
         success: function(data) {
            $('.CountSpam', $row).html(parseInt($('.CountSpam', $row).text()) + data.CountSpam);
            $('.CountAll', $row).html(parseInt($('.CountAll', $row).text()) + data.Count);
            if (!data.Complete) {
               pingClean(type);
            } else {
               $('.TinyProgress', $row).remove();
            }
         },
         error: function(xhr) {
            gdn.informError(xhr);
         }
      });
   }
   
   $('.TypeList input:checked').each(function() {
      var type = $(this).val();
      pingClean(type);
   });
});