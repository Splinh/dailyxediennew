/**
 * HDA Settings Page Scripts — Single entry for all settings page functionality.
 */

import '../styles/tailwind/index.css';
import '../styles/settings.scss';
import select2 from 'select2';
import 'select2/dist/css/select2.min.css';
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import 'tippy.js/animations/shift-away-subtle.css';
import './utils/jquery-plugins.js';

const $ = window.jQuery;
select2($);

// ── Helpers ──────────────────────────────────────

/** AJAX POST helper — returns parsed JSON promise. */
const ajaxPost = (data) => {
	const fd = new FormData();
	Object.entries(data).forEach(([k, v]) => (Array.isArray(v) ? v.forEach((i) => fd.append(`${k}[]`, i)) : fd.append(k, v)));
	return fetch(ajaxurl, { method: 'POST', body: fd }).then((r) => r.json());
};

/** Fade-out then remove a table row with collapse animation. */
const fadeOutRow = (row, delay = 250) => {
	row.style.transition = 'opacity 0.25s ease';
	row.style.opacity = '0';
	setTimeout(() => {
		row.querySelectorAll('td').forEach((td) => {
			td.style.cssText = 'transition:padding .25s,height .25s;padding:0;height:0;overflow:hidden;line-height:0;font-size:0;border:none';
		});
		setTimeout(() => row.remove(), 300);
	}, delay);
};

// ── Visibility Toggles ──────────────────────────

function initOtpSettings() {
	const radios = document.querySelectorAll('input[name="hda_login_security[otp_mode]"]');
	const gw = document.getElementById('otp_gateway');
	if (!radios.length) return;
	const smsOnly = document.querySelectorAll('.otp-sms-only');
	const otpOnly = document.querySelectorAll('.otp-enabled-only');
	const gwConfigs = document.querySelectorAll('.otp-gateway-config');
	const update = () => {
		const mode = document.querySelector('input[name="hda_login_security[otp_mode]"]:checked')?.value || 'disabled';
		const gateway = gw?.value || 'telegram';
		const isOtp = ['email', 'sms', 'totp'].includes(mode);
		smsOnly.forEach((el) => (el.style.display = mode === 'sms' ? '' : 'none'));
		otpOnly.forEach((el) => (el.style.display = isOtp || mode === 'magic_link' ? '' : 'none'));
		gwConfigs.forEach((el) => {
			el.style.display = mode === 'sms' && el.classList.contains('gateway-' + gateway) ? '' : 'none';
		});
	};
	radios.forEach((r) => r.addEventListener('change', update));
	gw?.addEventListener('change', update);
	update();
}

// ── CodeMirror ───────────────────────────────────

function initCodeMirror() {
	if (typeof codemirror_settings === 'undefined') return;
	const init = (els, settings) =>
		els.forEach((el) => {
			if (el.CodeMirror) return;
			const opts = settings ? { ...settings } : {};
			opts.codemirror = { ...opts.codemirror, indentUnit: 3, tabSize: 3, autoRefresh: true };
			el.CodeMirror = wp.codeEditor.initialize(el, opts);
		});
	init(document.querySelectorAll('.codemirror_css'), codemirror_settings.codemirror_css);
	init(document.querySelectorAll('.codemirror_html'), codemirror_settings.codemirror_html);
}

// ── Select2 ──────────────────────────────────────

function initSelect2($) {
	const octet = '(25[0-5]|2[0-4]\\d|1\\d{2}|\\d{1,2})';
	const ip4 = `${octet}\\.${octet}\\.${octet}\\.${octet}`;
	const ipRe = new RegExp(`^${ip4}$`);
	const cidrRe = new RegExp(`^${ip4}/(\\d|[1-2]\\d|3[0-2])$`);
	const rangeRe = new RegExp(`^${ip4}-(\\d|[1-9]\\d|1\\d{2}|2[0-4]\\d|25[0-5])$`);
	const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

	const isValidIP = (v) => {
		if (ipRe.test(v) || cidrRe.test(v)) return true;
		if (rangeRe.test(v)) {
			const [s, e] = v.split('-');
			return +s.split('.')[3] < +e;
		}
		return false;
	};

	const base = { multiple: true, allowClear: true, width: '100%', dropdownAutoWidth: true };
	const initS2 = (sel, opts) =>
		$(sel).each(function () {
			$(this).select2({ ...base, placeholder: $(this).attr('placeholder'), ...opts });
		});

	initS2('.select2-multiple');
	initS2('.select2-tags', { tags: true });
	initS2('.select2-ips', {
		tags: true,
		createTag: (p) => {
			const t = p.term.trim();
			return isValidIP(t) ? { id: t, text: t } : null;
		},
	});
	initS2('.select2-emails', {
		tags: true,
		createTag: (p) => {
			const t = p.term.trim();
			return emailRe.test(t) ? { id: t, text: t } : null;
		},
	});
}

// ── Filter Tabs ──────────────────────────────────

function initFilterTabs($) {
	$('.filter-tabs').each(function () {
		const $el = $(this),
			$nav = $el.find('.tabs-nav'),
			$tabs = $nav.find('.menu-group-item > [data-tab]'),
			$panels = $el.find('.tabs-content').children('.tabs-panel');
		const activateTab = (slug) => {
			$tabs.removeClass('current');
			$panels.removeClass('show');
			let $tab = slug ? $tabs.filter(`[data-tab="${slug}"]`) : $(),
				idx = $tabs.index($tab);
			if (idx < 0) {
				idx = 0;
				$tab = $tabs.eq(0);
			}
			$tab.addClass('current');
			$panels.eq(idx).addClass('show');
			return $tab.attr('data-tab') || '';
		};
		const initial = window.location.hash.slice(1),
			active = activateTab(initial);
		if (initial && initial !== active) window.history.replaceState(null, null, window.location.pathname + window.location.search);
		$nav.on('click', '[data-tab]', function (e) {
			e.preventDefault();
			const slug = $(this).attr('data-tab');
			window.location.hash = slug;
			activateTab(slug);
			$('html, body').animate({ scrollTop: $el.offset().top - $('header').outerHeight() }, 300);
		});
		$(window).on('hashchange', () => activateTab(window.location.hash.slice(1) || ''));
	});
}

// ── Media Upload ─────────────────────────────────

