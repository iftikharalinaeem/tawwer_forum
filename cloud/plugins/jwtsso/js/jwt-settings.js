$(document).on('click', '.js-generate', function(e) {
   e.preventDefault();
   var $parent = $(this).closest('form');
   $.ajax({
      type: 'POST',
      url: gdn.url('/settings/jwtsso'),
      data: {
         DeliveryType: 'VIEW',
         DeliveryMethod: 'JSON',
         TransientKey: gdn.definition('TransientKey'),
         Generate: true
      },
      dataType: 'json',
      error: function(xhr) {
         gdn.informError(xhr);
      },
      success: function(json) {
         $('#Form_AssociationSecret', $parent).val(json.AssociationSecret);
         $('#Form_AssociationSecret').focus();
      }
   });
});