jQuery(document).ready(function($) {
   if (gdn.definition('SignedIn') == '0')
      return;
   
   var $bar = $("#SignoutWarning"), // id of the warning div
		$countdown = $('#CountDown', $bar), // span tag that will hold the countdown value
      idleTime = parseInt(gdn.definition('AutoSignoutTime', 30)),
		redirectAfter = 30, // number of seconds to wait before redirecting the user
		running = false, // var to check if the countdown is running
		timer; // reference to the setInterval timer so it can be stopped
   
   
   $.idleTimer(idleTime);
   
   $(document).bind("idle.idleTimer", function(){
      // if the user is idle and a countdown isn't already running
		if( $.data(document, 'idleTimer') === 'idle' && !running ){
			var counter = redirectAfter;
			running = true;
 
			// set inital value in the countdown placeholder
			$countdown.html( redirectAfter );
 
			// show the warning bar.
			$bar.slideDown();
 
			// create a timer that runs every second
			timer = setInterval(function(){
				counter -= 1;
 
				// if the counter is 0, redirect the user
				if(counter < 0){
               clearInterval(timer);
               
               $.post(
                  gdn.url('/entry/signout.json'),
                  function() { window.location.replace(gdn.url('/entry/autosignedout')); }
               );
				} else {
					$countdown.html( counter );
				};
			}, 1000);
		};
   });
   
   // if the continue link is clicked..
	$("a", $bar).click(function(){
 
		// stop the timer
		clearInterval(timer);
 
		// stop countdown
		running = false;
 
		// hide the warning bar
		$bar.slideUp();
 
		// ajax call to keep the server-side session alive
//		$.get( keepAliveURL );
 
		return false;
	});
})(jQuery);