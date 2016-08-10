var ideation = {
  start: function($) {
    'use strict';

    // Hide idea checkbox
    $('input[value="Idea"]').parents('label').hide();
    $('#Form_UseDownVotes').parents('label').hide();

    // Hide discussion type settings if ideas is checked.
    if ($('#Form_IdeaCategory').attr('checked') === 'checked') {
      $('.P.DiscussionTypes').hide();
      $('#Form_UseDownVotes').parents('label').show();
    }

    // Toggle discussion type settings
    $('#Form_IdeaCategory').click(function() {
      console.log($('#Form_IdeaCategory').val());
      $('.P.DiscussionTypes').toggle();
      $('#Form_UseDownVotes').parents('label').toggle();
    });
  }
};

jQuery(document).ready(function($) {
  'use strict';
  ideation.start($);
});
