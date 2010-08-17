function Picker() {

   Picker.prototype.Attach = function(Options) {
      
      // Load options from supplied options object
      this.RangeTarget = $(Options.Range);
      this.Graduations = Options.MaxGraduations || 8;
      this.Nudge = Options.Nudge || true;
      
      this.RangeTarget.after('<a class="RangeToggle" href="#">' + this.RangeTarget.val() + '</a>');
      this.RangeTarget.hide();
      this.RangeToggle = $('a.RangeToggle');
      this.RangeTarget.bind('change', function() {
         $('a.RangeToggle').text($(this).val())
      })
      
      this.PickerContainer = $('div.Picker');
      // Add the picker container if it wasn't already on the page somewhere
      if (this.PickerContainer.length == 0) {
         this.RangeToggle.after('<div class="Picker"></div>');
         this.PickerContainer = $('div.Picker');
      }
      this.PickerContainer.hide();
      this.PickerContainer.html(this.Settings.SliderHtml);
      this.RangeToggle.bind('click', jQuery.proxy(function(e) {
         if ($(e.target).hasClass('RangeToggleActive')) {
            $(e.target).removeClass('RangeToggleActive');
            this.PickerContainer.slideUp('fast');
         } else {
            $(e.target).addClass('RangeToggleActive');
            this.PickerContainer.slideDown('fast');
            this.UpdateUI();
            this.SyncSlider();
         }
         $(e.target).blur();
      },this));
      
      this.DownTarget = false;
      this.SlideRail = $('div.Slider');
      this.Slider = $('div.SelectedRange');
      
      this.HandleStart = $('div.HandleStart');
      this.HandleEnd = $('div.HandleEnd');
      this.Range = $('div.SelectedRange');
      this.InputStart = $('div.InputRange input[name=DateStart]');
      this.InputEnd = $('div.InputRange input[name=DateEnd]');
      
      this.RailWidth = (this.SlideRail.width() != 0) ? this.SlideRail.width() : 700;
      
      this.HandleStart.get(0).limit = jQuery.proxy(function(){
         return this.LimitStayLeft(this.HandleEnd);
      }, this);
      
      this.HandleEnd.get(0).limit = jQuery.proxy(function(){
         return this.LimitStayRight(this.HandleStart);
      }, this);
      
      $(document).bind('mousemove', jQuery.proxy(this.MoveDelegator, this));
      
      $('div.SliderHandle, div.SelectedRange').bind('mousedown', jQuery.proxy(function(e){
         this.DownTarget = $(e.target);
         this.Down(e.clientX);
         return false;
      },this));
      
      $(document).bind('mouseup', jQuery.proxy(function(e){
         if (this.DownTarget == false) return;
         this.UpdateUI(true);
         this.DownTarget = false;
         this.DownMoveHandler = false;
         return false;
      },this));
      
      $('div.InputRange input').bind('change', jQuery.proxy(function(e){
         this.SetRange(this.InputStart.val(), this.InputEnd.val(), true);
      },this));
      
      this.Axis(Options.DateStart, Options.DateEnd, Options.Units);
      
      var RangeStart = Options.RangeStart || Options.DateStart;
      var RangeEnd = Options.RangeEnd || Options.DateEnd;
      this.SetRange(RangeStart, RangeEnd);
   }
   
   Picker.prototype.Axis = function(StartDate, EndDate, Units) {
      var AdjustedStartLimit = this.GetStartLimit(StartDate, Units);
      var AdjustedEndLimit = this.GetEndLimit(EndDate, Units);
      
      this.Axis = {
         'Start': {'Original':StartDate, 'Date':AdjustedStartLimit, 'Milli': AdjustedStartLimit.valueOf()},
         'End': {'Original':EndDate, 'Date':AdjustedEndLimit, 'Milli': AdjustedEndLimit.valueOf()},
         'Diff': {},
         'Ticks': {}
      }
      
      this.Units = Units;
      var MilliDiff = this.Axis.Diff.Milli = this.Axis.End.Date.getTime() - this.Axis.Start.Date.getTime();
      var SecondsDiff = this.Axis.Diff.Sec = MilliDiff / 1000;
      var DaysDiff = this.Axis.Diff.Day = SecondsDiff / (3600*24);
      
      switch (this.Units) {
         case 'month':
            var NumTicks = 0; var MonthTicks = [];
            var WorkingDate = new Date(this.Axis.Start.Date); var AvailableDays = DaysDiff;
            do {
            
               var DaysInThisMonth = this.GetDaysInMonth(WorkingDate.getFullYear(), WorkingDate.getMonth());
               var TickLabel = this.GetShortMonth(WorkingDate.getMonth())+' \''+String(WorkingDate.getFullYear()).substring(2,4);
            
               var NextMonth = (WorkingDate.getMonth() < 11) ? WorkingDate.getMonth()+1 : 0;
               var NextYear = (WorkingDate.getMonth() < 11) ? WorkingDate.getFullYear() : WorkingDate.getFullYear()+1;
               WorkingDate.setFullYear(NextYear);
               WorkingDate.setMonth(NextMonth);
               
               var KeepItUp = ((WorkingDate.getFullYear() > this.Axis.End.Date.getFullYear()) || (WorkingDate.getFullYear() == this.Axis.End.Date.getFullYear()) && (WorkingDate.getMonth() > this.Axis.End.Date.getMonth())) ? false : true;
               if (KeepItUp) {
                  AvailableDays -= DaysInThisMonth;
                  MonthTicks[NumTicks] = {
                     'Label': TickLabel,
                     'Days': DaysInThisMonth,
                     'Perc': (DaysInThisMonth / DaysDiff)
                  };
                  NumTicks++;
               }
            } while(KeepItUp == true);
            
         break;
         case 'week':
            var NumTicks = DaysDiff/7;
         break;
         case 'day':
            var NumTicks = DaysDiff;
         break;
      }
      
      this.Axis.Ticks.Count = NumTicks;
      this.Axis.Ticks.PerGraduation = Math.ceil(NumTicks / this.Graduations);
      this.Graduations = NumTicks / this.Axis.Ticks.PerGraduation;
      var WidthPerTick = this.RailWidth / NumTicks;
      this.Axis.Ticks.WidthPerGraduation = this.Axis.Ticks.PerGraduation * WidthPerTick;
      
      var SliderDates = $('div.SliderDates');
      SliderDates.html('');
      var SliderWidth = (this.RailWidth / this.Graduations) - 20;
      for (Graduation = 0; Graduation < this.Graduations; Graduation++) {
         var Tick = Graduation * this.Axis.Ticks.PerGraduation;
         var AmountPercent = (Tick / NumTicks);
         var DeltaMilli = AmountPercent * MilliDiff;
         var SpotDate = new Date(this.Axis.Start.Date.valueOf() + DeltaMilli);
         
         if (this.Units == 'month') {
            var TickLabel = MonthTicks[Tick].Label;
         } else {
            var TickLabel = this.GetShortMonth(SpotDate.getMonth())+' '+SpotDate.getDate();
         }
         
         var PxLeft = (AmountPercent * this.RailWidth);
         SliderDates.append('<div class="SliderDate" style="left: '+PxLeft+'px;">'+TickLabel+'</div>');
      }
      
   }

   Picker.prototype.GetLongDate = function(DateItem) {
      return this.GetMonth(DateItem.getMonth())+' '+DateItem.getDate()+', '+DateItem.getFullYear();
   }
   
   Picker.prototype.GetStrDate = function(DateItem) {
      return (DateItem.getMonth()+1)+'/'+DateItem.getDate()+'/'+DateItem.getFullYear();
   }
   
   Picker.prototype.GetShortStrDate = function(DateItem) {
      return DateItem.getDate()+'/'+(DateItem.getMonth()+1)+'/'+String(DateItem.getFullYear()).substring(2,4);
   }
   
   Picker.prototype.GetStartLimit = function(DateItem, Unit) {
      var CurrentDate = new Date(DateItem);
      switch(Unit) {
         case 'month':
            CurrentDate.setDate(1);
            return CurrentDate;
         break;
         case 'week':
            if (CurrentDate.getDate() > CurrentDate.getDay()) {
               CurrentDate.setDate(CurrentDate.getDate() - CurrentDate.getDay());
            } else {
               var Difference = CurrentDate.getDay() - CurrentDate.getDate();
               
               // Gotta roll back to previous month. Gotta check if possible first, otherwise roll back the year too.
               if (CurrentDate.getMonth()) {
                  CurrentDate.setMonth(CurrentDate.getMonth()-1);
               } else {
                  CurrentDate.setYear(CurrentDate.getYear()-1);
                  CurrentDate.setMonth(11);
               }
               var DaysInMonth = this.GetDaysInMonth(CurrentDate.getYear(), CurrentDate.getMonth());
               CurrentDate.setDate(DaysInMonth-Difference);
            }
            
            return CurrentDate;
         break;
         case 'day':
         default:
            return CurrentDate;
         break;
      }
   }
   
   Picker.prototype.GetEndLimit = function(DateItem, Unit) {
      return this.GetStartLimit(DateItem, Unit);
   }
   
   Picker.prototype.GetDaysInMonth = function (Year, Month) {
      Month++;
      switch (Month) {
         case 1: return 31;
         case 2:
            var isLeap = new Date(Year,1,29).getDate() == 29;
            return (isLeap) ? 29 : 28;
         case 3: return 31;
         case 4: return 30;
         case 5: return 31;
         case 6: return 30;
         case 7: return 31;
         case 8: return 31;
         case 9: return 30;
         case 10: return 31;
         case 11: return 30;
         case 12: return 31;
      }
   }
   
   Picker.prototype.GetShortMonth = function(Month) {
      var M = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      return M[Month];
   }
   
   Picker.prototype.GetMonth = function(Month) {
      var M = ['January','February','March','April','May','June','July','August','September','October','November','December'];
      return M[Month];
   }
   
   Picker.prototype.GetNextGraduation = function(DateObj) {
      
   }
   
   Picker.prototype.Down = function(ClientX) {
      this.DownX = ClientX;
      
      this.DownSliderWidth = this.Slider.width();
      this.DownRailWidth = this.SlideRail.width();
      
      this.DownRailLeft = this.SlideRail.offset().left;
      this.DownRailRight = this.DownRailLeft + this.DownRailWidth;
      this.DownRailX = this.DownX - this.DownRailLeft;
      
      this.DownClickLeftDifference = this.DownX - this.Slider.offset().left;
      this.DownClickRightDifference = (this.Slider.offset().left + this.DownSliderWidth) - this.DownX;
      
      this.DownMoveHandler = (this.DownTarget.hasClass('SelectedRange')) ? this.MoveSlider : this.MoveHandle;
   }
   
   Picker.prototype.UpdateUI = function(HardUpdate, NoTrigger) {
      this.RailWidth = this.SlideRail.width();
      
      var StartPerc = this.HandleStart.position().left;
      if (String(this.HandleStart.css('left')).substring(-1,1) != '%')
         StartPerc = StartPerc / this.RailWidth;
         
      var StartDeltaMilli = StartPerc * this.Axis.Diff.Milli;
      var StartDate = this.GetStartLimit(new Date(this.Axis.Start.Date.valueOf() + StartDeltaMilli),this.Units);
      var StartShortDate = this.GetShortStrDate(StartDate);
      this.HandleStart.html(StartShortDate);
      this.InputStart.val(this.GetStrDate(StartDate));
      
      var EndPerc = this.HandleEnd.position().left;
      if (String(this.HandleEnd.css('left')).substring(-1,1) != '%')
         EndPerc = EndPerc / this.RailWidth;
         
      var EndDeltaMilli = EndPerc * this.Axis.Diff.Milli;
      var EndDate = this.GetEndLimit(new Date(this.Axis.Start.Date.valueOf() + EndDeltaMilli),this.Units);
      var EndShortDate = this.GetShortStrDate(EndDate);
      this.HandleEnd.html(EndShortDate);
      this.InputEnd.val(this.GetStrDate(EndDate));
      
      if (HardUpdate == true) {
         var FormatStartDate = this.GetLongDate(StartDate);
         var FormatEndDate = this.GetLongDate(EndDate);
         this.RangeTarget.val(FormatStartDate+' - '+FormatEndDate);
         if (!NoTrigger)
            this.RangeTarget.trigger('change');
      }
   }
   
   Picker.prototype.SetRange = function(RangeStart, RangeEnd, Trigger) {
      if (Date.parse(RangeStart) < 1 || Date.parse(RangeEnd) < 1) return;
      
      var RangeStart = new Date(RangeStart);
      var RangeEnd = new Date(RangeEnd);
      
      if (RangeStart.valueOf() < this.Axis.Start.Milli)
         RangeStart.setTime(this.Axis.Start.Milli);
      if (RangeEnd.valueOf() > this.Axis.End.Milli)
         RangeEnd.setTime(this.Axis.End.Milli);
      
      var DateRangeStart = this.GetStartLimit(RangeStart, this.Units);
      var DateRangeEnd = this.GetEndLimit(RangeEnd, this.Units);
      
      
      var FormatStartDate = this.GetLongDate(DateRangeStart);
      var FormatEndDate = this.GetLongDate(DateRangeEnd);
      this.RangeTarget.val(FormatStartDate+' - '+FormatEndDate);

      var MilliStartDiff = DateRangeStart.valueOf() - this.Axis.Start.Milli;
      var MilliEndDiff = DateRangeEnd.valueOf() - this.Axis.Start.Milli;
      
      var PercStart = (MilliStartDiff / this.Axis.Diff.Milli) * 100;
      var PercEnd = (MilliEndDiff / this.Axis.Diff.Milli) * 100;
      
      this.DoMoveHandle(this.HandleStart, PercStart);
      this.DoMoveHandle(this.HandleEnd, PercEnd);
      this.SyncSlider();
/*      
      if (Trigger == true) {
         console.log('setrange trigger');
         this.RangeTarget.trigger('change');
      }
*/
   }
   
   Picker.prototype.MoveDelegator = function(e) {
      if (this.DownTarget == false) return;
      this.DownMoveHandler(this.DownTarget, e);
      this.UpdateUI();
      return false;
   }
   
   Picker.prototype.MoveHandle = function(Handle, Event) {
      
      // Computed 'X' relative to the start of the slide rail
      var RelativeX = Event.clientX - this.DownRailLeft;
      RelativeX = (RelativeX < 0) ? 0 : RelativeX;
      RelativeX = (RelativeX > this.DownRailWidth) ? this.DownRailWidth : RelativeX;
      var PercX = (RelativeX / this.DownRailWidth) * 100;
      
      var MoveAction = this.DoMoveHandle(Handle, PercX);
      
      // Resize slider
      
      if (MoveAction.Moved != 0)
         this.SyncSlider();
      
      return MoveAction.Moved;
   }
   
   Picker.prototype.SyncSlider = function() {
      var LeftPerc = this.ToPerc(this.HandleStart.css('left'))
      var RightPerc = this.ToPerc(this.HandleEnd.css('left'))
      var PercDiff = RightPerc - LeftPerc;
      this.Range.css('left',LeftPerc+'%');
      this.Range.css('width',PercDiff+'%');
   }
   
   Picker.prototype.DoMoveHandle = function(Handle, ProposedPercX, Manual) {
      if (Manual != true && this.Nudge == true) {
         var AllowedMinMax = Handle.get(0).limit();
         if (ProposedPercX > AllowedMinMax.right || ProposedPercX < AllowedMinMax.left) {
            // Nudge
            this.DoMoveHandle(AllowedMinMax.Ref, ProposedPercX);
         }
      }
      
      var AllowedMinMax = Handle.get(0).limit();
      var RealPercX = ProposedPercX;
      RealPercX = (RealPercX < AllowedMinMax.left) ? AllowedMinMax.left : RealPercX;
      RealPercX = (RealPercX > AllowedMinMax.right) ? AllowedMinMax.right : RealPercX;
      var CurrentPercX = this.ToPerc($(Handle).css('left'));
      $(Handle).css('left',RealPercX+'%');
      
      return {
         'Ref': AllowedMinMax.Ref,
         'Moved': RealPercX - CurrentPercX
      }
   }
   
   Picker.prototype.MoveSlider = function(Handle, Event) {
      var LeftRelativeX = (Event.clientX - this.DownRailLeft) - this.DownClickLeftDifference;
      if (LeftRelativeX < 0) {
         LeftRelativeX = 0;
      }
      var LeftPercX = (LeftRelativeX / this.DownRailWidth) * 100;
      
      var RightRelativeX = LeftRelativeX + this.DownSliderWidth;
      if (RightRelativeX > this.DownRailWidth) {
         RightRelativeX = this.DownRailWidth;
         LeftRelativeX = RightRelativeX - this.DownSliderWidth;
         LeftPercX = (LeftRelativeX / this.DownRailWidth) * 100;
      }
      var RightPercX = (RightRelativeX / this.DownRailWidth) * 100;
      
      MoveAction = this.DoMoveHandle(this.HandleStart, LeftPercX, true);
      MoveAction = this.DoMoveHandle(this.HandleEnd, RightPercX, true);
      
      this.Range.css('left',LeftPercX+'%');
      
/*      if (LeftPercX == 0 && Event.clientX <= this.DownRailLeft) {
         this.Down(this.DownRailLeft);
      }
      if (RightPercX == 100 && Event.clientX >= this.DownRailRight) {
         this.Down(this.DownRailRight);
      }*/
   }

   Picker.prototype.LimitStayLeft = function(ReferenceElement) {
      return { 'left':0, 'right':this.ToPerc(ReferenceElement.css('left')), 'Ref':ReferenceElement };
   }
   
   Picker.prototype.LimitStayRight = function(ReferenceElement) {
      return { 'left':this.ToPerc(ReferenceElement.css('left')), 'right':100, 'Ref':ReferenceElement };
   }
   
   Picker.prototype.ToPerc = function(X) {
      if (String(X).substr(-1,1) == '%') return parseFloat(X);
      return (parseInt(X) / this.SlideRail.width()) * 100;
   }
   
   Picker.prototype.Settings = {
      SliderHtml:       '\
<div class="Slider"> \
   <div class="SelectedRange"></div> \
   <div class="HandleContainer"> \
      <div class="SliderHandle HandleStart"></div> \
      <div class="SliderHandle HandleEnd"></div> \
   </div> \
   <div class="Range RangeStart"></div><div class="Range RangeMid"></div><div class="Range RangeEnd"></div> \
   <div class="SliderDates"></div> \
</div> \
<hr /> \
<div class="InputRange"> \
   <label for="DateStart" class="DateStart">Start Date</label> \
   <input type="text" name="DateStart" /> \
   <label for="DateEnd" class="DateEnd">End Date</label> \
   <input type="text" name="DateEnd" /> \
</div>'
   }

}