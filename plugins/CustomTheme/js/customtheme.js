// This file contains javascript that is specific to the dashboard/profile controller.
jQuery(document).ready(function($) {
   // Load tab content on tab-click
   $('.js-custom-theme-menu a').click(function() {
      $('.js-custom-theme-menu a').removeClass('active');
      $(this).addClass('active');
      if ($(this).hasClass('js-custom-html')) {
         $('input[name$=CurrentTab]').val('html');
         $('div.CustomHtmlContainer').show();
         $('div.CustomCSSContainer').hide();
      } else {
         $('input[name$=CurrentTab]').val('css');
         $('div.CustomHtmlContainer').hide();
         $('div.CustomCSSContainer').show();
      }
      return false;
   });

   $('a.Apply').popup();
});
