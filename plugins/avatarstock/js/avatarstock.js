jQuery(document).ready(function($) {

   // Upload new photos without needing to press anything.
   var $avatar_upload = $('.avatar-upload-input');
   $avatar_upload.on('change', function(e) {
      var filename = e.target.value.toLowerCase();
      if (filename != '') {
         var extension = filename.split('.').pop();
      }

      var $form = $(this).closest('form');
      $form.submit();
   });

   // Showing the delete selected avatars button.
   var $avatar_delete_input = $('.avatar-delete-input');
   $avatar_delete_input.on('change', function(e) {
      var total_checked = 0;
      $avatar_delete_input.each(function(i, el) {
         if ($(el).prop('checked')) {
            total_checked++;
         }
      });

      var $delete_selected_avatars = $('.delete-selected-avatars');
      if (total_checked) {
         $delete_selected_avatars.addClass('show-delete-button');
      } else {
         $delete_selected_avatars.removeClass('show-delete-button');
      }
   });

   // Submit the delete selected avatars form.
   var $delete_selected_avatars = $('.delete-selected-avatars');
   $delete_selected_avatars.on('click', function(e) {
      // Get form--currently only one for this.
       var $avatarstock_form_modify = $('#avatarstock-form-modify');
       $avatarstock_form_modify.submit();
   });

   // When selecting them in the edit profile pages.
   var $avatar_picker = $('.avatar-option input');
   $avatar_picker.on('change', function(e) {
      $avatar_picker.each(function(i, el) {
         if ($(el).prop('checked')) {
            $avatar_picker.each(function(i, el2) {
               $(el2).closest('label').removeClass('current-stock-avatar');
            });
            $(el).closest('label').addClass('current-stock-avatar');
         }
      });
   });
});