/**
 * Custom Sorting - Drag and Drop functionality for posts and taxonomies.
 *
 * @package HDAddons
 */

jQuery(function ($) {
	'use strict';

	// Check if customSortingVars is available (localized from PHP)
	const customSortingVars = window.customSortingVars || {};
	const nonce = customSortingVars.nonce || '';
	const ajaxUrl = customSortingVars.ajaxurl || window.ajaxurl;

	if (!nonce || !ajaxUrl) {
		console.warn('Custom Sorting: Nonce or Ajax URL not found. Sorting disabled.');
		return;
	}

	// Add cursor styling
	$('table.widefat tbody th, table.widefat tbody td').css('cursor', 'move');

	/**
	 * Helper function to maintain column widths during drag.
	 *
	 * @param {Event} event - The event object.
	 * @param {jQuery} ui - The jQuery UI object.
	 * @returns {jQuery} - The modified UI object.
	 */
	const sortableHelper = function (event, ui) {
		ui.children('td, th').each(function () {
			$(this).width($(this).width());
		});
		return ui;
	};

	/**
	 * Start handler - Style the dragged item.
	 *
	 * @param {Event} event - The event object.
	 * @param {jQuery} ui - The jQuery UI object.
	 */
	const sortableStart = function (event, ui) {
		ui.item.css({
			'background-color': '#ffffff',
			outline: '1px solid #dfdfdf',
		});
		ui.item.children('td, th').css('border-bottom-width', '0');
	};

	/**
	 * Stop handler - Reset styles on the item.
	 *
	 * @param {Event} event - The event object.
	 * @param {jQuery} ui - The jQuery UI object.
	 */
	const sortableStop = function (event, ui) {
		ui.item.removeAttr('style');
		ui.item.children('td, th').css('border-bottom-width', '1px');
	};

	/**
	 * Sort handler - Match visible columns in placeholder.
	 *
	 * @param {Event} event - The event object.
	 * @param {jQuery} ui - The jQuery UI object.
	 */
	const sortableSort = function (event, ui) {
		ui.placeholder.find('td').each(function (key) {
			const helperTd = ui.helper.find('td').eq(key);
			$(this).toggle(helperTd.is(':visible'));
		});
	};

	/**
	 * Show loading spinner in the row.
	 *
	 * @param {jQuery} item - The row item.
	 */
	const showSpinner = function (item) {
		item.find('.check-column input').hide().after('<span class="spinner is-active" style="margin: 0 0 0 6px; float: none;"></span>');
	};

	/**
	 * Hide loading spinner and restore checkbox.
	 *
	 * @param {jQuery} item - The row item.
	 */
	const hideSpinner = function (item) {
		item.find('.check-column input').show();
		item.find('.check-column .spinner').remove();
	};

	/**
	 * Show a temporary admin notice.
	 *
	 * @param {string} type - 'error' or 'success'.
	 * @param {string} message - The message to display.
	 */
	const showNotice = function (type, message) {
		const cssClass = type === 'error' ? 'notice-error' : 'notice-success';
		const $notice = $('<div class="notice ' + cssClass + ' is-dismissible"><p>' + $('<span>').text(message).html() + '</p></div>');
		$notice.hide().prependTo('.wrap').slideDown(200);
		setTimeout(function () {
			$notice.slideUp(200, function () {
				$(this).remove();
			});
		}, 5000);
	};

	/**
	 * Disable sortable during AJAX request.
	 */
	const disableSortable = function () {
		$('table.widefat tbody th, table.widefat tbody td').css('cursor', 'default');
		$('table.widefat tbody').sortable('disable');
	};

	/**
	 * Enable sortable after AJAX request.
	 */
	const enableSortable = function () {
		$('table.widefat tbody th, table.widefat tbody td').css('cursor', 'move');
		$('table.widefat tbody').sortable('enable');
	};

	/**
	 * Fix alternating row colors after reorder.
	 */
	const fixAlternateRows = function () {
		$('table.widefat tbody tr').each(function (index) {
			$(this).toggleClass('alternate', index % 2 === 0);
		});
	};

	/**
	 * Create update handler for sortable.
	 *
	 * @param {string} action - The AJAX action name.
	 * @returns {Function} - The update handler function.
	 */
	const createUpdateHandler = function (action) {
		return function (event, ui) {
			disableSortable();
			showSpinner(ui.item);

			// Sorting via AJAX with nonce
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				data: {
					action: action,
					order: $('#the-list').sortable('serialize'),
					nonce: nonce,
				},
				success: function (response) {
					hideSpinner(ui.item);
					enableSortable();

					if (!response.success) {
						showNotice('error', response.data || 'Sorting update failed.');
					}
				},
				error: function (xhr, status, error) {
					hideSpinner(ui.item);
					enableSortable();
					showNotice('error', 'Connection error: ' + (error || 'Please try again.'));
				},
			});

			// Fix alternating row colors
			fixAlternateRows();
		};
	};

	// Common sortable options
	const sortableOptions = {
		items: 'tr:not(.inline-edit-row)',
		cursor: 'move',
		axis: 'y',
		containment: 'table.widefat',
		scrollSensitivity: 40,
		helper: sortableHelper,
		start: sortableStart,
		stop: sortableStop,
		sort: sortableSort,
	};

	// Initialize sortable for posts and pages
	$('table.posts #the-list, table.pages #the-list').sortable({
		...sortableOptions,
		update: createUpdateHandler('update-menu-order'),
	});

	// Initialize sortable for taxonomies (tags, categories, etc.)
	$('table.tags #the-list').sortable({
		...sortableOptions,
		update: createUpdateHandler('update-menu-order-tags'),
	});
});
