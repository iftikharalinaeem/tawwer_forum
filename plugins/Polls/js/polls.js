jQuery(document).ready(function($) {

    // Composing a new poll, let poll options duplicate. This used to be inline,
    // which generated a race condition between jQuery being defined, and this
    // code running.
    if ($.fn.duplicate) {
        $('.PollOption').duplicate({
            addButton: '.AddPollOption'
        });
    }

   $(document).on('click', '.PollForm form :submit', function() {
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

    $(".js-poll-result-btn").click(function() {

        if ($(".js-poll-results").css('display') == 'none') {
            $(".js-poll-form").hide();
            $(".js-poll-results").show();
        } else {
            $(".js-poll-form").show();
            $(".js-poll-results").hide();
        }
        return false;
    });


});
