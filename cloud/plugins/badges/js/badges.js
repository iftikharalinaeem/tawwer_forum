var multicomplete = {
    start: function(element) {
        var input = $('.MultiComplete', element);
        if (input.length) {
            $('.MultiComplete').autocomplete(
                gdn.url('/user/autocomplete/'),
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

$(document).on('contentLoad', function(e) {
    multicomplete.start(e.target);
});
