jQuery(document).ready(function($) {
   if(!Array.indexOf){
	    Array.prototype.indexOf = function(obj){
	        for(var i=0; i<this.length; i++){
	            if(this[i]==obj){
	                return i;
	            }
	        }
	        return -1;
	    }
	}
   
   // Add the plus-minus icons.
   $('.CategoryList .Title').before('<a href="#" class="CollapseCategoryButton"><span>-</span></a> ');
   
   $('.CollapseCategoryButton').click(function(e) {
      e.preventDefault();
      e.stopPropagation();
      var $item = $(this).closest('.Item');
      var collapsed = $item.hasClass('Collapsed');
      setCollapsed($item, $(this), !collapsed);
   });
   
   var setCollapsed = function($item, $button, collapsed) {
      var id = $item.attr('id');
      
      // Expand/Collapse the item.
      if (collapsed) {
         $item.addClass('Collapsed');
         $button.html('<span>+</span>');
         
         if (collapsedCookie.indexOf(id) < 0)
            collapsedCookie.push(id);
      } else {
         $item.removeClass('Collapsed');
         $button.html('<span>-</span>');
         
         var index = collapsedCookie.indexOf(id);
         if (index >= 0) {
            collapsedCookie.splice(index, 1);
         }
      }
      
      // Expand/Collapse its children.
      setChildren($item);
      
      // Set the cookie.
      setCookie('Collapsed', collapsedCookie, 365);
   };
   
   var getCookie = function(name) {
      var nameEQ = '__vn'+name + "=";
      var ca = document.cookie.split(';');
      for(var i=0;i < ca.length;i++) {
         var c = ca[i];
         while (c.charAt(0)==' ') c = c.substring(1,c.length);
         if (c.indexOf(nameEQ) == 0) {
            var result = c.substring(nameEQ.length,c.length).split('.');
            return result;
         }
      }
      return [];
   };
   
   var setCookie = function(name, value, days) {
      var expires = "";
      value = value.join('.');
      
      if (days) {
         var date = new Date();
         date.setTime(date.getTime()+(days*24*3600000)); // milliseconds per hour
         expires = "; expires="+date.toGMTString();
      }
      document.cookie = '__vn'+name+"="+value+expires+"; path=/";
   };
   
   var setChildren = function($item, parentCollapsed) {
      var collapsed = $item.hasClass('Collapsed');
      var depth = getDepth($item);
      
      if (!depth)
         return;
      
      var $prev = null, $next = $item;
      for (i = 0; i < 100; i++) {
         var $next = $next.next('.Item');
         if ($next.length == 0)
            break;
         
         var nextDepth = getDepth($next);
         if (nextDepth == depth + 1) {
            if (collapsed || parentCollapsed)
               $next.hide();
            else
               $next.show();
         } else if (nextDepth > depth) {
            if ($prev) {
               $next = setChildren($prev, collapsed || parentCollapsed).prev('.Item');
               continue;
            } else {
               console.log('Problem!!!');
            }
         } else {
            break;
         }
         
         $prev = $next;
      }
      
      if ($next != $item)
         return $next;
   };
   
   var getDepth = function($item) {
      if ($item.hasClass('Depth1'))
         return 1;
      else if ($item.hasClass('Depth2'))
         return 2;
      else if ($item.hasClass('Depth3'))
         return 3;
      else if ($item.hasClass('Depth4'))
         return 4;
      else if ($item.hasClass('Depth5'))
         return 5;
      else if ($item.hasClass('Depth6'))
         return 6;
      else
         return false;
   }
   
   // Set the initial state of the page.
   var collapsedCookie = getCookie('Collapsed');
   
   // First add the appropriate css classes to the items.
   $('.CategoryList .Item').each(function() {
      var collapsed = collapsedCookie.indexOf($(this).attr('id')) >= 0;
      if (collapsed)
         $(this).addClass('Collapsed');
   });
   
   // Next expand/collapse all of the depth 1 items.
   $('.CategoryList .Item.Depth1').each(function() {
      setChildren($(this));
   });
   
});

