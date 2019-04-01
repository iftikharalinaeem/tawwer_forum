var ideation = {
    start: function($) {
        'use strict';
        // Hide DownVotes when the form first loads
        $('#Form_UseDownVotes').parents('.form-group').hide();

        // Hide/Show DownVotes.
        $('#Form_IdeaCategory').on('change', function() {
            $('.DiscussionTypes').toggle();
            if ($(this).prop('checked')) {
                $('#Form_UseDownVotes').parents('.form-group').show();
            } else {
                $('#Form_UseDownVotes').parents('.form-group').hide();
            }
        });
    }
};

jQuery(document).ready(function($) {
    'use strict';
    ideation.start($);
});