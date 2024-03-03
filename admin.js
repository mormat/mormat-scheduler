(function($) {

	$(document).ready(function() {

		$('[name="mormat_scheduler_events_tsv"]').each(function() {

			$(this).hide();

			var props = {
				targetElement: this
			};
			
			mormat_standalone_scheduler.renderEventsList('.mormat_scheduler_eventsList', props);

		});

	});

})(jQuery);

