jQuery(document).ready(function($) {
   
   // Handle Vote button clicks   
   $('.Votes a').live('click', function() {
      if (!$(this).hasClass('SignInPopup')) {
         var btn = this;
         var parent = $(this).parents('.Votes');
         var votes = $(parent).find('span');
         $.ajax({
            type: "POST",
            url: btn.href,
            data: 'DeliveryType=BOOL&DeliveryMethod=JSON',
            dataType: 'json',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
               // Popup the error
               $(btn).attr('class', oldClass);
               $.popup({}, definition('TransportError').replace('%s', textStatus));
            },
            success: function(json) {
               // Change the Vote count
               $(votes).text(json.TotalScore);
            }
         });
         return false;
      }
   });

});
