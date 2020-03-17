var ideation = {
    start: function($) {
        'use strict';

        //Adjust ideation options visibility based on field values
        function adjustIdeationOptions() {
            //If the current category is an ideation category
            if ($('#Form_IdeaCategory').prop('checked')) {
                $('#Form_UseDownVotes').parents('.form-group').show();
                $('#Form_UseBestOfIdeation').parents('.form-group').show();

                //If the current ideation category uses the bestOfIdeation feature
                if ($('#Form_UseBestOfIdeation').prop('checked')) {
                    $('[id^="Form_BestOfIdeation"]').parents('.form-group').show();
                } else {
                    $('[id^="Form_BestOfIdeation"]').parents('.form-group').hide();
                }
            } else {
                $('#Form_UseDownVotes').parents('.form-group').hide();
                $('#Form_UseBestOfIdeation').iCheck('uncheck').parents('.form-group').hide();
            }
        }

        //Adjust Ideation fields visibility based on fields values.
        adjustIdeationOptions();

        // Hide/Show DownVotes depending on choice on IdeaCategory
        $('#Form_IdeaCategory').on('change', function() { adjustIdeationOptions(); });

        // Hide/Show BestOfIdeationFields depending on choice on UseBestOfIdeation
        $('#Form_UseBestOfIdeation').on('change', function() { adjustIdeationOptions(); });

    }
};

jQuery(document).ready(function($) {
    'use strict';
    ideation.start($);
});