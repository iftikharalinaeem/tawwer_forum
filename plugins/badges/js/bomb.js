jQuery(document).ready(function($) {
    var bombComments = function() {
        $.ajax({
            url: gdn.url('/utility/model/userbadge/bombcomment.json?limit=250'),
            success: function(data) {
                if (data.Result > 0) {
                    gdn.informMessage(data.Result + ' users processed.');
                    bombComments();
                } else {
                    gdn.informMessage('Comment badges complete.');
                }
            }
        });
    };
    bombComments();

    var bombAnniversaries = function() {
        $.ajax({
            url: gdn.url('/utility/model/userbadge/bombanniversary.json?limit=250'),
            success: function(data) {
                if (data.Result > 0) {
                    gdn.informMessage(data.Result + ' users processed.');
                    bombAnniversaries();
                } else {
                    gdn.informMessage('Anniversary badges complete.');
                }
            }
        });
    };
    bombAnniversaries();
});