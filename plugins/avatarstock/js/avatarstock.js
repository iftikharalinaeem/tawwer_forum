$(document).on('contentLoad', function(e) {
   var element = e.target;

   $(".js-new-avatar-pool", element).click(function () {
      $(".js-new-avatar-pool-upload").trigger("click");
      $(".js-new-avatar-pool-upload").change(function() {
         var $form = $(this).closest('form');
         $form.submit();
      });
   });

   // Showing the delete selected avatars button.
   var $avatar_delete_input = $('.avatar-delete-input', element);
   $avatar_delete_input.on('change', function(e) {
      var total_checked = 0;
      $avatar_delete_input.each(function(i, el) {
         if ($(el).prop('checked')) {
            total_checked++;
         }
      });

      var $delete_selected_avatars = $('.delete-selected-avatars', element);
      if (total_checked) {
         $delete_selected_avatars.addClass('show-delete-button');
      } else {
         $delete_selected_avatars.removeClass('show-delete-button');
      }
   });

   // Submit the delete selected avatars form.
   var $delete_selected_avatars = $('.delete-selected-avatars', element);
   $delete_selected_avatars.on('click', function(e) {
      // Get form--currently only one for this.
       var $avatarstock_form_modify = $('#avatarstock-form-modify', element);
       $avatarstock_form_modify.submit();
   });

});
