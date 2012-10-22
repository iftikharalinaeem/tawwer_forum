jQuery(document).ready(function() {
   
   // Define the webroot & path without domain
   var webroot = gdn.definition('WebRoot');
   var pathroot = gdn.definition('UrlFormat').replace('/{Path}', '').replace('{Path}', '');

   function setHeight() {
      var page = document.getElementById("EmbedContainer");
      if (page) {
         var height = page.scrollHeight || page.offsetHeight;
         parent.transport.postMessage('height:'+(height*1+10));
      }
   }
   window.onload = function() {
      setHeight();
   }

   // Redraw the grpah when the window is resized
   $(window).resize(function() {
      setHeight();
   });
   
   // hijack all anchors to see if they should go to "top" or be within the embed (ie. are they in Vanilla or not?)
   $('a').live('click', function() {
      var href = $(this).attr('href');
      var isHttp = href.substr(0, 7) == 'http://';
      if (isHttp && href.substr(0, webroot.length) != webroot) {
         document.location.top = href;
      } else {
         // Strip the path from the root folder of the app
         parent.transport.postMessage(isHttp ? href.substr(webroot.length) : href.substr(pathroot.length));
      }
      return false;
   });
   
});