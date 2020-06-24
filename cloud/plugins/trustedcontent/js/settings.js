$(document).ready(function () {
    $('#filterContentSource').change(function() {
        if ($(this).prop('checked')) {
            $('#trustedContentSources').removeClass('foggy');
        } else {
            $('#trustedContentSources').addClass('foggy');
        }
    }).trigger('change');
});
