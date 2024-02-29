(function($) {

	$(document).ready(function() {

		$('.mormat-scheduler-Scheduler').each(function() {
		
			var props = {
				events: $(this).html(),
				editable: false,
				draggable: false
			}
			
			mormat_standalone_scheduler.renderScheduler(this, props);
		
		});

	});

})(jQuery);

