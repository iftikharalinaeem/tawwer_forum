// This file contains javascript that is specific to the dashboard/profile controller.
jQuery(document).ready(function($) {
   
   // Load tab content on tab-click
   $('.Tabs li a').click(function() {
      $('.Tabs li').removeClass('Active');
      var li = $(this).parent('li');
      $(li).addClass('Active');
      if ($(li).hasClass('CustomHtml')) {
         $('input[name$=CurrentTab]').val('Html');
         $('div.CustomHtmlContainer').show();
         $('div.CustomCSSContainer').hide();
      } else {
         $('input[name$=CurrentTab]').val('Css');
         $('div.CustomHtmlContainer').hide();
         $('div.CustomCSSContainer').show();
      }
      return false;
   });
   
   $('a.Apply').popup();

});