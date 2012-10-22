$(document).ready(function() {
 
  $('#slickbox').show();
  $.cookie('welcome', 'expanded');

  $('#slick-slidetoggle').click(function() {
    $('#slickbox').slideToggle(400);
    return false;
  });
  
  
  
  // COOKIES
	// welcome state
	var welcome = $.cookie('welcome');
	
	// Set the user's selection for the welcome
	if (welcome == 'expanded') {
		$('#slickbox').css("display","block");
	 	
	};
	
	if (welcome != 'expanded') {
		$('#slickbox').css("display","none");
	
	};
	
	
  
});