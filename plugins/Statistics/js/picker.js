function Picker() {

   Picker.prototype.Attach = function(Options) {
   
      this.RangeTarget = $(Options.Range);
      this.Granularity = Options.Units;
      this.StartDateText = Options.DateStart;
      this.EndDateText = Options.DateEnd;
      this.Nudge = Options.Nudge || true;
      
      this.StartDate = new Date(this.StartDateText);
      this.EndDate = new Date(this.EndDateText);
      
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
         this.DownX = e.clientX;
         return false;
      },this));
      
      $(document).bind('mouseup', jQuery.proxy(function(e){
         if (this.DownTarget == false) return;
         this.DownTarget = false;
      },this));
   }
   
   Picker.prototype.MoveDelegator = function(e) {
      if (this.DownTarget == false) return;
      
      if (this.DownTarget.hasClass('SelectedRange'))
         this.MoveSlider(this.DownTarget, e);
      else
         this.MoveHandle(this.DownTarget, e);
         
      return false;
   }
   
   Picker.prototype.MoveHandle = function(Handle, Event, Coupled) {
      console.log('real:'+Event.clientX+' start:'+this.DownX);
      
      var SliderPos = this.SlideRail.offset();
      var SliderWidth = this.SlideRail.width();
      var SliderLeft = SliderPos.left;
      var SliderRight = SliderPos.left + this.SlideRail.width();
      
      var CursorX = Event.clientX;
      var RelativeX = CursorX - SliderLeft;
      RelativeX = (RelativeX < 0) ? 0 : RelativeX;
      RelativeX = (RelativeX > SliderWidth) ? SliderWidth : RelativeX;
      
      var PercX = (RelativeX / SliderWidth) * 100;
      var MoveAction = this.DoMove(Handle, RelativeX, PercX);
      if (Coupled == true) {
         var NewCoupledPercX = this.ToPerc(MoveAction.Ref.attr('left')+MoveAction.Moved);
         this.DoMove(MoveAction.Ref, 0, NewCoupledPercX);
      }
      
   }
   
   Picker.prototype.DoMove = function(Handle, ProposedPosX, ProposedPercX) {
      var AllowedMinMax = Handle.get(0).limit();
      
      if (this.Nudge) {
         if (ProposedPercX > AllowedMinMax.right || ProposedPercX < AllowedMinMax.left) {
            // Nudge
            this.DoMove(AllowedMinMax.ref, ProposedPosX, ProposedPercX);
         }
      }
      
      var AllowedMinMax = Handle.get(0).limit();
      var RealPercX = ProposedPercX;
      RealPercX = (RealPercX < AllowedMinMax.left) ? AllowedMinMax.left : RealPercX;
      RealPercX = (RealPercX > AllowedMinMax.right) ? AllowedMinMax.right : RealPercX;
      var CurrentPercX = this.ToPerc($(Handle).css('left'));
      $(Handle).css('left',RealPercX+'%');
      
      // Resize slider
      var LeftPerc = this.ToPerc(this.HandleStart.css('left'))
      var RightPerc = this.ToPerc(this.HandleEnd.css('left'))
      var PercDiff = RightPerc - LeftPerc;
      this.Range.css('left',LeftPerc+'%');
      this.Range.css('width',PercDiff+'%');
      
      return {
         'Ref': AllowedMinMax.ref,
         'Moved': CurrentPercX - RealPercX
      }
   }
   
   Picker.prototype.MoveSlider = function(Handle, Event) {
      console.log('real:'+Event.clientX+' start:'+this.DownX);

      if (this.DownX !== false) {
         var SliderPos = this.Slider.offset();
         var SliderLeft = SliderPos.left;
         
         var RelAdjustedX = Event.clientX - (this.DownX - SliderLeft);
         console.log('sliderleft:'+SliderLeft+' adjusted:'+RelAdjustedX);
         
         Event.clientX = RelAdjustedX;
         this.DownX = false;
      }
      
      this.MoveHandle(this.HandleStart, Event);
      
   }

   Picker.prototype.LimitStayLeft = function(ReferenceElement) {
      return { 'left':0, 'right':this.ToPerc(ReferenceElement.css('left')), 'ref':ReferenceElement };
   }
   
   Picker.prototype.LimitStayRight = function(ReferenceElement) {
      return { 'left':this.ToPerc(ReferenceElement.css('left')), 'right':100, 'ref':ReferenceElement };
   }
   
   Picker.prototype.ToPerc = function(X) {
      if (X.substr(-1,1) == '%') return parseFloat(X);
      return (parseInt(X) / this.SlideRail.width()) * 100;
   }

}