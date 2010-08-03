jQuery(document).ready(function($) {
   // Hide/show the appropriate repeat options.
   var revealRepeatOptions = function() {
      // Get the current value of the repeat options.
      var selected = $("input[name=Pocket/RepeatType]:checked").val();
      switch (selected) {
         case 'every':
            $('.RepeatEveryOptions').show();
            $('.RepeatIndexesOptions').hide();
            break;
         case 'index':
            $('.RepeatEveryOptions').hide();
            $('.RepeatIndexesOptions').show();
            break;
         default:
            $('.RepeatEveryOptions').hide();
            $('.RepeatIndexesOptions').hide();
            break;
      }
   };

   $("input[name=Pocket/RepeatType]").click(revealRepeatOptions);

   revealRepeatOptions();
});
