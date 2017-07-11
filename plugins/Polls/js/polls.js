jQuery(document).ready(function($) {

    $('.NewPollForm').each(function(){
        var $form = $(this);
        var $template = $form.find('.PollOption:first').clone(false);
        $template.find('.InputBox').val(''); // Reset value just in case it's not empty
        var $pollOptions = $form.find('.PollOptions');

        $form.find('.AddPollOption').on('click', function(e){
            e.stopPropagation();
            e.preventDefault();
            $pollOptions.append($template.clone());
            $pollOptions.find('.PollOption:last .InputBox').focus();
        });

        // Block new lines if current line is empty
        $form.on('keypress', '.InputBox', function(e){
            var isEnterKey = (e.keyCode || e.which) == 13;
            if (isEnterKey && $(this).val() == "") {
                e.stopPropagation();
                e.preventDefault();
            }
        });
    });

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
