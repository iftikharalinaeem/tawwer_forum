/**
 * Groups Application - Event JS
 *
 *
 */

(function(window, $) {
   // Synchronize the hidden date whenever there is an update.
   if (!Date.prototype.toISOString) {
      (function() {

         function pad(number) {
            if (number < 10) {
               return '0' + number;
            }
            return number;
         }

         Date.prototype.toISOString = function() {
            return this.getUTCFullYear() +
                '-' + pad(this.getUTCMonth() + 1) +
                '-' + pad(this.getUTCDate()) +
                'T' + pad(this.getUTCHours()) +
                ':' + pad(this.getUTCMinutes()) +
                ':' + pad(this.getUTCSeconds()) +
                '.' + (this.getUTCMilliseconds() / 1000).toFixed(3).slice(2, 5) +
                'Z';
         };

      }());
   }

   var syncDate = function () {
      var $picker = $(this).closest('.js-datetime-picker');
      var dateStr = $('.DatePicker', $picker).val();
      var timeStr = $('.TimePicker', $picker).val();

      // Adjust the time string to add a space between the time and am/pm.
      if (!timeStr) {
         timeStr = $('.TimePicker', $picker).data('empty') || '';
      }
      timeStr = timeStr.replace(/(\d)([ap]m)/i, '$1 $2', timeStr);

      var dateTimeStr = '';
      if (dateStr && !isNaN(Date.parse(dateStr+' '+timeStr))) {
         var dt = new Date(dateStr+' '+timeStr);
         dateTimeStr = dt.toISOString();
      }
      console.log(dateTimeStr);
      $('input[type="hidden"]', $picker).val(dateTimeStr);
   };

   $(document).on('blur', '.js-datetime-picker input[type="text"]', syncDate);
   $(document).on('submit', 'form', function() {
      $('.js-datetime-picker', this).each(function () {
         syncDate.apply(this);
      });
   });
})(window, jQuery);

jQuery(document).ready(function($) {

   if ($('.Event.add').length) {
      EventAddEdit($);
   }

   if ($('.Event.edit').length) {
      EventAddEdit($);
   }

   if ($('.Event.event').length) {
      EventShow($);
   }

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

      // Set the initial date picker values from the full hidden date.
      $('.js-datetime-picker').each(function () {
         var str = $('input[type="hidden"]', this).val();
         if (str && !isNaN(Date.parse(str))) {
            var dt = new Date(str);

            // Fill in the date.
            $('.DatePicker', this).val((dt.getMonth() + 1)+'/'+dt.getDate()+'/'+dt.getFullYear());

            // Fill in the time.
            var timeStr, mins;
            mins = dt.getMinutes().toString();
            if (mins.length === 1) {
               mins = '0'+mins;
            }

            if (dt.getHours() === 0) {
               timeStr = '12:'+mins+'am';
            } else if (dt.getHours() < 12) {
               timeStr = dt.getHours().toString()+':'+mins+'am';
            } else {
               timeStr = (dt.getHours() - 12).toString()+':'+mins+'pm';
            }

            if (timeStr === $('.TimePicker', this).data('empty')) {
               timeStr = '';
            }
            $('.TimePicker', this).val(timeStr);
         }
      });

      $('.Event .CancelButton').on('click', function(e){
         var Event = $(e.target).closest('.Event');
         var GroupID = Event.data('groupid');
         if (GroupID)
            window.location.replace(gdn.url('/group/'+GroupID));
         else
            window.location.replace(gdn.url('/groups'));
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


   /**
    * Handles the rsvp dropdowns in the event lists and the select box on the event page
    * and the updating of the user's event status.
    */
   function EventShow($) {

      // On event list
      $('.js-event .EventAttending').on('click', function(e) {
         e.preventDefault();
         var selection = $(e.target);
         var eventId = $(selection.closest('.js-event')).attr('id');

         var optionList = selection.closest('ul');
         var option = selection.closest('li');

         var newStatus = selection.html();
         var newStatusCode = selection.attr('data-name');

         var oldStatus = $('#'+eventId+' .js-status').html();
         var oldStatusCode = $('#'+eventId+' .js-status').attr('data-name');

         // set trigger data
         $('#'+eventId+' .js-status').html(newStatus);
         $('#'+eventId+' .js-status').attr('data-name', newStatusCode);

         // reset dropdown options
         option.detach();
         if (oldStatusCode !== 'rsvp') {
            optionList.append('<li><a class="EventAttending" data-name="' + oldStatusCode + '">' + oldStatus + '</a></li>');
         }

         EventShow($);
         processStatus(eventId, newStatusCode);
      });

      // On event page
      $('.EventAttending').on('change', function(e) {
         var eventId = $('.EventInfo').data('eventid');
         var eventAttending = $(e.target);
         var result = false;
         if (eventAttending.val()) {
            result = eventAttending.val();
         }
         if (!result) {
            return;
         }
         processStatus(eventId, result);
      });


      /**
       * Handles the ajax-y updating of the user's attendance status for an event.
       */
      function processStatus(eventId, result) {
         $.ajax({
            url: gdn.url('/event/attending'),
            data: {'EventID':eventId, 'Attending':result},
            dataType: 'json',
            success: function(json) {
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
      }
   }
});