function initMediaUpload($) {
	$(document).on('click', '.js-media-select', function (e) {
		e.preventDefault();
		const $w = $(this).closest('.hda-media-upload'),
			$input = $w.find('.hda-media-value'),
			$preview = $w.find('.hda-media-preview'),
			$rm = $w.find('.js-media-remove');
		const previewSize = $w.data('preview') || 'medium';
		let libType = 'image';
		const raw = $w.data('library');
		if (raw) {
			const t = String(raw)
				.split(',')
				.map((s) => s.trim())
				.filter(Boolean);
			libType = t.length > 1 ? t : t[0] || 'image';
		}
		const frame = wp.media({ title: $w.data('title') || 'Select Image', button: { text: $w.data('button') || 'Use this image' }, multiple: false, library: { type: libType } });
		frame.on('select', () => {
			const a = frame.state().get('selection').first().toJSON();
			$input.val(a.id);
			$preview.html('<img src="' + (a.sizes?.[previewSize]?.url || a.sizes?.medium?.url || a.sizes?.thumbnail?.url || a.url) + '" alt="preview">').removeClass('empty');
			$rm.removeClass('hidden');
		});
		frame.open();
	});
	$(document).on('click', '.js-media-remove', function (e) {
		e.preventDefault();
		const $w = $(this).closest('.hda-media-upload');
		$w.find('.hda-media-value').val('');
		$w.find('.hda-media-preview').html('<span class="dashicons dashicons-format-image"></span>').addClass('empty');
		$(this).addClass('hidden');
	});
}

// ── DB Optimizer ─────────────────────────────────

function initDbOptimizer() {
	const btn = document.getElementById('hda-db-optimize-btn'),
		statusEl = document.getElementById('hda-db-optimize-status');
	if (!btn) return;
	const nonce = window.hdaDbOptimizer?.nonce || '';

	btn.addEventListener('click', () => {
		btn.disabled = true;
		statusEl.textContent = window.hdaDbOptimizer?.i18n?.optimizing || 'Optimizing...';
		statusEl.style.color = '#0073aa';
		ajaxPost({ action: 'hda_db_optimize', _nonce: nonce })
			.then((data) => {
				if (data.success) {
					const entries = Object.entries(data.data.results);
					statusEl.textContent = `✅ ${entries.map(([k, v]) => `${k}: ${v}`).join(', ')}`;
					statusEl.style.color = '#46b450';
					entries.forEach(([key]) => {
						const countEl = document.querySelector(`.hda-db-count[data-task="${key}"]`);
						if (countEl) {
							countEl.textContent = '0';
							const strong = countEl.closest('strong');
							if (strong) strong.style.color = '#46b450';
						}
					});
				} else {
					statusEl.textContent = `❌ ${data.data?.message || 'Error'}`;
					statusEl.style.color = '#d63638';
				}
				btn.disabled = false;
			})
			.catch(() => {
				statusEl.textContent = '❌ Request failed';
				statusEl.style.color = '#d63638';
				btn.disabled = false;
			});
	});
}

// ── File Integrity ───────────────────────────────

function initFileIntegrity() {
	const buttons = document.querySelectorAll('.hda-fi-scan-btn');
	if (!buttons.length) return;
	const { nonce = '', i18n = {} } = window.hdaFileIntegrity || {};

	buttons.forEach((btn) =>
		btn.addEventListener('click', () => {
			if (btn.disabled) return;
			const action = btn.dataset.scan;
			if (!action) return;
			btn.disabled = true;
			const prog = document.querySelector(`.hda-fi-progress[data-progress-for="${action}"]`);
			prog?.classList.add('is-active');
			ajaxPost({ action, _nonce: nonce })
				.then((data) => {
					if (data.success) {
						window.location.reload();
						return;
					}
					btn.disabled = false;
					prog?.classList.remove('is-active');
					const c = document.getElementById('hda-fi-toast');
					if (c) {
						const d = document.createElement('div');
						d.className = 'notice notice-error is-dismissible';
						d.innerHTML = `<p>${data.data?.message || i18n.error || 'Error'}</p>`;
						c.appendChild(d);
						setTimeout(() => {
							d.style.transition = 'opacity .3s';
							d.style.opacity = '0';
							setTimeout(() => d.remove(), 300);
						}, 6000);
					}
				})
				.catch(() => {
					btn.disabled = false;
					prog?.classList.remove('is-active');
				});
		}),
	);
}

// ── Settings Form (AJAX submit + toast) ──────────

const SVG_CHECK =
	'<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
const SVG_X =
	'<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
const SVG_CLOSE =
	'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

function showToast($, message, type = 'success', autoHide = true) {
	let $c = $('#hda-toast-container');
	if (!$c.length) $c = $('<div id="hda-toast-container">').appendTo('body');
	const ok = type !== 'error';
	const $t = $('<div>', { class: `hda-toast hda-toast--${ok ? 'success' : 'error'}` })
		.append($('<span>', { class: 'hda-toast__icon' }).html(ok ? SVG_CHECK : SVG_X))
		.append($('<span>', { class: 'hda-toast__message' }).text(message))
		.append($('<button>', { type: 'button', class: 'hda-toast__close', 'aria-label': 'Close' }).html(SVG_CLOSE));
	$c.append($t);
	requestAnimationFrame(() => $t.addClass('hda-toast--show'));
	const remove = () => {
		$t.removeClass('hda-toast--show');
		setTimeout(() => $t.remove(), 300);
	};
	$t.find('.hda-toast__close').on('click', remove);
	if (autoHide) setTimeout(remove, 4000);
}

function initSettingsForm($) {
	$(document).on('submit', '#_settings_form', function (e) {
		e.preventDefault();
		const $f = $(this),
			$data = $f.serializeObject(),
			$btn = $f.find('button[name="_submit_settings"]'),
			html = $btn.html();
		$btn.prop('disabled', true).html('<span class="ajax-loader">&nbsp;</span>');
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			dataType: 'json',
			data: { action: 'submit_settings', _data: $data, _ajax_nonce: $f.find('[name="_wpnonce"]').val(), _wp_http_referer: $f.find('[name="_wp_http_referer"]').val() },
		})
			.done((res) => {
				if (res?.data?.message) showToast($, res.data.message, res.data.type || 'success', res.data.autoHide);
				const h = window.location.hash;
				const reload =
					!h || ['#global_setting_settings', '#custom_css_settings', '#custom_script_settings'].includes(h) || (h === '#custom_sorting_settings' && $data.order_reset !== undefined);
				if (reload) setTimeout(() => window.location.reload(), 1500);
			})
			.fail((xhr) => {
				let msg = 'An error occurred';
				try {
					const r = JSON.parse(xhr.responseText);
					if (r?.data?.message) msg = r.data.message;
				} catch {}
				showToast($, msg, 'error', false);
			})
			.always(() => $btn.prop('disabled', false).html(html));
	});
}

