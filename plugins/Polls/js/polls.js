jQuery(document).ready(function($) {
    var emptySpace = /^\s*$/g;
    var $template = $('.NewPollForm:first').find('.PollOption:first').clone();
    $template.find('.InputBox').val(''); // Reset value just in case it's not empty

    function cleanUpEmptyInputs($form) {
        $form.find('.InputBox').each(function(){
            if($(this).val().match(emptySpace)) {
                $(this).closest('.PollOption').remove();
            }
        });
    }

    function addEmptyField($pollOptions, $template) {
        $pollOptions.append($template.clone()).find('.InputBox').focus();
    }

    $('.AddPollOption').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        addEmptyField($(this).closest('.NewPollForm').find('.PollOptions'), $template);
    });

    $('.NewPollForm').each(function(){
        var $form = $(this);
        var $pollOptions = $form.find('.PollOptions');
        cleanUpEmptyInputs($form);
        addEmptyField($pollOptions, $template);

        $form.on('keypress', '.InputBox', function(e){
            if ($(this).val() != "" && $pollOptions.find('.PollOption:last-child .InputBox').val() != "") {
                $pollOptions.append($template.clone());
            }
        });

        $form.on('submit', function(){
            cleanUpEmptyInputs($form);
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
             console.log("json: ", json);
            $('.PollForm').replaceWith(json.PollHtml);
            gdn.inform(json);
            return false;
         },
         complete: function(XMLHttpRequest, textStatus) {
             console.log("XMLHttpRequest: ", XMLHttpRequest);
             console.log("textStatus: ", textStatus);
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
