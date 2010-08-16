function Picker() {

   Picker.prototype.Attach = function(Options) {
      
      // Load options from supplied options object
      this.RangeTarget = $(Options.Range);
      this.Granularity = Options.Units;
      this.StartDateText = Options.DateStart;
      this.EndDateText = Options.DateEnd;
      this.Nudge = Options.Nudge || true;
      
      this.StartDate = new Date(this.StartDateText);
      this.EndDate = new Date(this.EndDateText);
      
      this.RangeTarget.after('<a class="RangeToggle" href="#">' + this.RangeTarget.val() + '</a>');
      this.RangeToggle = $('a.RangeToggle');
      
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
         }
         $(e.target).blur();
      },this));
      
      this.DownTarget = false;
      this.SlideRail = $('div.Slider');
      this.Slider = $('div.SelectedRange');
      
      this.HandleStart = $('div.HandleStart');
      this.HandleEnd = $('div.HandleEnd');
      this.Range = $('div.SelectedRange');
      
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
         this.DownTarget = false;
         this.DownMoveHandler = false;
         return false;
      },this));
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
   
   Picker.prototype.MoveDelegator = function(e) {
      if (this.DownTarget == false) return;
      this.DownMoveHandler(this.DownTarget, e);
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
      if (MoveAction.Moved != 0) {
         var LeftPerc = this.ToPerc(this.HandleStart.css('left'))
         var RightPerc = this.ToPerc(this.HandleEnd.css('left'))
         var PercDiff = RightPerc - LeftPerc;
         this.Range.css('left',LeftPerc+'%');
         this.Range.css('width',PercDiff+'%');
      }
      
      return MoveAction.Moved;
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
      if (X.substr(-1,1) == '%') return parseFloat(X);
      return (parseInt(X) / this.SlideRail.width()) * 100;
   }
   
   Picker.prototype.Settings = {
      SliderHtml:       '\
<div class="Slider"> \
   <div class="SelectedRange"></div> \
   <div class="HandleContainer"> \
      <div class="SliderHandle HandleStart">6/06/09</div> \
      <div class="SliderHandle HandleEnd">8/10/10</div> \
   </div> \
   <div class="Range RangeStart"></div><div class="Range RangeMid"></div><div class="Range RangeEnd"></div> \
   <div class="SliderDates"> \
      <div class="SliderDate">Jun 6</div> \
      <div class="SliderDate">Aug 7</div> \
      <div class="SliderDate">Oct 8</div> \
      <div class="SliderDate">Dec 9</div> \
      <div class="SliderDate">Feb 9</div> \
      <div class="SliderDate">Apr 12</div> \
      <div class="SliderDate">Jun 13</div> \
   </div> \
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