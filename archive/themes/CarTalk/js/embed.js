// Include this script where you want to embed the top 4 most popular discussions.
// This script requires jQuery.

document.write('<div id="vn_discussions_popular"></div>');

jQuery(document).ready(function($) {
   var url = 'http://community.cartalk.com/discussions/popular.json/1-4?callback=?';
   
   $.ajax({
     url: url,
     dataType: 'json',
     success: function(data) {
         var foo = 'bar';
         var result = '';
         
         for(i in data.Discussions) {
            d = data.Discussions[i];
            result += '<li><a href="'+d.Url+'">'+d.Name+'</a></li>';
         }
         
         result = '<ul>'+result+'</ul>';
         $('#vn_discussions_popular').html(result);
      }
   });
});