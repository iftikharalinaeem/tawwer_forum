/**
 * Groups Application - Event JS
 * 
 * 
 */

jQuery(document).ready(function($) {
   
   $('.TimePicker').timepicker({
      'step': 5,
      'forceRoundTime': true
   });
   
   var DateNow = new Date();
   $('.DatePicker').val(DateNow.getMonth()+'/'+DateNow.getDate()+'/'+DateNow.getFullYear());
   
   $('.TimePicker').change(function(e){
      var TimePicker = $(e.target);
      var FormParent = TimePicker.closest('.P');
      var DatePicker = FormParent.find('.DatePicker');
      var EventTime = FormParent.closest('.EventTime');
      
      // See if we need to enable 'both' mode
      if (TimePicker.attr('id').match(/EventTimeStarts/)) {
         EventTime.addClass('Times');
         $('.Timezone select').change();
      }
   });
   
   var DefaultTimezone = jstz.determine();
   DefaultTimezone = DefaultTimezone.name();
   
   $.ajax({
      url: '/event/gettimezoneabbr',
      data: {'TimezoneID': DefaultTimezone},
      dataType: 'json',
      method: 'GET',
      success: function(data, str, xhr) {
         if (data.Abbr != 'unknown') {
            UpdateTimezoneDisplay(data.TimezoneID, data.Abbr);
         }
      }
   });
   
   $('.TimePicker').on('showTimepicker', function(e){
      var TimePicker = $(e.target);
      $('.ui-timepicker-list').css('width', TimePicker.outerWidth()+'px');
   });
   
   $('.EventTimezonePicker a').click(function(e){
      var Timezone = $(e.target);
      
      var TimezoneID = Timezone.data('timezoneid');
      var TimezoneLabel = Timezone.text();
      UpdateTimezoneDisplay(TimezoneID, TimezoneLabel);
   })
   
   $('.EndTime a').click(function(e){
      var EventTime = $(e.target).closest('.EventTime');
      EventTime.addClass('Both');
      
      var DateStartsPicker = EventTime.find('.From .DatePicker');
      var DateEndsPicker = EventTime.find('.To .DatePicker');
      DateEndsPicker.val(DateStartsPicker.val());
      return false;
   });
   
   $('.NoEndTime a').click(function(e){
      var EventTime = $(e.target).closest('.EventTime');
      EventTime.removeClass('Both');
      return false;
   });
   
   function UpdateTimezoneDisplay(TimezoneID, TimezoneLabel) {
      var TimezoneAbbr = TimezoneLabel.match(/[A-Z]+$/).pop();
      var EventTimezone = $('.Timezone input');
      EventTimezone.val(TimezoneID);
      
      var TimezoneDisplay = $('.Timezone .EventTimezoneDisplay');
      TimezoneDisplay.text(TimezoneAbbr);
   }
   
});