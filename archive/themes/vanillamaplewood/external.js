jQuery(document).ready(function($) {
   var enc = function(html) {
      $('<div/>').text(html).html();
   };

   $.fn.discussions = function() {
      var item = this;

      var templateString = '<ul><% _.each(Discussions, function(d) { %> <li><a href="<%=d.Url%>"><%=d.Name%></a></li> <% }); %></ul>';
      var template = _.template(templateString);

      $.ajax({
         url: 'http://www.maplewoodonline.com/sov/vc/discussions.json?page=1-5&callback=?',
         dataType: 'json',
         success: function(data) {
            var html = template(data);
            $(item).html(html);
         }
      });
   }

   $('.Vanilla-Discissions').discussions();
});