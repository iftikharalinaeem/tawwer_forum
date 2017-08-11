$(document).ready(function () {
    $('#filterClassSource').change(function() {
        if ($(this).prop('checked')) {
            $('.js-foggy').removeClass('foggy');
            $('#Form_Garden-dot-TrustedHTMLClasses').removeAttr('disabled');
        } else {
            $('.js-foggy').addClass('foggy');
        }
    });
});
