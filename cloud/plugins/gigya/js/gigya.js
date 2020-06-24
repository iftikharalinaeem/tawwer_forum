(function(window, $) {
    gigya.socialize.addEventHandlers({
        onLogin: function(e) {
            var user = e.user;
            if (user) {
                // Create a hidden form.
                $('body').append('<form id="jsGigyaForm" method="post" style="display:block"><input id="jsGigyaUser" type="hidden" name="User" /></form>');

                $('#jsGigyaUser').val(JSON.stringify(user));
                $('#jsGigyaForm')
                    .attr('action', gdn.url('/entry/connect/gigya'))
                    .submit();

            }
        }
    });
})(window, jQuery);