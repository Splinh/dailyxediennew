/**
 * HD AI Classic Settings Dashboard.
 */

import '../styles/admin.scss';

(function ($) {
	'use strict';

	// Bail if localized data is missing.
	if (typeof window.hdacAdminData === 'undefined') {
		return;
	}

	const { nonce, settings, i18n } = window.hdacAdminData;

	$(function () {
		const $form = $('#hdac-settings-form');
		const $tokenInput = $('#hdac_consumer_token');
		const $tempInput = $('#hdac_temperature');
		const $titleInput = $('#hdac_max_tokens_title');
		const $excerptInput = $('#hdac_max_tokens_excerpt');
		const $contentInput = $('#hdac_max_tokens_content');
		const $imageInput = $('#hdac_max_tokens_image');
		const $formatSelect = $('#hdac_content_format');
		const $statusBadge = $('#hdac-connection-status');
		const $statusText = $statusBadge.find('.status-text');
		const $saveStatus = $('#hdac-save-status');

		// Populate initial values.
		if (settings.consumer_token) {
			$tokenInput.val(settings.consumer_token);
		}
		$tempInput.val(settings.temperature);
		$titleInput.val(settings.max_tokens_title);
		$excerptInput.val(settings.max_tokens_excerpt);
		$contentInput.val(settings.max_tokens_content);
		$imageInput.val(settings.max_tokens_image);
		$formatSelect.val(settings.content_format);

		// Check features toggles
		if (Array.isArray(settings.features_enabled)) {
			settings.features_enabled.forEach(feat => {
				$form.find(`input[name="features_enabled[]"][value="${feat}"]`).prop('checked', true);
			});
		}

		// Tab navigation.
		$('.nav-tab-wrapper').on('click', '.nav-tab', function (e) {
			e.preventDefault();
			const $tab = $(this);
			const target = $tab.data('tab');

			// Update tabs UI
			$tab.addClass('nav-tab-active').siblings().removeClass('nav-tab-active');

			// Update contents UI
			$('.tab-content').prop('hidden', true);
			$(`#tab-${target}`).prop('hidden', false);
		});


		// Test connection.
		$('#hdac-test-connection').on('click', function (e) {
			e.preventDefault();

			$statusBadge.removeClass('is-success is-error').addClass('is-neutral');
			$statusText.text(i18n.connecting);

			wp.apiFetch({
				path: 'hd-ai-classic/v1/settings/test-connection',
				method: 'POST',
				headers: {
					'X-WP-Nonce': nonce
				},
				data: {
					consumer_token: $tokenInput.val()
				}
			}).then(response => {
				if (response.success) {
					$statusBadge.removeClass('is-neutral is-error').addClass('is-success');
					$statusText.text(i18n.connected);
				} else {
					$statusBadge.removeClass('is-neutral is-success').addClass('is-error');
					$statusText.text(response.message || i18n.connError);
				}
			}).catch(err => {
				$statusBadge.removeClass('is-neutral is-success').addClass('is-error');
				$statusText.text(err.message || i18n.connError);
			});
		});

		// Save settings.
		$form.on('submit', function (e) {
			e.preventDefault();

			// Gather features enabled
			const featuresEnabled = [];
			$form.find('input[name="features_enabled[]"]:checked').each(function () {
				featuresEnabled.push($(this).val());
			});

			const payload = {
				consumer_token: $tokenInput.val(),
				temperature: parseFloat($tempInput.val()),
				max_tokens_title: parseInt($titleInput.val(), 10),
				max_tokens_excerpt: parseInt($excerptInput.val(), 10),
				max_tokens_content: parseInt($contentInput.val(), 10),
				max_tokens_image: parseInt($imageInput.val(), 10),
				content_format: $formatSelect.val(),
				features_enabled: featuresEnabled
			};

			wp.apiFetch({
				path: 'hd-ai-classic/v1/settings',
				method: 'POST',
				headers: {
					'X-WP-Nonce': nonce
				},
				data: payload
			}).then(response => {
				if (response.success) {
					// Show success notice
					showNotice(i18n.saveSuccess, 'success');
					$saveStatus.removeClass('is-error').addClass('is-success').text(i18n.saveSuccess);
					// If token was changed, make sure input updates if masked response
					if (response.settings && response.settings.consumer_token) {
						$tokenInput.val(response.settings.consumer_token);
					}
				} else {
					showNotice(i18n.saveError, 'error');
					$saveStatus.removeClass('is-success').addClass('is-error').text(i18n.saveError);
				}
			}).catch(err => {
				showNotice(err.message || i18n.saveError, 'error');
				$saveStatus.removeClass('is-success').addClass('is-error').text(err.message || i18n.saveError);
			});
		});

		function showNotice(msg, type = 'success') {
			// Remove existing notice
			$('.hdac-settings-wrap .notice').remove();

			const notice = document.createElement('div');
			notice.className = `notice notice-${type} is-dismissible`;

			const paragraph = document.createElement('p');
			paragraph.textContent = msg;
			notice.appendChild(paragraph);

			const heading = document.querySelector('.hdac-settings-wrap h1');
			if (heading) {
				heading.insertAdjacentElement('afterend', notice);
			}

			// Init WP dismissibles
			$(document).trigger('wp-updates-notice-added');
		}

	});

})(jQuery);
