function Picker() {

   Picker.prototype.Attach = function(Options) {
      var PickerDiv = $('div.DateRange');
      this.PickerField = $('input.DateRangeActive');
      
      this.DownTarget = false;
      this.Slider = $('div.Slider');
      
      this.HandleStart = $('div.HandleStart');
      this.HandleEnd = $('div.HandleEnd');
      this.Range = $('div.SelectedRange');
      
      this.HandleStart.get(0).limit = jQuery.proxy(function(){
         return this.LimitStayLeft(this.HandleEnd);
      }, this);
      
      this.HandleEnd.get(0).limit = jQuery.proxy(function(){
         return this.LimitStayRight(this.HandleStart);
      }, this);
      
      $(document).bind('mousemove', jQuery.proxy(this.Move, this));
      
      $('div.SliderHandle').bind('mousedown', jQuery.proxy(function(e){
         this.DownTarget = $(e.target);
         console.log(this.ToPerc(this.DownTarget.css('left')));
      },this));
      
      $(document).bind('mouseup', jQuery.proxy(function(e){
         if (this.DownTarget == false) return;
         console.log(this.ToPerc(this.DownTarget.css('left')));
         this.DownTarget = false;
      },this));
   }
   
   Picker.prototype.Move = function(e) {
      if (this.DownTarget == false) return;
      
      var SliderPos = this.Slider.offset();
      var SliderWidth = this.Slider.width();
      var SliderLeft = SliderPos.left;
      var SliderRight = SliderPos.left + this.Slider.width();
      
      var CursorX = e.clientX;
      var RelativeX = CursorX - SliderLeft;
      RelativeX = (RelativeX < 0) ? 0 : RelativeX;
      RelativeX = (RelativeX > SliderWidth) ? SliderWidth : RelativeX;
      
      var PercX = (RelativeX / SliderWidth) * 100;
      this.DoMove(this.DownTarget, RelativeX, PercX);
   }
   
   Picker.prototype.DoMove = function(Handle, ProposedPosX, ProposedPercX) {
      var AllowedMinMax = Handle.get(0).limit();
      var RealPercX = ProposedPercX;
      RealPercX = (RealPercX < AllowedMinMax.left) ? AllowedMinMax.left : RealPercX;
      RealPercX = (RealPercX > AllowedMinMax.right) ? AllowedMinMax.right : RealPercX;
      $(Handle).css('left',RealPercX+'%');
      
      var LeftPerc = this.ToPerc(this.HandleStart.css('left'))
      var RightPerc = this.ToPerc(this.HandleEnd.css('left'))
      var PercDiff = RightPerc - LeftPerc;
      this.Range.css('left',LeftPerc+'%');
      this.Range.css('width',PercDiff+'%');
   }

   Picker.prototype.LimitStayLeft = function(ReferenceElement) {
      return { 'left':0, 'right':this.ToPerc(ReferenceElement.css('left')) };
   }
   
   Picker.prototype.LimitStayRight = function(ReferenceElement) {
      return { 'left':this.ToPerc(ReferenceElement.css('left')), 'right':100 };
   }
   
   Picker.prototype.ToPerc = function(X) {
      if (X.substr(-1,1) == '%') return parseFloat(X);
      return (parseInt(X) / this.Slider.width()) * 100;
   }

}

jQuery(document).ready(function(){
   var GraphPicker = new Picker();
   GraphPicker.Attach();

});