(function($) {

	$(document).ready(function() {

		$('.mormat-scheduler-Scheduler').each(function() {
		
			console.log('mormat_scheduler', mormat_scheduler);
		
			var element = this;
			
			var props = {
				events: mormat_scheduler.utils['csv'].parseString($(element).html())
			}
			
			mormat_scheduler.renderScheduler(element, props);
		
		});

	});

})(jQuery);

