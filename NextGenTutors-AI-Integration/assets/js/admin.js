(function () {
	'use strict';

	document.addEventListener('submit', function (event) {
		var button = event.submitter;
		if (!button || !button.matches('[name="ngtai_event_action"][value="cancel"]')) {
			return;
		}
		if (!window.confirm('Cancel this delivery?')) {
			event.preventDefault();
		}
	});
}());
