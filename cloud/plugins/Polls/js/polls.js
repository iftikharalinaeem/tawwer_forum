function getPollsInputTemplate() {
    var $template = $('.NewPollForm:first .PollOption:last-child').clone();
    $template.find('.InputBox').val('');
    return $template;
}

$(document).on('click', '.AddPollOption', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).closest('.NewPollForm').find('.PollOptions').append(getPollsInputTemplate()).find('.InputBox').focus();
});

$(document).on('input', '.PollOptionInput', function(e){
    var $pollOptions = $(this).closest('.PollOptions');
    if ($(this).val() != "" && $pollOptions.find('.PollOption:last-child .InputBox').val() != "") {
        $pollOptions.append(getPollsInputTemplate());
    }
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

$(document).on('click', '.js-poll-result-btn', function() {
    var $poll = $(this).closest('.Item');

    if (!$poll.find(".js-poll-results").is(':visible')) {
        $poll.find(".js-poll-form").hide();
        $poll.find(".js-poll-results").show();
    } else {
        $poll.find(".js-poll-form").show();
        $poll.find(".js-poll-results").hide();
    }
    return false;
});
