jQuery(document).ready(function($) {

    if (!$.fn.userTokens) {
        $.fn.userTokens = function() {
            var $this = $(this);

            var author = $this.val();
            if (author && author.length) {
                author = author.split(",");
                for (i = 0; i < author.length; i++) {
                    author[i] = { id: i, name: author[i] };
                }
            } else {
                author = [];
            }

            $this.tokenInput(gdn.url('/user/tagsearch'), {
                hintText: gdn.definition("TagHint", "Start to type..."),
                tokenValue: 'name',
                searchingText: '', // search text gives flickery ux, don't like
                searchDelay: 300,
                minChars: 1,
                maxLength: 25,
                prePopulate: author,
                animateDropdown: false
            });
        }
    }

    $('.Tokens-User').livequery(function() {
        $(this).userTokens();
    });
});
