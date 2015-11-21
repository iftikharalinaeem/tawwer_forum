var multicomplete = {
    start: function() {
        var input = $('.MultiComplete');
        if (input.length) {
            $('.MultiComplete').autocomplete(
                gdn.url('/dashboard/user/autocomplete/'),
                {
                    minChars: 1,
                    multiple: true,
                    scrollHeight: 220,
                    selectFirst: true
                }
            ).autogrow();
        }
    }
}

jQuery(document).ready(function($) {
    multicomplete.start();
    $('.js-give-badge').popup({
        afterLoad: multicomplete.start
    });
});
