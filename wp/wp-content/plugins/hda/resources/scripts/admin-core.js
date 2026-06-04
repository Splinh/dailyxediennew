/**
 * HDA Admin Scripts
 *
 * Entry point — global admin pages (all admin screens).
 */

import '../styles/admin-core.scss';

jQuery(function ($) {
	// Notice dismiss handler
	$(document).on('click', '.notice-dismiss', function () {
		$(this)
			.closest('.notice.is-dismissible')
			.fadeOut(500, function () {
				$(this).remove();
			});
	});
});
