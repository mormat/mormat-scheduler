(function($) {

	$(document).ready(function() {

		$('[name="mormat_scheduler[jsonEvents]"]').each(function() {

			var manager = mormat_scheduler.buildEventsManager({
				type: 'hidden_input',
				element: this
			});
			
			var config = {
				events: manager.load(),
				onEventCreate: function(v) {
					manager.save(v);
				},
				onEventUpdate: function(v) {
					manager.save(v);
				},
				onEventDelete: function(v) {
					manager.delete(v);
				},
			}

			$('.mormat_scheduler_eventsManager').each(function() {
				mormat_scheduler.bindEventsManager(this, config);
			});

			

		});

	});

})(jQuery);

