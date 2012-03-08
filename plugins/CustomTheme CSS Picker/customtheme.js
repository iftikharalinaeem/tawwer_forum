// This file contains javascript that is specific to the dashboard/profile controller.
jQuery(document).ready(function($) {
   
   // Load tab content on tab-click
   $('.Tabs li a').click(function() {
      $('.Tabs li').removeClass('Active');
      var li = $(this).parent('li');
      $(li).addClass('Active');
      if ($(li).hasClass('CustomHtml')) {
         $('input[name$=CurrentTab]').val('html');
         $('div.CustomHtmlContainer').show();
         $('div.CustomCSSContainer').hide();
         $('div.CustomColorContainer').hide();
      } else if ($(li).hasClass('CustomColor')) {
         $('input[name$=CurrentTab]').val('color');
         $('div.CustomHtmlContainer').hide();
         $('div.CustomCSSContainer').hide();
         $('div.CustomColorContainer').show();
      } else {
         $('input[name$=CurrentTab]').val('css');
         $('div.CustomHtmlContainer').hide();
         $('div.CustomCSSContainer').show();
         $('div.CustomColorContainer').hide();
      }
      return false;
   });
   
   // Popup apply links
   $('a.Apply').popup();
   
});