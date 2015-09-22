jQuery(document).ready(function($) {
    $(".js-new-group-icon").click(function () {
        $(".js-new-group-icon-upload").trigger("click");
        $(".js-new-group-icon-upload").change(function() {
            $('form').submit();
        });
    });
});
