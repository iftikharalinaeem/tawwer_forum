jQuery(document).ready(function($) {
   $('.DateBox').datepicker().each(function() {
      var dateVal = $(this).val();
      if (dateVal.length == 0)
         return;
      var d = $.datepicker.parseDate('yy-mm-dd', dateVal);
      $(this).datepicker("setDate", d);
   });


   var dateFormat = new Date(2000, 11, 31);
   $('#Form_DateFormat').datepicker("setDate", dateFormat);

   $('#Form_Company').autocomplete(
      gdn.url('/dashboard/user/autocomplete/'),
      {
         minChars: 1,
         multiple: false,
         scrollHeight: 220,
         selectFirst: true
      }
   );
});