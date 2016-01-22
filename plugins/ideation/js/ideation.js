var ideation = {
  start: function() {

    // Hide idea checkbox
    $('input[value="Idea"]').parents('label').hide();

    // Hide discussion type settings if ideas is checked.
    if ($('#Form_IdeaCategory').attr('checked') === 'checked') {
      $('.P.DiscussionTypes').hide();
    }

    // Toggle discussion type settings
    $('#Form_IdeaCategory').click(function() {
      console.log($('#Form_IdeaCategory').val());
      $('.P.DiscussionTypes').toggle();
    });
  }
}

jQuery(document).ready(function($) {
  ideation.start();
});
