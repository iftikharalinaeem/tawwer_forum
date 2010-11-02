function CapabilityLoader() {

   CapabilityLoader.prototype.Prepare = function() {
      this.Hooks = {};

      this.RegisterHook("Canvas",['available','unavailable'],jQuery.proxy(this.CapableCanvas,this));
      
      jQuery(document).ready(jQuery.proxy(function(){
         this.Process();
      },this));
      
   }
   
   CapabilityLoader.prototype.RegisterHook = function(HookEvent, HookConditions, HookEvaluator) {
      HookEvent = HookEvent.toLowerCase();
      this.Hooks[HookEvent] = {};
      
      this.Hooks[HookEvent] = {
         'status':HookEvaluator,
         'events':[]
      };
      $(HookConditions).each(jQuery.proxy(function(i,el){
         this.Hooks[HookEvent]['events'][el] = [];
      },this));
   }
   
   CapabilityLoader.prototype.Process = function() {
      $(this.Hooks).each(jQuery.proxy(function(i,el){
         var PropName = '';
         for (property in el) {
            PropName = property;
            break;
         }
         
         this.Hooks[PropName].status = this.Hooks[PropName].status();
         this.ExecHooks(PropName, 'include');
         this.ExecHooks(PropName, 'exec');
      },this));
   }
   
   CapabilityLoader.prototype.ExecHooks = function(HookEvent, EventType) {
      var HookCondition = this.Hooks[HookEvent].status;
      
      $(this.Hooks[HookEvent]['events'][HookCondition]).each(jQuery.proxy(function(i,el){
         if (EventType != undefined && el.type != EventType) return;
         switch (el.type) {
            
            case 'exec':
            case 'execdelay':
               el.payload();
            break;
            
            case 'include':
               var head= document.getElementsByTagName('head')[0];
               var script= document.createElement('script');
               script.type= 'text/javascript';
               script.src= el.payload;
               script.onload= jQuery.proxy(function(){
                  this.VerifySource(HookEvent, el.payload);
               },this);
               head.appendChild(script);
            break;
            
            default:
               console.log("Unknown hook type '"+el.type+"' for hook '"+HookEvent+"'/'"+HookCondition+"'");
            break;
         }
      },this));
   }
   
   CapabilityLoader.prototype.VerifySource = function(HookEvent, LoadFile) {
      this.Hooks[HookEvent].verify--;
      if (this.Hooks[HookEvent].verify == 0)
         this.ExecHooks(HookEvent, 'execdelay');
   }
   
   CapabilityLoader.prototype.Hook = function(HookEvent, HookCondition, HookCallback, HookDelay) {
      HookEvent = HookEvent.toLowerCase();
      var EventType = HookDelay == true ? 'execdelay' : 'exec';
   
      if (this.Hooks[HookEvent] != undefined && this.Hooks[HookEvent]['events'][HookCondition] != undefined)
         this.Hooks[HookEvent]['events'][HookCondition].push({'type':EventType,'payload':HookCallback});
   }
   
   CapabilityLoader.prototype.LoadHook = function(HookEvent, HookCondition, HookLoadFile) {
      HookEvent = HookEvent.toLowerCase();
   
      if (this.Hooks[HookEvent] != undefined && this.Hooks[HookEvent]['events'][HookCondition] != undefined) {
         if (this.Hooks[HookEvent].verify == undefined)
            this.Hooks[HookEvent].verify = 0;
         
         this.Hooks[HookEvent]['events'][HookCondition].push({'type':'include','payload':HookLoadFile});
         this.Hooks[HookEvent].verify++;
      }
   }
   
   CapabilityLoader.prototype.CapableCanvas = function() {
      var CanCanvas = !!document.createElement('canvas').getContext;
      return (CanCanvas) ? 'available' : 'unavailable';
   }
   
}

var Loader = new CapabilityLoader();
Loader.Prepare();