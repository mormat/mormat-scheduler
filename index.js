(function($) {

	$(document).ready(function() {

		$('.mormat-scheduler-Scheduler').each(function() {
		
			var element = this;
			
			$.get( $(element).data('url'), function(events) {

				mormat_scheduler.bindScheduler(element, {
					events: events,
					draggable: false,
					enableOverlapping: true
				});
				
			});
		
		});

	});

})(jQuery);

