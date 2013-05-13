(function(window, $) {

$(document).on('click', '.AdvancedSearch .Handle', function(e) {
    e.preventDefault();
    var $container = $(this).closest('.AdvancedSearch');
    var $adv = $container.find('input[name="adv"]');
    
    if ($container.hasClass('Open')) {
        $container.find('.AdvancedWrap').slideUp(100, function() { $container.removeClass('Open'); });
        $adv.val('0')
    } else {
        $container.find('.AdvancedWrap').slideDown(100, function() { $container.addClass('Open'); });
        $adv.val('1')
    }
    
//    var open = $(this).closest('.AdvancedSearch').toggleClass('Open').hasClass('Open');
//    $(this).closest('.AdvancedSearch').find('input[name="adv"]').val(open ? '1' : '0');
});

$.fn.searchAutocomplete = function() {
    this.each(function() {
        var $this = $(this);
    //    $this.attr('autocomplete', 'off');
        $this.autocomplete({
            source: gdn.url('/search/autocomplete.json'),
            focus: function() {
              // prevent value inserted on focus
              return false;
            },
            select: function( event, ui ) { 
                window.location.replace(gdn.url(ui.item.Url));
            }
        });
        
        var $ac = $this.data( "ui-autocomplete" );

        if ($ac) {
            $ac.menu.element.addClass('MenuItems MenuItems-Input');

            $ac._renderItem = function( ul, item ) {
                return $( "<li><a></a></li>" )
                  .find('a')
                  .text(item.Title)
                  .attr('href', item.Url)
                  .closest('li')
                  .appendTo( ul );
              };
        }
    });
    return this;
};
    

})(window, jQuery);

jQuery(document).ready(function($) {
    /// Search box autocomplete.
    if ($.fn.searchAutocomplete) {
        $('.AdvancedSearch #Form_search').searchAutocomplete();
        $('.SiteSearch #Form_Search').searchAutocomplete();
    }

    /// Author tag token input.
    var $author = $('.AdvancedSearch input[name="author"]');
    
    var author = $author.val();
    if (author && author.length) {
        author = author.split(",");
        for (i = 0; i < author.length; i++) {
            author[i] = { id: i, name: author[i] };
        }
    } else {
        author = [];
    }
    
    $author.tokenInput(gdn.url('/user/tagsearch'), {
        hintText: gdn.definition("TagHint", "Start to type..."),
        tokenValue: 'name',
        searchingText: '', // search text give flickery ux, don't like
        searchDelay: 300,
        minChars: 1,
        maxLength: 25,
        prePopulate: author,
        animateDropdown: false
    });

    /// Tag token input.
    var $tags = $('.AdvancedSearch input[name="tags"]');

    var tags = $tags.val();
    if (tags && tags.length) {
        tags = tags.split(",");
        for (i = 0; i < tags.length; i++) {
            tags[i] = { id: i, name: tags[i] };
        }
    } else {
        tags = [];
    }

    $tags.tokenInput(gdn.url('/plugin/tagsearch?id=1'), {
      hintText: gdn.definition("TagHint", "Start to type..."),
      tokenValue: 'name',
      searchingText: '',
      searchDelay: 300,
      minChars: 1,
      maxLength: 25,
      prePopulate: tags,
      animateDropdown: false
  });
});