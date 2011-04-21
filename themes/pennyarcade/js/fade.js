$(document).ready(function() {
	$('.nav a').append('<span></span>').each(function () {
		var $span = $('> span', this).css('opacity', 0);
		$(this).hover(function () {
			$span.stop().fadeTo(175, 1);
		}, function () {
			$span.stop().fadeTo(1000, 0);
		});
	});
});