if (!window.vanilla) {
   window.vanilla = function() { return this; }();
}
if (!window.vanilla.embeds) {
   window.vanilla.embeds = {};
}
window.vanilla.embed = function(host) {
   
   var scripts = document.getElementsByTagName('script'),
      id = Math.floor((Math.random()) * 100000).toString(),
      embedUrl = window.location.href.split('#')[0],
      currentPath = window.location.hash.substr(1),
      jsPath = '/plugins/embedvanilla/remote.js';
      
   if (typeof(host) == 'undefined') {
      host = '';
      for (i = 0; i < scripts.length; i++) {
         if (scripts[i].src.indexOf(jsPath) > 0) {
            host = scripts[i].src;
            host = host.replace('http://', '').replace('https://', '');
            host = host.substr(0, host.indexOf(jsPath));
         }
      }
   }
      
   window.vanilla.embeds[id] = this;
   if (window.postMessage) {
      function onMessage(e) {
         var message = e.data.split(':');
         var frame = document.getElementById('vanilla'+id);
         if (frame.contentWindow != e.source)
            return;
         processMessage(message);
      }
      if (window.addEventListener)
         window.addEventListener("message", onMessage, false);
      else
         window.attachEvent("onmessage", onMessage);
   } else {
      var messageId = null;
      setInterval(function() {
         try {
            var hash = window.frames['vanilla'+id].frames.messageFrame.location.hash.substr(6);
         } catch(e) {
            return;
         }

         var message = hash.split(':');
         var newMessageId = message[0];
         if (newMessageId == messageId)
            return;
            
         messageId = newMessageId;
         message.splice(0, 1);
         processMessage(message);
      }, 300);
   }


   if ("onhashchange" in window) {
      if (window.addEventListener)
         window.addEventListener("hashchange", checkHash, false);
      else
         window.attachEvent("onhashchange", checkHash);
   } else {
      setInterval(checkHash, 300);
   }

   function checkHash() {
      var path = window.location.hash.substr(1);
      if (path != currentPath) {
         currentPath = path;
         window.frames['vanilla'+id].location.replace(vanillaUrl(path));
      }
   }

   function findPos(id) {
      var node = document.getElementById(id),
         top = 0;
      if (node.offsetParent) {
         do {
            top += node.offsetTop;
         } while (node = node.offsetParent);
         return top;
      }
      return -1;
   }

   function findPosScroll(id) {
      var node = document.getElementById(id),
         top = 0,
         topScroll = 0;
      if (node.offsetParent) {
         do {
            top += node.offsetTop;
            topScroll += node.offsetParent ? node.offsetParent.scrollTop : 0;
         } while (node = node.offsetParent);
         return top - topScroll;
      }
      return -1;
   }

   processMessage = function(message) {
      if (message[0] == 'height') {
         setHeight(message[1]);
      } else if (message[0] == 'location') {
         current_path = window.location.hash.substr(1);
         if (current_path != message[1]) {
            current_path = message[1];
            location.href = embedUrl + "#" + current_path;
         }
      } else if (message[0] == 'unload') {
         if (window.attachEvent || findPosScroll('vanilla'+id) < 0)
            document.getElementById('vanilla'+id).scrollIntoView(true);
      } else if (message[0] == 'unembed') {
         document.location = 'http://' + host + window.location.hash.substr(1);
      }
   }

   function setHeight(height) {
      document.getElementById('vanilla'+id).style['height'] = height + "px";
   }

   function vanillaUrl(path) {
      var concat = path.indexOf('?') > -1 ? '&' : '?';
      return 'http://' + host + path + concat + 'remote=' + encodeURIComponent(embedUrl);
   }

   document.write('<iframe id="vanilla'+id+'" name="vanilla'+id+'" src="'+vanillaUrl(currentPath)+'" scrolling="no" frameborder="0" border="0" width="100%" height="1000" style="width: 100%; height: 1000px; border: 0; display: block;"></iframe>');
   return this;
};
try {
   if (window.location.hash.substr(0, 6) != "#poll:")
      window.vanilla.embed();
} catch(e) {
   document.write("<div style=\"padding: 10px; font-size: 12px; font-family: 'lucida grande'; background: #fff; color:#000\";>Failed to embed Vanilla: " + e + "</div>");
}