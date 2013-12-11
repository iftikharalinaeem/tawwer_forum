(function($) {
   $.fn.readLess = function() {
      // Grab value defined in settings dashboard
      var max_height = parseInt(gdn.definition('readlessMaxHeight', 200));
      
      this.each(function(i, el) {
         var message = $(el);
         var message_height = parseInt($(el).css('height'));
         
         if (message_height > max_height) {
            message.addClass('readless').css({
               "height": max_height
            });
            
            message.after('<a class="readmore" data-height="'+ message_height +'">Read More</a>');
            
            $('.readmore').off('click').on('click', function(e) {
               var message_height_original = $(e.target).data('height');
               var expand_message = $(e.target).parent().find('.readless');
               
               $(expand_message).css({
                  "height": message_height_original
               });
               
               // Remove "Read More" button
               $(e.target).remove();
               setTimeout(function() {
                  expand_message.removeClass('readless');
               }, 400);
            });
         }
      });
      
      // jQuery chaining
      return this;
   };
}(jQuery));

// Set all .BodyBox elements as editor, calling plugin above.
jQuery(document).ready(function($) {
   // For now, restrict message truncating to discussions.
   if ($('body').hasClass('Section-Discussion')) {
      $('.Message').readLess();
   }
});