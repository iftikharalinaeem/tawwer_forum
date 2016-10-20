// This file contains javascript that is specific to the dashboard/profile controller.
jQuery(document).ready(function($) {
   // Load tab content on tab-click
   $('.js-custom-theme-menu a').click(function() {
      $('.js-custom-theme-menu a').removeClass('active');
      $('.js-custom-theme-menu a').attr('aria-selected', 'false');
      $(this).addClass('active');
      $(this).attr('aria-selected', 'true');
      if ($(this).hasClass('js-custom-html')) {
         $('input[name$=CurrentTab]').val('html');
         $('#customHtmlContainer').show();
         $('#customCssContainer').hide();
      } else {
         $('input[name$=CurrentTab]').val('css');
         $('#customHtmlContainer').hide();
         $('#customCssContainer').show();
      }
      return false;
   });

});
