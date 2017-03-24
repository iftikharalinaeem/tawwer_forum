$(document).ready(function () {
    if ($('#Form_Garden-dot-HTML-dot-FilterContentSources').prop('checked')) {
        $('#trustedContentSources').removeClass('hidden');
    } else {
        $('#trustedContentSources').addClass('hidden');
    }

    $('#Form_Garden-dot-HTML-dot-FilterContentSources').change(
        function() {
            if ($(this).prop('checked')) {
                $('#trustedContentSources').removeClass('hidden');
            } else {
                $('#trustedContentSources').addClass('hidden');
            }
        }
    );
});
