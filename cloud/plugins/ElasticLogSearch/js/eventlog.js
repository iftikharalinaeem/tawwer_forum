$( document ).ready(function() {

    $('.LogRow').on('mouseup', function(e){
        var Source = '#Source_' + e.currentTarget.id;
        if (!document.getSelection().toString().length && e.target.nodeName.toLowerCase() != 'a') {
            $(Source).toggle();
        }
    });
});
