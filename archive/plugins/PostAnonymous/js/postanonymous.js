// Enable multicomplete on selected inputs.
jQuery(document).ready(function($) {
   var setAnonymousForm = function() {
      var categoryID = $('#Form_CategoryID').val();
      var ids = gdn.definition('AnonymousCategoryIDs', '').split(',');
      var visible = false;

      for (var i = 0; i < ids.length; i++) {
         if (ids[i] == categoryID) {
            visible = true;
            break;
         }
      }

      if (visible)
         $('.PostAnonymous-Form').show();
      else
         $('.PostAnonymous-Form').hide();
   };

   setAnonymousForm();

   $('#Form_CategoryID').change(setAnonymousForm);
   /*
   $(document).bind('CommentEditingComplete', function() {
      setTimeout(setAnonymousForm, 300);
   });
   */
});