// ── WAF Country Selector ─────────────────────────

function initWaf() {
	const select = document.getElementById('hda-country-select'),
		addBtn = document.getElementById('hda-add-country-btn'),
		list = document.getElementById('hda-blocked-list');
	if (!select || !addBtn || !list) return;

	const TEXT = {
		block_selected: { heading: 'Blocked Countries', placeholder: 'Select a country to block...', btn: 'Add to Blocklist', empty: 'No countries blocked.' },
		allow_selected: { heading: 'Allowed Countries', placeholder: 'Select a country to allow...', btn: 'Add to Allowlist', empty: 'No countries in allowlist. All traffic will be blocked!' },
	};
	const getMode = () => document.querySelector('input[name="country_mode"]:checked')?.value || 'block_selected';
	const getText = () => TEXT[getMode()] || TEXT.block_selected;

	const updateModeText = () => {
		const t = getText();
		const set = (id, val) => {
			const el = document.getElementById(id);
			if (el) el.textContent = val;
		};
		set('hda-country-heading', t.heading);
		set('hda-country-placeholder', t.placeholder);
		set('hda-country-btn-text', t.btn);
		const em = list.querySelector('.empty-msg');
		if (em) em.textContent = t.empty;
	};
	document.querySelectorAll('input[name="country_mode"]').forEach((r) => r.addEventListener('change', updateModeText));

	addBtn.addEventListener('click', () => {
		const code = select.value;
		if (!code) return;
		const opt = select.options[select.selectedIndex],
			name = opt.textContent.replace(/\s*\([A-Z]{2}\)\s*$/, '');
		list.querySelector('.empty-msg')?.remove();
		if (list.querySelector(`input[value="${code}"]`)) return;
		const li = document.createElement('li');
		li.className = 'blocked-item';
		li.innerHTML = `<img src="https://flagcdn.com/16x12/${code.toLowerCase()}.png" width="16" height="12" alt=""><span>${name}</span><input type="hidden" name="blocked_countries[]" value="${code}"><button type="button" class="remove-country" aria-label="Remove">&times;</button>`;
		list.appendChild(li);
		opt.disabled = true;
		select.value = '';
	});

	list.addEventListener('click', (e) => {
		const btn = e.target.closest('.remove-country');
		if (!btn) return;
		const li = btn.closest('.blocked-item'),
			code = li.querySelector('input').value;
		li.remove();
		const opt = select.querySelector(`option[value="${code}"]`);
		if (opt) opt.disabled = false;
		if (!list.querySelector('.blocked-item')) {
			const em = document.createElement('li');
			em.className = 'empty-msg';
			em.textContent = getText().empty;
			list.appendChild(em);
		}
	});
}

// ── Cron Manager ─────────────────────────────────

function initCronManager() {
	const statusEl = document.getElementById('hda-cron-status'),
		table = document.getElementById('hda-cron-table');
	if (!table) return;
	const nonce = window.hdaCronManager?.nonce || '';

	const showStatus = (msg, color) => {
		if (statusEl) {
			statusEl.textContent = msg;
			statusEl.style.color = color || '#666';
		}
	};
	const setLoading = (btn, on) => {
		btn.disabled = on;
		const ic = btn.querySelector('.dashicons');
		if (!ic) return;
		if (on) {
			btn._ic = ic.className;
			ic.className = 'dashicons dashicons-update hda-spin';
			btn.style.opacity = '.7';
		} else {
			if (btn._ic) ic.className = btn._ic;
			btn.style.opacity = '';
		}
	};
	const flash = (row, msg, ok) => {
		row.style.transition = 'background-color .3s';
		row.style.backgroundColor = ok ? '#ecf7ed' : '#fcf0f1';
		showStatus(`${ok ? '✅' : '❌'} ${msg}`, ok ? '#46b450' : '#d63638');
		if (ok) setTimeout(() => (row.style.backgroundColor = ''), 2000);
	};

	// Unified click handler for run + delete
	table.addEventListener('click', (e) => {
		const runBtn = e.target.closest('.hda-cron-run'),
			delBtn = e.target.closest('.hda-cron-delete');
		const btn = runBtn || delBtn;
		if (!btn || btn.disabled) return;
		const isRun = !!runBtn,
			row = btn.closest('.hda-cron-row');
		if (!confirm(`${isRun ? 'Run' : 'Delete'} "${row.dataset.hook}" ${isRun ? 'now' : 'event'}?`)) return;
		setLoading(btn, true);
		showStatus(isRun ? 'Running...' : 'Deleting...', isRun ? '#0073aa' : '#d63638');
		ajaxPost({ action: isRun ? 'hda_cron_run' : 'hda_cron_delete', _nonce: nonce, hook: row.dataset.hook, timestamp: row.dataset.timestamp, sig: row.dataset.sig || '' })
			.then((data) => {
				if (data.success) {
					flash(row, data.data.message, true);
					if (isRun && !data.data.removed) setLoading(btn, false);
					else setTimeout(() => fadeOutRow(row, 300), isRun ? 1500 : 1000);
				} else {
					flash(row, data.data?.message || 'Error', false);
					setLoading(btn, false);
				}
			})
			.catch(() => {
				flash(row, 'Request failed', false);
				setLoading(btn, false);
			});
	});
}

// ── Contact Link Repeater ────────────────────────

