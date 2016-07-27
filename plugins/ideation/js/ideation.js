var ideation = {
  start: function($) {
    'use strict';
    // Hide discussion type settings if ideas is checked.
    if (!$('#Form_IdeaCategory').length || $('#Form_IdeaCategory').attr('checked') === 'checked') {
      $('.P.DiscussionTypes').hide();
      $('#Form_UseDownVotes').parents('.form-group').show();
    } else {
      $('#Form_UseDownVotes').parents('.form-group').hide();
    }

    // Toggle discussion type settings
    $('#Form_IdeaCategory').on('change', function() {
      $('.DiscussionTypes').toggle();
      $('#Form_UseDownVotes').parents('.form-group').toggle();
    });
  }
};

jQuery(document).ready(function($) {
  'use strict';
  ideation.start($);
});
