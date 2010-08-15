function Picker() {

   Picker.prototype.Attach = function(Options) {
      var self = this;
      self.PickerField = $('input.DateRange');
      self.PickerField.after('<a class="RangeToggle" href="#">' + self.PickerField.val() + '</a>');
      self.PickerToggle = $('a.RangeToggle');
      // self.PickerField.hide();
      self.PickerContainer = $('div.Picker');
      // Add the picker container if it wasn't already on the page somewhere
      if (self.PickerContainer.length == 0) {
         self.PickerToggle.after('<div class="Picker"></div>');
         self.PickerContainer = $('div.Picker');
      }
      self.PickerContainer.hide();
      self.PickerContainer.html(this.Settings.SliderHtml);
      self.PickerToggle.live('click', function() {
         if ($(this).hasClass('RangeToggleActive')) {
            $(this).removeClass('RangeToggleActive');
            self.PickerContainer.slideUp('fast');
         } else {
            $(this).addClass('RangeToggleActive');
            self.PickerContainer.slideDown('fast');
         }
         this.blur();
      });
      
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
         return false; // Return false so text selection fails.
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

jQuery(document).ready(function(){
   var GraphPicker = new Picker();
   GraphPicker.Attach();

});