function initContactLinkRepeater($) {
	const $c = $('#contact-link-items');
	if (!$c.length) return;
	const i18n = window.hdaContactLinkI18n || {
		newContact: 'New Contact',
		remove: 'Remove',
		selectIcon: 'Select Icon',
		useThisIcon: 'Use this icon',
		atLeastOne: 'You must have at least one contact link.',
	};

	$c.sortable({ handle: '.drag-handle', placeholder: 'repeater-item ui-sortable-placeholder', update: reindex });
	$c.on('click', '.toggle-item, .item-title', function (e) {
		e.stopPropagation();
		$(this).closest('.repeater-item').toggleClass('collapsed');
	});
	$c.on('input', '.item-name', function () {
		$(this)
			.closest('.repeater-item')
			.find('.item-title')
			.text($(this).val() || i18n.newContact);
	});
	$c.on('click', '.remove-item', function (e) {
		e.stopPropagation();
		const $item = $(this).closest('.repeater-item');
		if ($c.find('.repeater-item').length > 1)
			$item.slideUp(200, function () {
				$(this).remove();
				reindex();
			});
		else alert(i18n.atLeastOne);
	});

	$('#add-contact-item').on('click', function () {
		const idx = $c.find('.repeater-item').length;
		const id =
			crypto.randomUUID?.() ||
			'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
				const r = (Math.random() * 16) | 0;
				return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
			});
		const $item = $(buildContactHTML(idx, id, i18n));
		$c.append($item);
		$item.hide().slideDown(200);
		$('html, body').animate({ scrollTop: $item.offset().top - 100 }, 300);
	});

	function reindex() {
		$c.find('.repeater-item').each(function (i) {
			$(this).attr('data-index', i).find('.item-order').val(i);
			$(this)
				.find('[name^="hda_contact_link[contact_items]["]')
				.each(function () {
					$(this).attr(
						'name',
						$(this)
							.attr('name')
							.replace(/\[contact_items\]\[\d+\]/, `[contact_items][${i}]`),
					);
					const id = $(this).attr('id');
					if (id) $(this).attr('id', id.replace(/_\d+$/, `_${i}`));
				});
			$(this)
				.find('label[for]')
				.each(function () {
					$(this).attr('for', $(this).attr('for').replace(/_\d+$/, `_${i}`));
				});
		});
	}

	function buildContactHTML(idx, id, t) {
		const field = (label, name, type = 'text', extra = '') =>
			`<div class="field-row"><label for="contact_${name}_${idx}">${t[name] || label}</label><input type="${type}" id="contact_${name}_${idx}" name="hda_contact_link[contact_items][${idx}][${name}]" value="" class="regular-text${name === 'name' ? ' item-name' : ''}${name === 'color' ? ' hda-color-field' : ''}" placeholder="${t[name + 'Placeholder'] || ''}" ${extra}></div>`;
		return `<div class="repeater-item" data-index="${idx}">
			<div class="repeater-item-header">
				<span class="drag-handle dashicons dashicons-move"></span><span class="item-title">${t.newContact}</span>
				<button type="button" class="toggle-item" aria-expanded="true"><span class="dashicons dashicons-arrow-up-alt2"></span></button>
				<button type="button" class="remove-item" title="${t.remove}"><span class="dashicons dashicons-trash"></span></button>
			</div>
			<div class="repeater-item-content">
				<input type="hidden" name="hda_contact_link[contact_items][${idx}][id]" value="${id}"><input type="hidden" name="hda_contact_link[contact_items][${idx}][order]" value="${idx}" class="item-order">
				<div class="field-row field-icon"><label>${t.icon || 'Icon'}</label>
					<div class="hda-media-upload" data-title="${t.selectIcon}" data-button="${t.useThisIcon}" data-library="image,image/svg+xml">
						<div class="hda-media-preview empty"><span class="dashicons dashicons-format-image"></span></div>
						<input type="hidden" name="hda_contact_link[contact_items][${idx}][icon]" value="" class="hda-media-value">
						<div style="display:flex;gap:6px;margin-top:6px"><button type="button" class="button js-media-select">${t.selectIcon}</button><button type="button" class="button js-media-remove hidden">${t.remove}</button></div>
					</div>
					<p class="field-desc">${t.iconDesc || 'Select an image or SVG from the media library.'}</p>
				</div>
				${field('Name', 'name')}
				<div class="field-row"><label for="contact_value_${idx}">${t.linkValue || 'Link/Value'}</label><input type="text" id="contact_value_${idx}" name="hda_contact_link[contact_items][${idx}][value]" value="" class="regular-text" placeholder="${t.valuePlaceholder || ''}"></div>
				<div class="field-row field-row-inline">
					<div class="field-col"><label for="contact_target_${idx}">${t.target || 'Target'}</label><select id="contact_target_${idx}" name="hda_contact_link[contact_items][${idx}][target]"><option value="_blank">${t.targetBlank || 'New Tab (_blank)'}</option><option value="_self">${t.targetSelf || 'Same Tab (_self)'}</option></select></div>
					<div class="field-col"><label for="contact_class_${idx}">${t.cssClass || 'CSS Class'}</label><input type="text" id="contact_class_${idx}" name="hda_contact_link[contact_items][${idx}][class]" value="" class="regular-text" placeholder="${t.classPlaceholder || ''}"></div>
					<div class="field-col"><label for="contact_color_${idx}">${t.color || 'Color'}</label><input type="text" id="contact_color_${idx}" name="hda_contact_link[contact_items][${idx}][color]" value="" class="hda-color-field" placeholder="#000000"></div>
				</div>
			</div>
		</div>`;
	}
}

// ── Redirect Manager ─────────────────────────────

