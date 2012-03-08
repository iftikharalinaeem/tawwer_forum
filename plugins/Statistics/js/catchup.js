function Gdn_Statistics() {
   
   Gdn_Statistics.prototype.Prepare = function() {
   
      this.CatchingUp = false;
      this.Elements = ['registrations','discussions','comments'];
      this.Queue = [];
      this.Active = false;
   
      $('div.CatchupBlock input.RunCatchup').click(jQuery.proxy(function(event){
         this.Start();
      },this));
   }
   
   Gdn_Statistics.prototype.Start = function() {
      if (this.Queue.length) return;
      
      var ResultsBox = $('div.CatchupBlock div.CatchupResults');
      ResultsBox.html('Preparing to run catchup queue...');
      
      $.ajax({
         url: gdn.url('plugin/statistics/startcatchup'),
         dataType: 'json',
         success: jQuery.proxy(this.PreloadQueue, this)
      });
   }
   
   Gdn_Statistics.prototype.PreloadQueue = function(data, status, xhr) {
      var ResultsBox = $('div.CatchupBlock div.CatchupResults');
      ResultsBox.html('');
      $(this.Elements).each(jQuery.proxy(function(i,el){
         ResultsBox.append('<div class="Result" id="CatchupResult_'+el+'"><span class="CatchupTitle">'+el+'</span><span class="CatchupValue">0%</span></div>');
         this.Catchup(el);
      },this));
   }
   
   Gdn_Statistics.prototype.Catchup = function(Element) {
      if (Element != '' && Element != undefined) {
         this.Queue.push(Element);
      }
      
      // If we got nothing in queue, gtfo
      if (!this.Queue.length) return;
      
      // If we are not currently busy, get
      if (!this.Active) {
         
         var NextQueueItem = this.Queue.shift();
         this.Active = NextQueueItem;
         var self = this;

         $.ajax({
            url: gdn.url('plugin/statistics/execcatchupinit/'+this.Active),
            dataType: 'json',
            success: function() {
               $.ajax({
                  url: gdn.url('plugin/statistics/execcatchup/'+self.Active),
                  dataType: 'json',
                  success: jQuery.proxy(self.DoneCatchup, self)
               });

               self.Monitor();
            }
         });
      }
   }
   
   Gdn_Statistics.prototype.Monitor = function(data, status, xhr) {
      
      if (data == undefined) {
         if (this.Active == false) return;
         this.MonitorQuery(this.Active);
      } else {
         if (data.Progress) {
            var Element = data.Progress.Item;
            
            var ResultBox = $('div.CatchupBlock div.CatchupResults');
            ResultBox.find('div#CatchupResult_'+Element+' span.CatchupValue').html(data.Progress.Completion+'%');
            if (data.Progress.Completion >= 100) return;
            if (data.Progress.Item != this.Active) return;
         }
         
         
         var Exec = jQuery.proxy(function(){ this.Monitor(); }, this);
         setTimeout(Exec, 4000);
      }
   }
   
   Gdn_Statistics.prototype.MonitorQuery = function(Element) {
      $.ajax({
         url: gdn.url('plugin/statistics/monitor/'+Element),
         dataType: 'json',
         success: jQuery.proxy(this.Monitor, this)
      });
   }
   
   Gdn_Statistics.prototype.DoneCatchup = function(data, status, xhr) {
      // Final lookup to get last tick
      this.Monitor();
      
      this.Active = false;
      this.Catchup();
   }
}

var Catchup;
jQuery(document).ready(function(){
   Catchup = new Gdn_Statistics();
   Catchup.Prepare();
});