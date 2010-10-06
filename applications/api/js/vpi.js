function VPIBase() {
   // Private variables.
   var _AppID = 0; // The application ID.
   var _Binds = []; // event bindings.
   var _Root = null; // location of html root.
   var _Url = "http://vanilla.local"; // The url of the application.

   // Private methods.

   // Call the event handlers for a given event.
   var _Event = function(type, arg1, arg2, arg3) {
      if (_Binds[type] != undefined) {
         var binds = _Binds[type];

         for (var i = 0; i < binds.length; i++) {
            var handler = binds[i];
            handler(arg1, arg2, arg3);
         }
      }
   };

   // Get a value from a collection.
   var _gv = function(collection, key, defaultValue) {
      if (collection[key] != undefined)
         return collection[key];
      return defaultValue;
   };

   var Root = function() {
      if (_Root == null) {
         // Create the root node.
         _Root = $("body").prepend('<div id="VPI_Root" style="display:none"></div>');
      }
      return _Root;
   };

   // Public declaration.
   return {
      // Get or set the application ID.
      AppID: function(value) {
         if (value)
            _AppID = value;
         return _AppID;
      },

      // Bind to an event.
      Bind: function(type, handler) {
         if (_Binds[type] == undefined)
            _Binds[type] = [handler];
         else
            _Binds[type].push(handler);
      },

      // Get the signin status of the user.
      Profile: function(callback) {
         if (callback == undefined) {
            callback = function(data) {
               _Event("sessionChange", data);
            };
         }
         $.getJSON(this.Url() + "/vpi/profile?callback=?", callback);
      },

      // Initialize the object.
      Init: function(options) {
         _AppID = _gv(options, "AppID", _AppID);
         _Url = _gv(options, "Url", _Url);
      },

      // Get or set the url of the api.
      Url: function(value) {
         if (value)
            _Url = value;
         return _Url;
      }
   }
}

if (!window.VPI)
   VPI = new VPIBase();

jQuery(document).ready(function($) {
   VPI.Init({
      AppID: gdn.definition("VPI_AppID")
   });

   VPI.Profile(function(data) { console.log(data); });
});