function initRedirectRepeater() {
	const tbody = document.getElementById('hda-redirect-rules');
	if (!tbody) return;
	const tableWrap = document.getElementById('hda-redirect-table-wrap'),
		addBtn = document.getElementById('hda-redirect-add');
	const emptyMsg = document.getElementById('hda-redirect-empty'),
		selectAllCb = document.getElementById('hda-redirect-select-all');
	const deleteSelectedBtn = document.getElementById('hda-redirect-delete-selected'),
		deleteAllBtn = document.getElementById('hda-redirect-delete-all');
	const searchInput = document.getElementById('hda-redirect-search'),
		importBtn = document.getElementById('hda-redirect-import-btn');
	const importFile = document.getElementById('hda-redirect-import-file'),
		importMode = document.getElementById('hda-redirect-import-mode');
	const importStatus = document.getElementById('hda-redirect-import-status');
	const { nonce = '', i18n = {} } = window.hdaRedirect || {};

	// Duplicate check
	tbody.addEventListener(
		'blur',
		(e) => {
			const input = e.target;
			if (input.name !== 'hda_redirect[redirect_from][]') return;
			const val = (input.value || '').trim();
			clearDupe(input);
			if (!val) return;
			const row = input.closest('.hda-redirect-row'),
				norm = val.toLowerCase().replace(/\/+$/, '');
			let dupe = false;
			tbody.querySelectorAll('[name="hda_redirect[redirect_from][]"]').forEach((o) => {
				if (o !== input && (o.value || '').trim().toLowerCase().replace(/\/+$/, '') === norm) dupe = true;
			});
			if (dupe) {
				markDupe(input, 'Duplicate — this path already exists on this page.');
				return;
			}
			if (row?.dataset.origFrom?.toLowerCase().replace(/\/+$/, '') === norm) return;
			ajaxPost({ action: 'hda_redirect_check_dupe', _nonce: nonce, from: val })
				.then((d) => {
					if (d.success && d.data.exists) markDupe(input, `Duplicate — already redirects to: ${d.data.existing_to}`);
				})
				.catch(() => {});
		},
		true,
	);

	const markDupe = (input, msg) => {
		clearDupe(input);
		const s = document.createElement('small');
		s.className = 'hda-redirect-dupe-warn';
		s.textContent = msg;
		input.parentNode.appendChild(s);
		input.classList.add('hda-redirect-input--dupe');
	};
	const clearDupe = (input) => {
		input.parentNode?.querySelector('.hda-redirect-dupe-warn')?.remove();
		input.classList.remove('hda-redirect-input--dupe');
	};

	// Inline edit
	tbody.addEventListener('click', (e) => {
		const eb = e.target.closest('.hda-redirect-edit');
		if (!eb) return;
		const row = eb.closest('.hda-redirect-row');
		if (!row) return;
		row.classList.contains('hda-redirect-row--editing') ? cancelEdit(row) : enterEdit(row);
	});
	const enterEdit = (row) => {
		row.classList.add('hda-redirect-row--editing');
		row.querySelectorAll('.hda-redirect-input').forEach((i) => i.removeAttribute('readonly'));
		const sel = row.querySelector('.hda-redirect-select');
		if (sel) {
			sel.disabled = false;
			sel.onchange = () => {
				const h = row.querySelector('.hda-redirect-type-hidden');
				if (h) h.value = sel.value;
			};
		}
		row.dataset.origFrom = row.querySelector('[name="hda_redirect[redirect_from][]"]')?.value || '';
		row.dataset.origTo = row.querySelector('[name="hda_redirect[redirect_to][]"]')?.value || '';
		row.dataset.origType = row.querySelector('.hda-redirect-type-hidden')?.value || '301';
		const eb = row.querySelector('.hda-redirect-edit');
		if (eb) {
			eb.title = 'Cancel';
			const ic = eb.querySelector('.dashicons');
			if (ic) {
				ic.classList.remove('dashicons-edit');
				ic.classList.add('dashicons-no-alt');
			}
		}
	};
	const cancelEdit = (row) => {
		row.classList.remove('hda-redirect-row--editing');
		const fi = row.querySelector('[name="hda_redirect[redirect_from][]"]'),
			ti = row.querySelector('[name="hda_redirect[redirect_to][]"]'),
			ts = row.querySelector('[name="hda_redirect[redirect_type][]"]');
		if (fi) {
			fi.value = row.dataset.origFrom || '';
			fi.setAttribute('readonly', '');
		}
		if (ti) {
			ti.value = row.dataset.origTo || '';
			ti.setAttribute('readonly', '');
		}
		if (ts) {
			ts.value = row.dataset.origType || '301';
			ts.disabled = true;
			ts.onchange = null;
		}
		const ht = row.querySelector('.hda-redirect-type-hidden');
		if (ht) ht.value = row.dataset.origType || '301';
		const spans = row.querySelectorAll('.hda-redirect-display');
		if (spans[0]) spans[0].textContent = fi?.value || '';
		if (spans[1]) spans[1].textContent = ti?.value || '';
		if (spans[2]) spans[2].textContent = ht?.value || '';
		const eb = row.querySelector('.hda-redirect-edit');
		if (eb) {
			eb.title = 'Edit';
			const ic = eb.querySelector('.dashicons');
			if (ic) {
				ic.classList.remove('dashicons-no-alt');
				ic.classList.add('dashicons-edit');
			}
		}
	};

	// Save row — direct AJAX, bypasses form serialization
	tbody.addEventListener('click', (e) => {
		const sb = e.target.closest('.hda-redirect-save');
		if (!sb) return;
		const row = sb.closest('.hda-redirect-row');
		if (!row) return;

		const fi = row.querySelector('[name="hda_redirect[redirect_from][]"]'),
			ti = row.querySelector('[name="hda_redirect[redirect_to][]"]'),
			ht = row.querySelector('.hda-redirect-type-hidden');
		const fromVal = (fi?.value || '').trim(),
			toVal = (ti?.value || '').trim(),
			typeVal = ht?.value || '301';

		if (!fromVal || !toVal) {
			alert(i18n.required || 'From and To fields are required.');
			return;
		}

		// Determine old_from for edit (empty string for new rows)
		const oldFrom = row.classList.contains('hda-redirect-row--new') ? '' : row.dataset.origFrom || '';

		ajaxPost({
			action: 'hda_redirect_save_row',
			_nonce: nonce,
			from: fromVal,
			to: toVal,
			type: typeVal,
			old_from: oldFrom,
		})
			.then((d) => {
				if (d.success) {
					window.location.reload();
				} else {
					alert(d.data?.message || 'Error saving rule.');
				}
			})
			.catch(() => alert('Request failed.'));
	});

	// Add row
	addBtn?.addEventListener('click', () => {
		emptyMsg?.remove();
		if (tableWrap) tableWrap.style.display = '';
		const n = tbody.querySelectorAll('.hda-redirect-row').length + 1,
			tr = document.createElement('tr');
		tr.className = 'hda-redirect-row hda-redirect-row--new hda-redirect-row--editing';
		tr.innerHTML = `<td class="hda-redirect-table__cb"><input type="checkbox" class="hda-redirect-cb"></td><td class="hda-redirect-table__num">${n}</td><td><span class="hda-redirect-display"></span><input type="text" class="input hda-redirect-input" name="hda_redirect[redirect_from][]" placeholder="/old-page"></td><td><span class="hda-redirect-display"></span><input type="url" class="input hda-redirect-input" name="hda_redirect[redirect_to][]" placeholder="https://example.com/new-page"></td><td><span class="hda-redirect-display">301</span><input type="hidden" name="hda_redirect[redirect_type][]" value="301" class="hda-redirect-type-hidden"><select class="select hda-redirect-select" data-name="hda_redirect[redirect_type]"><option value="301">301</option><option value="302">302</option></select></td><td class="hda-redirect-table__actions-cell"><button type="button" class="button button-small hda-redirect-edit" title="Cancel"><span class="dashicons dashicons-no-alt"></span></button><button type="button" class="button button-small hda-redirect-save" title="Save"><span class="dashicons dashicons-yes"></span></button><button type="button" class="button button-small hda-redirect-remove" title="Delete"><span class="dashicons dashicons-trash"></span></button></td>`;
		const ns = tr.querySelector('.hda-redirect-select'),
			nh = tr.querySelector('.hda-redirect-type-hidden');
		if (ns && nh) ns.onchange = () => (nh.value = ns.value);
		tbody.appendChild(tr);
		tr.querySelector('input[name="hda_redirect[redirect_from][]"]')?.focus();
	});

	// Delete row
	const fade = (row) => {
		fadeOutRow(row);
		setTimeout(() => {
			renumber();
			updateBulk();
		}, 300);
	};
	const ajaxDel = (indices, rows) => {
		ajaxPost({ action: 'hda_redirect_delete', _nonce: nonce, indices })
			.then((d) => {
				if (d.success) {
					rows.forEach(fade);
					setTimeout(() => window.location.reload(), 800);
				} else alert(d.data?.message || 'Error');
			})
			.catch(() => alert('Request failed.'));
	};
	tbody.addEventListener('click', (e) => {
		const rb = e.target.closest('.hda-redirect-remove');
		if (!rb) return;
		const row = rb.closest('.hda-redirect-row');
		if (!row) return;
		const idx = row.dataset.index;
		if (idx === undefined || idx === '') {
			fade(row);
			return;
		}
		if (!confirm('Delete this redirect rule?')) return;
		rb.disabled = true;
		ajaxDel([parseInt(idx, 10)], [row]);
	});

	// Bulk actions
	const getChecked = () => [...tbody.querySelectorAll('.hda-redirect-cb:checked')].map((cb) => cb.closest('.hda-redirect-row'));
	const updateBulk = () => {
		if (deleteSelectedBtn) deleteSelectedBtn.style.display = getChecked().length > 0 ? '' : 'none';
	};
	selectAllCb?.addEventListener('change', () => {
		tbody.querySelectorAll('.hda-redirect-cb').forEach((cb) => {
			const r = cb.closest('.hda-redirect-row');
			if (r && r.style.display !== 'none') cb.checked = selectAllCb.checked;
		});
		updateBulk();
	});
	tbody.addEventListener('change', (e) => {
		if (e.target.classList.contains('hda-redirect-cb')) updateBulk();
	});
	deleteSelectedBtn?.addEventListener('click', () => {
		const rows = getChecked();
		if (!rows.length || !confirm(`Delete ${rows.length} selected rule(s)?`)) return;
		const indices = rows
			.map((r) => r.dataset.index)
			.filter((i) => i !== undefined && i !== '')
			.map((i) => parseInt(i, 10));
		if (indices.length > 0) ajaxDel(indices, rows);
		else {
			rows.forEach((r) => r.remove());
			renumber();
			updateBulk();
		}
		if (selectAllCb) selectAllCb.checked = false;
	});
	deleteAllBtn?.addEventListener('click', () => {
		const rows = tbody.querySelectorAll('.hda-redirect-row');
		if (!rows.length || !confirm('Delete ALL redirect rules? This cannot be undone.')) return;
		ajaxPost({ action: 'hda_redirect_delete_all', _nonce: nonce })
			.then((d) => {
				if (d.success) {
					rows.forEach(fade);
					setTimeout(() => window.location.reload(), 800);
				} else alert(d.data?.message || 'Error');
			})
			.catch(() => alert('Request failed.'));
	});

	// Search
	searchInput?.addEventListener('input', () => {
		const q = searchInput.value.toLowerCase().trim();
		tbody.querySelectorAll('.hda-redirect-row').forEach((row) => {
			if (!q) {
				row.style.display = '';
				return;
			}
			const from = (row.querySelector('[name="hda_redirect[redirect_from][]"]')?.value || '').toLowerCase();
			const to = (row.querySelector('[name="hda_redirect[redirect_to][]"]')?.value || '').toLowerCase();
			row.style.display = from.includes(q) || to.includes(q) ? '' : 'none';
		});
	});

	const renumber = () =>
		tbody.querySelectorAll('.hda-redirect-row').forEach((r, i) => {
			const c = r.querySelector('.hda-redirect-table__num');
			if (c) c.textContent = i + 1;
		});

	// Import
	if (importBtn && importFile) {
		importBtn.addEventListener('click', () => importFile.click());
		importFile.addEventListener('change', () => {
			const file = importFile.files?.[0];
			if (!file) return;
			const mode = importMode?.value || 'append';
			if (mode === 'replace' && !confirm(i18n.confirm_replace || 'Replace all existing rules?')) {
				importFile.value = '';
				return;
			}
			const fd = new FormData();
			fd.append('action', 'hda_redirect_import');
			fd.append('_nonce', nonce);
			fd.append('import_file', file);
			fd.append('import_mode', mode);
			showImport(i18n.importing || 'Importing...', '#0073aa');
			importBtn.disabled = true;
			fetch(ajaxurl, { method: 'POST', body: fd })
				.then((r) => r.json())
				.then((d) => {
					if (d.success) {
						let msg = `✅ ${d.data.message}`;
						if (d.data.errors?.length) msg += `<br><small style="color:#d63638">${d.data.errors.join('<br>')}</small>`;
						showImport(msg, '#46b450');
						setTimeout(() => window.location.reload(), 1500);
					} else {
						showImport(`❌ ${d.data?.message || 'Error'}`, '#d63638');
						importBtn.disabled = false;
					}
				})
				.catch(() => {
					showImport('❌ Request failed', '#d63638');
					importBtn.disabled = false;
				});
			importFile.value = '';
		});
	}
	const showImport = (html, color) => {
		if (importStatus) {
			importStatus.innerHTML = html;
			importStatus.style.color = color || '#666';
			importStatus.style.display = '';
		}
	};
}

