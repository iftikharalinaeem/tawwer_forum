/**
 * Valentines Plugin - Event JS
 * 
 */

String.prototype.toFormatTime = function () {
   sec_numb    = parseInt(this);
   var hours   = Math.floor(sec_numb / 3600);
   var minutes = Math.floor((sec_numb - (hours * 3600)) / 60);
   var seconds = sec_numb - (hours * 3600) - (minutes * 60);

   if (hours   < 10) {hours   = "0"+hours;}
   if (minutes < 10) {minutes = "0"+minutes;}
   if (seconds < 10) {seconds = "0"+seconds;}
   var time    = hours+':'+minutes+':'+seconds;
   return time;
}

jQuery(document).ready(function($) {
   
   // Show timer for user
   var TimerExpiry = gdn.definition('ValentinesExpiry', 'none');
   if (TimerExpiry && TimerExpiry != 'none') {
      TimerExpiry = parseInt(TimerExpiry);

      var TimerDate = new Date();
      var ExpiryTime = TimerDate.getTime() + (TimerExpiry*1000);
      $(document).data('ValentinesExpiryTime', ExpiryTime);

      RefreshValentines();
   }
   
   $('.Item.ArrowCache').each(function(i,el){
      var Item = $(el);
      var Cache = Item.find('.FallenCupid');
      var CacheLink = Cache.find('a.FallenCupidLink');
      var CacheID = CacheLink.attr('rel');
      if (!CacheID) return;
      
      CacheLink.on('click', function(){
         $.ajax({
            url: gdn.url('/plugin/valentines/cache/'+CacheID),
            dataType: 'json',
            method: 'GET',
            success: function(json) {
               json = $.postParseJson(json);
               var processedTargets = false;
               // If there are targets, process them
               if (json.Targets && json.Targets.length > 0)
                  gdn.processTargets(json.Targets);
               
               gdn.inform(json);
            }
         });
      });
   });
   
   function RefreshValentines() {
      
      EndValentines();
      var CurrentInterval = setInterval(UpdateValentines,1000);
      $(document).data('ValentinesExpiryInterval', CurrentInterval);
      
   }
   
   function UpdateValentines() {
      var TimerDate = new Date();
      var ExpiryTime = $(document).data('ValentinesExpiryTime');
      var ExpiryDelay = (ExpiryTime - TimerDate.getTime()) / 1000;
      if (ExpiryDelay < 0)
         return EndValentines();
      
      var ExpiryFormatTime = String(ExpiryDelay).toFormatTime();
      var ComplianceMessage = '<div class="Compliance" style="">Compliance in: <span style="color:#51CEFF;">'+ExpiryFormatTime+'</span></div>';
         
      var ValentinesTimerInform = $('#ValentinesTimer');
      if (!ValentinesTimerInform.length) {
         var ValentinesInform = {'InformMessages':[
            {
               'CssClass':             'ValentinesTimer Dismissable',
               'id':                   'ValentinesTimer',
               'DismissCallbackUrl':   gdn.url('/plugin/valentines/dismiss'),
               'Message':              ComplianceMessage
            }
         ]}
         gdn.inform(ValentinesInform);
         return;
      }
      //return;
      ValentinesTimerInform.find('.InformMessage .Compliance').html(ComplianceMessage);
      return;
   }
   
   function EndValentines() {
      var CurrentInterval = $(document).data('ValentinesExpiryInterval');
      if (CurrentInterval)
         clearInterval(CurrentInterval);
   }
   
});