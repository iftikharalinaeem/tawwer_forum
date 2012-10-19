jQuery(document).ready(function($) {
   var selector = '.Tiles > .Tile', $container = $('.Tiles');
   var $items = $('.Tiles .Tile:not(.Wide)');
   if ($items.length == 0) $items = $('.Tiles .Tile');
   var width = $items.outerWidth() + 15;
   
   $(selector).css({opacity: 0});
   $container.imagesLoaded(function() {
      $(selector).animate({ opacity: 1 });
      $container.masonry({
         itemSelector: selector,
         // columnWidth: width
      });
   });
   
   // Do inifinite scroll on the best of page.
   if ($('.BestOfWrap').length > 0) {
      $('.Pager').hide();
      $container.infinitescroll({
         navSelector  : '.Pager',
         nextSelector : '.Pager .Next',
         itemSelector : selector,
         loading: {
            finished: function() {
               // $('.LoadingMore').hide();
            },
            selector: '.LoadingMore',
             finishedMsg: '&nbsp;',
             msgText: '&nbsp;',
             img: 'http://cdn.vanillaforums.com/images/progress.gif'
           },
         pixelsFromNavToBottom: 800
      }, function(newElements) {
           var $newElems = $(newElements).css({ opacity: 0 });
           $newElems.imagesLoaded(function(){
             $newElems.animate({ opacity: 1 });
             $container.masonry( 'appended', $newElems, true ); 
           });
         }
      );   
   }
});