// ── Status Code Manager ──────────────────────────

function initStatusCodeRepeater() {
	const tbody = document.getElementById('hda-sc-rules');
	if (!tbody) return;
	const tableWrap = document.getElementById('hda-sc-table-wrap'),
		addBtn = document.getElementById('hda-sc-add');
	const emptyMsg = document.getElementById('hda-sc-empty'),
		selectAllCb = document.getElementById('hda-sc-select-all');
	const deleteSelectedBtn = document.getElementById('hda-sc-delete-selected'),
		deleteAllBtn = document.getElementById('hda-sc-delete-all');
	const searchInput = document.getElementById('hda-sc-search'),
		importBtn = document.getElementById('hda-sc-import-btn');
	const importFile = document.getElementById('hda-sc-import-file'),
		importMode = document.getElementById('hda-sc-import-mode');
	const importStatus = document.getElementById('hda-sc-import-status');
	const { nonce = '', i18n = {} } = window.hdaRedirect || {};

	// Inline edit
	tbody.addEventListener('click', (e) => {
		const eb = e.target.closest('.hda-redirect-edit');
		if (!eb) return;
		const row = eb.closest('.hda-sc-row');
		if (!row) return;
		row.classList.contains('hda-redirect-row--editing') ? cancelEdit(row) : enterEdit(row);
	});
	const enterEdit = (row) => {
		row.classList.add('hda-redirect-row--editing');
		row.querySelectorAll('.hda-redirect-input').forEach((i) => i.removeAttribute('readonly'));
		const sel = row.querySelector('.hda-redirect-select');
		if (sel) {
			sel.disabled = false;
			sel.onchange = () => {
				const h = row.querySelector('.hda-sc-code-hidden');
				if (h) h.value = sel.value;
			};
		}
		row.dataset.origPath = row.querySelector('[name="hda_redirect[sc_path][]"]')?.value || '';
		row.dataset.origCode = row.querySelector('.hda-sc-code-hidden')?.value || '410';
		const eb = row.querySelector('.hda-redirect-edit');
		if (eb) {
			eb.title = 'Cancel';
			const ic = eb.querySelector('.dashicons');
			if (ic) {
				ic.classList.remove('dashicons-edit');
				ic.classList.add('dashicons-no-alt');
			}
		}
	};
	const cancelEdit = (row) => {
		row.classList.remove('hda-redirect-row--editing');
		const pi = row.querySelector('[name="hda_redirect[sc_path][]"]'),
			ci = row.querySelector('.hda-sc-code-hidden');
		if (pi) {
			pi.value = row.dataset.origPath || '';
			pi.setAttribute('readonly', '');
		}
		if (ci) ci.value = row.dataset.origCode || '410';
		const sel = row.querySelector('.hda-redirect-select');
		if (sel) {
			sel.value = row.dataset.origCode || '410';
			sel.disabled = true;
			sel.onchange = null;
		}
		const spans = row.querySelectorAll('.hda-redirect-display');
		if (spans[0]) spans[0].textContent = pi?.value || '';
		if (spans[1]) {
			spans[1].textContent = ci?.value || '410';
			spans[1].className = `hda-redirect-display hda-sc-code-badge hda-sc-code-badge--${ci?.value || '410'}`;
		}
		const eb = row.querySelector('.hda-redirect-edit');
		if (eb) {
			eb.title = 'Edit';
			const ic = eb.querySelector('.dashicons');
			if (ic) {
				ic.classList.remove('dashicons-no-alt');
				ic.classList.add('dashicons-edit');
			}
		}
	};

	// Save row — direct AJAX, bypasses form serialization
	tbody.addEventListener('click', (e) => {
		const sb = e.target.closest('.hda-redirect-save');
		if (!sb) return;
		const row = sb.closest('.hda-sc-row');
		if (!row) return;

		const pi = row.querySelector('[name="hda_redirect[sc_path][]"]'),
			ci = row.querySelector('.hda-sc-code-hidden');
		const pathVal = (pi?.value || '').trim(),
			codeVal = ci?.value || '410';

		if (!pathVal) {
			alert(i18n.required || 'Path field is required.');
			return;
		}

		// Determine old_path for edit (empty string for new rows)
		const oldPath = row.classList.contains('hda-redirect-row--new') ? '' : row.dataset.origPath || '';

		ajaxPost({
			action: 'hda_status_code_save_row',
			_nonce: nonce,
			path: pathVal,
			code: codeVal,
			old_path: oldPath,
		})
			.then((d) => {
				if (d.success) {
					window.location.reload();
				} else {
					alert(d.data?.message || 'Error saving rule.');
				}
			})
			.catch(() => alert('Request failed.'));
	});

	// Add row
	addBtn?.addEventListener('click', () => {
		emptyMsg?.remove();
		if (tableWrap) tableWrap.style.display = '';
		const n = tbody.querySelectorAll('.hda-sc-row').length + 1,
			tr = document.createElement('tr');
		tr.className = 'hda-redirect-row hda-sc-row hda-redirect-row--new hda-redirect-row--editing';
		tr.innerHTML = `<td class="hda-redirect-table__cb"><input type="checkbox" class="hda-sc-cb"></td><td class="hda-redirect-table__num">${n}</td><td><span class="hda-redirect-display"></span><input type="text" class="input hda-redirect-input" name="hda_redirect[sc_path][]" placeholder="/removed-page"></td><td><span class="hda-redirect-display hda-sc-code-badge hda-sc-code-badge--410">410</span><input type="hidden" name="hda_redirect[sc_code][]" value="410" class="hda-sc-code-hidden"><select class="select hda-redirect-select" data-name="hda_redirect[sc_code]"><option value="410">410 Gone</option><option value="401">401 Unauthorized</option></select></td><td class="hda-redirect-table__actions-cell"><button type="button" class="button button-small hda-redirect-edit" title="Cancel"><span class="dashicons dashicons-no-alt"></span></button><button type="button" class="button button-small hda-redirect-save" title="Save"><span class="dashicons dashicons-yes"></span></button><button type="button" class="button button-small hda-redirect-remove" title="Delete"><span class="dashicons dashicons-trash"></span></button></td>`;
		const ns = tr.querySelector('.hda-redirect-select'),
			nh = tr.querySelector('.hda-sc-code-hidden');
		if (ns && nh) ns.onchange = () => (nh.value = ns.value);
		tbody.appendChild(tr);
		tr.querySelector('input[name="hda_redirect[sc_path][]"]')?.focus();
	});

	// Delete row
	const fade = (row) => {
		fadeOutRow(row);
		setTimeout(() => {
			renumber();
			updateBulk();
		}, 300);
	};
	const ajaxDel = (indices, rows) => {
		ajaxPost({ action: 'hda_status_code_delete', _nonce: nonce, indices })
			.then((d) => {
				if (d.success) {
					rows.forEach(fade);
					setTimeout(() => window.location.reload(), 800);
				} else alert(d.data?.message || 'Error');
			})
			.catch(() => alert('Request failed.'));
	};
	tbody.addEventListener('click', (e) => {
		const rb = e.target.closest('.hda-redirect-remove');
		if (!rb) return;
		const row = rb.closest('.hda-sc-row');
		if (!row) return;
		const idx = row.dataset.index;
		if (idx === undefined || idx === '') {
			fade(row);
			return;
		}
		if (!confirm('Delete this status code rule?')) return;
		rb.disabled = true;
		ajaxDel([parseInt(idx, 10)], [row]);
	});

	// Bulk actions
	const getChecked = () => [...tbody.querySelectorAll('.hda-sc-cb:checked')].map((cb) => cb.closest('.hda-sc-row'));
	const updateBulk = () => {
		if (deleteSelectedBtn) deleteSelectedBtn.style.display = getChecked().length > 0 ? '' : 'none';
	};
	selectAllCb?.addEventListener('change', () => {
		tbody.querySelectorAll('.hda-sc-cb').forEach((cb) => {
			const r = cb.closest('.hda-sc-row');
			if (r && r.style.display !== 'none') cb.checked = selectAllCb.checked;
		});
		updateBulk();
	});
	tbody.addEventListener('change', (e) => {
		if (e.target.classList.contains('hda-sc-cb')) updateBulk();
	});
	deleteSelectedBtn?.addEventListener('click', () => {
		const rows = getChecked();
		if (!rows.length || !confirm(`Delete ${rows.length} selected rule(s)?`)) return;
		const indices = rows
			.map((r) => r.dataset.index)
			.filter((i) => i !== undefined && i !== '')
			.map((i) => parseInt(i, 10));
		if (indices.length > 0) ajaxDel(indices, rows);
		else {
			rows.forEach((r) => r.remove());
			renumber();
			updateBulk();
		}
		if (selectAllCb) selectAllCb.checked = false;
	});
	deleteAllBtn?.addEventListener('click', () => {
		const rows = tbody.querySelectorAll('.hda-sc-row');
		if (!rows.length || !confirm('Delete ALL status code rules? This cannot be undone.')) return;
		ajaxPost({ action: 'hda_status_code_delete_all', _nonce: nonce })
			.then((d) => {
				if (d.success) {
					rows.forEach(fade);
					setTimeout(() => window.location.reload(), 800);
				} else alert(d.data?.message || 'Error');
			})
			.catch(() => alert('Request failed.'));
	});

	// Search
	searchInput?.addEventListener('input', () => {
		const q = searchInput.value.toLowerCase().trim();
		tbody.querySelectorAll('.hda-sc-row').forEach((row) => {
			if (!q) {
				row.style.display = '';
				return;
			}
			const path = (row.querySelector('[name="hda_redirect[sc_path][]"]')?.value || '').toLowerCase();
			const code = (row.querySelector('.hda-sc-code-hidden')?.value || '').toLowerCase();
			row.style.display = path.includes(q) || code.includes(q) ? '' : 'none';
		});
	});

	const renumber = () =>
		tbody.querySelectorAll('.hda-sc-row').forEach((r, i) => {
			const c = r.querySelector('.hda-redirect-table__num');
			if (c) c.textContent = i + 1;
		});

	// Import
	if (importBtn && importFile) {
		importBtn.addEventListener('click', () => importFile.click());
		importFile.addEventListener('change', () => {
			const file = importFile.files?.[0];
			if (!file) return;
			const mode = importMode?.value || 'append';
			if (mode === 'replace' && !confirm(i18n.confirm_replace || 'Replace all existing rules?')) {
				importFile.value = '';
				return;
			}
			const fd = new FormData();
			fd.append('action', 'hda_status_code_import');
			fd.append('_nonce', nonce);
			fd.append('import_file', file);
			fd.append('import_mode', mode);
			showImport(i18n.importing || 'Importing...', '#0073aa');
			importBtn.disabled = true;
			fetch(ajaxurl, { method: 'POST', body: fd })
				.then((r) => r.json())
				.then((d) => {
					if (d.success) {
						let msg = `✅ ${d.data.message}`;
						if (d.data.errors?.length) msg += `<br><small style="color:#d63638">${d.data.errors.join('<br>')}</small>`;
						showImport(msg, '#46b450');
						setTimeout(() => window.location.reload(), 1500);
					} else {
						showImport(`❌ ${d.data?.message || 'Error'}`, '#d63638');
						importBtn.disabled = false;
					}
				})
				.catch(() => {
					showImport('❌ Request failed', '#d63638');
					importBtn.disabled = false;
				});
			importFile.value = '';
		});
	}
	const showImport = (html, color) => {
		if (importStatus) {
			importStatus.innerHTML = html;
			importStatus.style.color = color || '#666';
			importStatus.style.display = '';
		}
	};
}

