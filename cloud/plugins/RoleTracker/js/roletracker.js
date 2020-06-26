(function($) {
    $(document).on('click', '.JumpTo.Next', function(e) {
        var $this = $(this);

        // Let's t try to find the "next" comment in the page.
        var $targetElement;

        $container = $this.closest('.tracked');
        if ($container.hasClass('ItemDiscussion')) {
            $targetElement = $('.CommentsWrap').find('.tracked:first');
        } else {
            $targetElement = $container.next('.tracked');
        }

        if ($targetElement.length) {
            window.location.hash = '#'+$targetElement[0].id;
        } else { // Let's call the backend instead!
            $.ajax({
                dataType: 'json',
                type: 'get',
                data: { DeliveryType: 'VIEW', DeliveryMethod: 'JSON' },
                url: $this.attr('href'),
                error: function(err) {
                    var responseJSON = JSON.parse(err.responseText)
                    if (responseJSON && responseJSON.Exception)
                        gdn.informMessage(responseJSON.Exception);
                },
                success: function(json) {
                    var informed = gdn.inform(json);
                    gdn.processTargets(json.Targets);
                    // If there is a redirect url, go to it.
                    if (json.RedirectTo) {
                        setTimeout(function() {
                            window.location = json.RedirectTo;
                        }, informed ? 3000 : 0);
                    }
                }
            });
        }

        return false;
    });
})(jQuery);
