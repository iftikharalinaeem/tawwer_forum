/**
 * Groups Application - Event JS
 * 
 * 
 */

jQuery(document).ready(function($) {
   
   if ($('.Event.add').length)
      EventAddEdit($);
   
   if ($('.Event.edit').length)
      EventAddEdit($);
   
   if ($('.Event.event').length)
      EventShow($);
   
   function UpdateTimezoneDisplay(TimezoneID, TimezoneLabel) {
      var TimezoneAbbr = TimezoneLabel.match(/[A-Z]+$/).pop();
      var EventTimezone = $('.Timezone input');
      EventTimezone.val(TimezoneID);
      
      var TimezoneDisplay = $('.Timezone .EventTimezoneDisplay');
      TimezoneDisplay.text(TimezoneAbbr);
      TimezoneDisplay.attr('title', TimezoneLabel);
   }
   
   /**
    * Handle event/add
    * 
    */
   function EventAddEdit($) {
      $('.TimePicker').timepicker({
         'step': 5,
         'forceRoundTime': true
      });

      var DateNow = new Date();
      var DateNowStr = (DateNow.getMonth()+1)+'/'+DateNow.getDate()+'/'+DateNow.getFullYear();
      $('.DatePicker').val(DateNowStr);
      
      $('.Event .CancelButton').on('click', function(e){
         var Event = $(e.target).closest('.Event');
         var GroupID = Event.data('groupid');
         if (GroupID)
            window.location.replace(gdn.url('/group/'+GroupID));
         else
            window.location.replace(gdn.url('/groups'));
      });

      var DefaultTimezone = jstz.determine();
      DefaultTimezone = DefaultTimezone.name();

      var Timezone = $('.Event .EventTimezone');
      var TimezoneAbbr = $('.Event .EventTimezoneAbbr');
      
      // Lookup timezone automatically
      var TimezoneRequestData = {
         'TimezoneID': DefaultTimezone,
         'Auto': true
      }
      
      // If TZ was supplied in form, use that
      if (Timezone.val()) {
         TimezoneRequestData.TimezoneID = Timezone.val();
         TimezoneRequestData.Auto = false;
      }
      
      $.ajax({
         url: gdn.url('/event/gettimezoneabbr'),
         data: TimezoneRequestData,
         dataType: 'json',
         method: 'GET',
         success: function(data, str, xhr) {
            if (data.Abbr != 'unknown') {
               if (TimezoneRequestData.Auto)
                  var TimezoneLabel = "("+data.Offset+") Automatically detected "+data.Abbr;
               else
                  var TimezoneLabel = "("+data.Offset+") "+data.Abbr;
               
               UpdateTimezoneDisplay(data.TimezoneID, TimezoneLabel);
            }
         }
      });

      // Intercept form submission and strip end date/time if not visible
      $('.AddEvent form').submit(function(e){
         console.log('form submit');
         var EventTime = $('.EventTime');
         if (!EventTime.hasClass('Both')) {
            EventTime.find('.To input').val('');
         }
         return true;
      });

      // When the timepicker is launched, make it the same width as the input
      $('.TimePicker').on('showTimepicker', function(e){
         var TimePicker = $(e.target);
         $('.ui-timepicker-list').css('width', TimePicker.outerWidth()+'px');
      });

      // When the timepicker changes, enable 'Times' mode
      $('.TimePicker').change(function(e){
         var TimePicker = $(e.target);
         var FormParent = TimePicker.closest('.P');
         var DatePicker = FormParent.find('.DatePicker');
         var EventTime = FormParent.closest('.EventTime');

         // See if we need to enable 'Times' mode
         if (TimePicker.attr('id').match(/TimeStarts/)) {
            EventTime.addClass('Times');
         }
      });

      // Make sure we dont create events with negative time ranges
      $('.DatePicker').on('change', function(e){
         var DatePicker = $(e.target);
         var EventTime = DatePicker.closest('.EventTime');
         if (!EventTime.hasClass('Both'))
            return;

         if (DatePicker.attr('id').match(/Starts/)) {
            var TargetPicker = $('.To input.DatePicker');
            var FromDate = new Date(DatePicker.val());
            var ToDate = new Date(TargetPicker.val());
            if (ToDate.getTime() < FromDate.getTime())
               TargetPicker.val(DatePicker.val());
         }

         if (DatePicker.attr('id').match(/Ends/)) {
            var TargetPicker = $('.From input.DatePicker');
            var ToDate = new Date(DatePicker.val());
            var FromDate = new Date(TargetPicker.val());
            if (ToDate.getTime() < FromDate.getTime())
               TargetPicker.val(DatePicker.val());
         }
      });

      // When we choose a new timezone, update the hidden field and label
      $('.EventTimezonePicker a').on('click', function(e){
         var Timezone = $(e.target);

         var TimezoneID = Timezone.data('timezoneid');
         var TimezoneLabel = Timezone.text();
         UpdateTimezoneDisplay(TimezoneID, TimezoneLabel);
      })

      // Handle enabling the 'To' field
      $('.EndTime a').on('click', function(e){
         var EventTime = $(e.target).closest('.EventTime');
         EventTime.addClass('Both');

         var DateStartsPicker = EventTime.find('.From .DatePicker');
         var DateEndsPicker = EventTime.find('.To .DatePicker');
         DateEndsPicker.val(DateStartsPicker.val());
         return false;
      });

      // handle disabling the 'To' field
      $('.NoEndTime a').on('click', function(e){
         var EventTime = $(e.target).closest('.EventTime');
         EventTime.removeClass('Both');
         return false;
      });
   }
   
   function EventShow($) {
      
      var EventID = $('.EventInfo').data('eventid');
      
      $('input.EventAttending').on('change', function(e){
         var EventAttending = $(e.target);
         if (!EventAttending.val()) return;
         
         $.ajax({
            url: gdn.url('/event/attending'),
            data: {'EventID':EventID, 'Attending':EventAttending.val()},
            dataType: 'json',
            success: function(json) {
               json = $.postParseJson(json);
               
               // Process targets
               if (json.Targets && json.Targets.length > 0)
                  gdn.processTargets(json.Targets);
               
               // If there is a redirect url, go to it
               if (json.RedirectUrl != null && jQuery.trim(json.RedirectUrl) != '') {
                  window.location.replace(json.RedirectUrl);
                  return false;
               }
               
               // Inform
               gdn.inform(json);
            }
         });
      });
      
   }
   
});