<?php if (!defined('APPLICATION')) exit(); ?>
<script type="text/javascript">
$(function () {
   var selector = '.BestOfList > .Item', $container = $('.BestOfList');
   var $items = $('.BestOfList .Item:not(.Wide)');
   if ($items.length == 0) $items = $('.BestOfList .Item');
   var width = $items.outerWidth() + 20;

   $(selector).css({opacity: 0});
   $container.imagesLoaded(function() {
      $(selector).animate({ opacity: 1 });
      $container.masonry({
         itemSelector: selector,
         columnWidth: width
      });
   });
   
   $container.infinitescroll({
      navSelector  : '.Pager',
      nextSelector : '.Pager .Next',
      itemSelector : selector,
      loading: {
         finished: function() {
            $('.Loading').hide();
         },
         selector: '.Loading',
          finishedMsg: '&nbsp;',
          msgText: '&nbsp;',
          img: null
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
});
</script>
<?php echo Wrap($this->Data('Title'), 'h1 class="H"'); ?>
<div class="BestOfData">
   <div class="DataList BestOfList">
      <?php include_once('bestoflist.php'); ?>
   </div>
   <?php echo PagerModule::Write(array('MoreCode' => 'Load More')); ?>
   <div class="Loading"></div>
</div>