jQuery(document).ready(function($) {
   
   $('.PollForm form :submit').live('click', function() {
      var btn = this,
         frm = $(this).parents('form');
         
      var postValues = $(frm).serialize()+'&DeliveryType=VIEW&DeliveryMethod=JSON',
         action = $(frm).attr('action');
         
      $(btn).addClass('InProgress'); // Replace the button with a spinner
      $.ajax({
         type: "POST",
         url: action,
         data: postValues,
         dataType: 'json',
         error: function(xhr) {
            gdn.informError(xhr);
         },
         success: function(json) {
            json = $.postParseJson(json);
            $('.PollForm').replaceWith(json.PollHtml);
            gdn.inform(json);
            return false;
         },
         complete: function(XMLHttpRequest, textStatus) {
            $('.InProgress').removeClass('InProgress'); // just in case
         }
      });
      return false;
   });   
});
