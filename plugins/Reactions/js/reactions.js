jQuery(document).ready(function($) {
//   var toggleButtons = function ($container, showOrHide, direction) {
//      // Toggle the buttons.
//      if (showOrHide) {
//         $('.Handle', $container).hide();
//         $('.ReactButtons', $container).show('slide', { direction: direction }, 200);
//      } else {
//         $('.ReactButtons', $container).hide('slide', { direction: direction }, 200, function() {
//            $('.Handle', $container).show();
//         });
//      }
//         
//   };
   
//   $(document).delegate('.FlagHandle,.React .ReactHeading', 'click', function() {
//      var $container = $(this).closest('.Reactions');
//      
//      toggleButtons($('.Flag', $container), true, 'left');
//      toggleButtons($('.React', $container), false, 'right');
//      
//      return false;
//   });
//   
//   $(document).delegate('.ReactHandle,.Flag .ReactHeading', 'click', function() {
//      var $container = $(this).closest('.Reactions');
//      
//      toggleButtons($('.React', $container), true, 'right');
//      toggleButtons($('.Flag', $container), false, 'left');
//      
//      return false;
//   });
   
   if ($.fn.expander)
      $('.Expander').expander({slicePoint: 200, expandText: gdn.definition('ExpandText'), userCollapseText: gdn.definition('CollapseText')});
   
   $(document).delegate('.Buried', 'click', function(e) {
      e.preventDefault();
      $(this).removeClass('Buried').addClass('Un-Buried');
//      console.log('buried click');
      return false;
   });
   
   $(document).delegate('.Un-Buried', 'click', function() {
//      console.log('unburied click');
      $(this).removeClass('Un-Buried').addClass('Buried');
   });
});