// ── Bootstrap ────────────────────────────────────

/**
 * Tooltip (hda-tip) — powered by Tippy.js.
 * Trigger: .hda-tip__icon button
 * Content: .hda-tip__body (hidden sibling, supports full HTML)
 * Touch:   tap to show/hide (Tippy handles this natively)
 */
function initTooltips() {
	document.querySelectorAll('.hda-tip').forEach((tip) => {
		const icon = tip.querySelector('.hda-tip__icon');
		const body = tip.querySelector('.hda-tip__body');
		if (!icon || !body) return;

		// Clone so Tippy can move it to body without losing the original
		const content = body.cloneNode(true);
		content.style.display = '';

		tippy(icon, {
			content,
			allowHTML: true,
			theme: 'hda',
			placement: 'top',
			interactive: false,
			trigger: 'mouseenter focus click',
			animation: 'shift-away-subtle',
			duration: [150, 100],
			maxWidth: 320,
			appendTo: document.body,
			aria: { content: 'describedby' },
		});
	});
}

jQuery(($) => {
	// Vanilla inits (no jQuery needed)
	initCodeMirror();
	initSelect2($);
	initOtpSettings();
	initRedirectRepeater();
	initStatusCodeRepeater();
	initWaf();
	initDbOptimizer();
	initCronManager();
	initFileIntegrity();
	initTooltips();

	// jQuery-dependent inits
	$('.hda-color-field').wpColorPicker();
	initSettingsForm($);
	initFilterTabs($);
	initContactLinkRepeater($);
	initMediaUpload($);
});
