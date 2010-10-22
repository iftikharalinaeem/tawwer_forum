<?php if (!defined('APPLICATION')) exit(); ?>
document.write('<div id="VanillaContainer"></div>');
<?php include(PATH_PLUGINS.'/EmbedVanilla/js/easyXDM.min.js'); ?>
var webroot = "<?php echo Gdn_Url::WebRoot(TRUE); ?>";
socket = new easyXDM.Socket({
   local: "transporthelper.html",
   remote: webroot+"/plugins/EmbedVanilla/transport.html",
   remoteHelper: webroot+"/plugins/EmbedVanilla/transporthelper.html",
   props: {style:{width:"100%"}},
   container: "VanillaContainer",
   onMessage: function(message, origin) {
      if (message.indexOf('height:') == 0) {
         this.container.getElementsByTagName("iframe")[0].style.height = message.substr(7) + "px";
      } else {
         var path = message;
         var newlocation = document.location.toString();
         if (newlocation.indexOf('#') >= 0)
            newlocation = newlocation.substring(0, newlocation.indexOf('#'));
            
         window.location.hash = '#' + path;
         // socket.postMessage(path);
      }
   },
   onReady: function() {
      socket.postMessage(document.location.hash.substr(1));
   }

});

function hashChanged(hash) {
   socket.postMessage(hash.substr(1));
}
if ("onhashchange" in window) { 
   window.onhashchange = function () {
      hashChanged(window.location.hash);
   }
} else {
   var storedHash = window.location.hash;
   window.setInterval(function () {
      if (window.location.hash != storedHash) {
         storedHash = window.location.hash;
         hashChanged(storedHash);
      }
   }, 100);
}
