$(document).ready(function() {
   var frequency = gdn.definition('WhosOnlineFrequency') * 1000;
   if (frequency <= 0)
      return;
   
   var gettingOnline = 0;
   
	function GetOnline() {
      if (!gdn.focused)
         return;
      
		var url = gdn.url('/plugin/imonline');
      if (gettingOnline > 0)
         return;
		gettingOnline++;
      
		$.ajax({
			url: url,
			global: false,
			type: "GET",
			data: null,
			dataType: "html",
			success: function(Data){
				$("#WhosOnline").replaceWith(Data);
			},
         complete: function() {
            gettingOnline--;
         }
		});
	}   

	window.setInterval(GetOnline, frequency);
});


