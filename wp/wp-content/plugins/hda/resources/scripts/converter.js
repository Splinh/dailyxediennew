/**
 * Image Converter — Admin Page JavaScript
 *
 * Client-driven batch conversion:
 * JS calls the "process" endpoint in a sequential loop;
 * each call converts one chunk and returns updated stats.
 *
 * @package HDAddons\ImageConverter
 */

import '../styles/converter.scss';

(function () {
	'use strict';

	/** @type {boolean} Whether the process loop is running */
	let isProcessing = false;

	/** @type {boolean} Whether a cancel was requested */
	let cancelRequested = false;

	// ─── DOM References ─────────────────────────────────

	const $ = (sel) => document.querySelector(sel);
	const $$ = (sel) => document.querySelectorAll(sel);
	const el = {
		startBtn: $('#hda-imgconv-start-btn'),
		cancelBtn: $('#hda-imgconv-cancel-btn'),
		sourceDir: $('#imgconv_source_dir'),
		progressPanel: $('#hda-imgconv-progress'),
		completePanel: $('#hda-imgconv-complete'),
		progressBar: $('#progress-bar'),
		progressPercent: $('#progress-percent'),
		progressText: $('#progress-text'),
		qualityJpg: $('#imgconv_quality_jpg'),
		qualityPng: $('#imgconv_quality_png'),
		qualityJpgNum: $('#imgconv_quality_jpg_num'),
		qualityPngNum: $('#imgconv_quality_png_num'),
		statConverted: $('#stat-converted'),
		statSkipped: $('#stat-skipped'),
		statErrors: $('#stat-errors'),
		statSaved: $('#stat-saved'),
		statElapsed: $('#stat-elapsed'),
		statEta: $('#stat-eta'),
		completeMessage: $('#complete-message'),
		forceCheckbox: $('#hda-imgconv-force-reconvert'),
		cleanupBtn: $('#hda-imgconv-cleanup-btn'),
		cleanupResult: $('#hda-imgconv-cleanup-result'),
	};

	// ─── Init ───────────────────────────────────────────

	function init() {
		if (!el.startBtn) return;

		// Bind batch events
		el.startBtn.addEventListener('click', startBatch);
		el.cancelBtn?.addEventListener('click', cancelBatch);
		el.cleanupBtn?.addEventListener('click', cleanupAll);

		// Quality slider ↔ number sync
		syncQualityInputs(el.qualityJpg, el.qualityJpgNum);
		syncQualityInputs(el.qualityPng, el.qualityPngNum);

		// Format radio change → reload page to update counts
		$$('input[name="imgconv_format"]').forEach((radio) => {
			radio.addEventListener('change', () => {
				// Re-render source dir counts by reloading the tab
				// (counts are server-rendered based on format)
			});
		});

		// Auto-resume processing if batch already active (page was refreshed)
		if (el.progressPanel && el.progressPanel.style.display !== 'none') {
			showProgress();
			processLoop();
		}
	}

	// ─── Quality Sync ───────────────────────────────────

	function syncQualityInputs(rangeEl, numEl) {
		if (!rangeEl || !numEl) return;

		rangeEl.addEventListener('input', () => {
			numEl.value = rangeEl.value;
		});

		numEl.addEventListener('input', () => {
			let val = parseInt(numEl.value, 10);
			if (isNaN(val)) return;

			val = Math.max(30, Math.min(100, val));
			rangeEl.value = val;
		});

		numEl.addEventListener('blur', () => {
			let val = parseInt(numEl.value, 10);
			if (isNaN(val)) val = parseInt(rangeEl.value, 10);

			val = Math.max(30, Math.min(100, val));
			const step = parseInt(rangeEl.step, 10) || 5;
			val = Math.round(val / step) * step;

			numEl.value = val;
			rangeEl.value = val;
		});
	}

	// ─── Batch Start ────────────────────────────────────

	async function startBatch() {
		// Get selected source directories
		const checkedDirs = $$('.hda-imgconv-source-checkbox:checked');
		if (!checkedDirs.length) {
			alert(hdaImgConv.i18n.error + ': No directories selected.');
			return;
		}

		if (!confirm(hdaImgConv.i18n.confirm_start)) return;

		const formatRadio = document.querySelector('input[name="imgconv_format"]:checked');
		const format = formatRadio ? formatRadio.value : 'avif';
		const forceReconvert = el.forceCheckbox?.checked || false;

		// Use the first selected directory as source
		const sourceDir = checkedDirs[0].value;

		el.startBtn.disabled = true;
		el.startBtn.textContent = '⏳ ' + hdaImgConv.i18n.converting;

		try {
			const response = await ajaxPost('hda_imgconv_start', {
				source_dir: sourceDir,
				format: format,
				force: forceReconvert ? '1' : '0',
			});

			if (response.success) {
				showProgress();
				updateProgressUI({
					active: true,
					format: response.data.format,
					engine: response.data.engine,
					stats: { total: response.data.total, pending: response.data.total },
					processed: 0,
					elapsed: 0,
					eta: 0,
				});

				processLoop();
			} else {
				alert(response.data?.message || hdaImgConv.i18n.error);
				resetStartButton();
			}
		} catch (err) {
			alert(hdaImgConv.i18n.error + ': ' + err.message);
			resetStartButton();
		}
	}

	// ─── Batch Cancel ───────────────────────────────────

	async function cancelBatch() {
		if (!confirm(hdaImgConv.i18n.confirm_cancel)) return;

		el.cancelBtn.disabled = true;
		cancelRequested = true;

		try {
			const response = await ajaxPost('hda_imgconv_cancel');

			if (response.success) {
				el.progressText.textContent = hdaImgConv.i18n.cancelled;
				setTimeout(() => {
					hideProgress();
					resetStartButton();
				}, 2000);
			}
		} catch {
			el.cancelBtn.disabled = false;
			cancelRequested = false;
		}
	}

	// ─── Process Loop (Client-Driven) ───────────────────

	async function processLoop() {
		if (isProcessing || cancelRequested) return;

		isProcessing = true;

		try {
			const response = await ajaxPost('hda_imgconv_process');

			if (!response.success) {
				isProcessing = false;
				setTimeout(processLoop, 3000);
				return;
			}

			const data = response.data;

			if (!data.active) {
				isProcessing = false;
				showComplete(data);
				return;
			}

			updateProgressUI(data);

			isProcessing = false;
			setTimeout(processLoop, 500);
		} catch {
			isProcessing = false;
			if (!cancelRequested) {
				setTimeout(processLoop, 5000);
			}
		}
	}

	// ─── UI Updates ─────────────────────────────────────

	function updateProgressUI(data) {
		const stats = data.stats || {};
		const total = stats.total || 0;
		const processed = data.processed || 0;
		const percent = total > 0 ? Math.round((processed / total) * 100) : 0;

		el.progressBar.style.width = percent + '%';
		el.progressPercent.textContent = percent + '% (' + processed + '/' + total + ')';

		el.progressText.textContent = data.engine ? 'Engine: ' + data.engine + ' | ' + (data.format || '').toUpperCase() : hdaImgConv.i18n.converting;

		el.statConverted.textContent = stats.converted || 0;
		el.statSkipped.textContent = stats.skipped || 0;
		el.statErrors.textContent = stats.error || 0;
		el.statSaved.textContent = formatBytes(stats.saved_bytes || 0);

		el.statElapsed.textContent = formatDuration(data.elapsed || 0);
		el.statEta.textContent = data.eta > 0 ? formatDuration(data.eta) : '—';

		el.cancelBtn.style.display = 'inline-flex';
		el.cancelBtn.disabled = false;
	}

	function showProgress() {
		el.progressPanel.style.display = 'block';
		el.completePanel.style.display = 'none';
		el.cancelBtn.style.display = 'inline-flex';
		cancelRequested = false;
	}

	function hideProgress() {
		el.progressPanel.style.display = 'none';
		el.cancelBtn.style.display = 'none';
	}

	function showComplete(data) {
		hideProgress();

		const stats = data.stats || {};
		const savedStr = formatBytes(stats.saved_bytes || 0);

		el.completeMessage.textContent = 'Converted: ' + (stats.converted || 0) + ' | Skipped: ' + (stats.skipped || 0) + ' | Errors: ' + (stats.error || 0) + ' | Saved: ' + savedStr;

		el.completePanel.style.display = 'block';
		resetStartButton();
	}

	function resetStartButton() {
		el.startBtn.disabled = false;
		el.startBtn.innerHTML = '🚀 ' + (hdaImgConv.i18n?.start || 'Start Conversion');
		isProcessing = false;
		cancelRequested = false;
	}

	// ─── AJAX Helper ────────────────────────────────────

	async function ajaxPost(action, extraData = {}) {
		const formData = new FormData();
		formData.set('action', action);
		formData.set('_ajax_nonce', hdaImgConv.nonce);

		for (const [key, val] of Object.entries(extraData)) {
			formData.set(key, val);
		}

		const response = await fetch(hdaImgConv.ajaxUrl, {
			method: 'POST',
			body: formData,
		});

		return response.json();
	}

	// ─── Formatting ─────────────────────────────────────

	function formatBytes(bytes) {
		if (bytes === 0) return '0 B';
		const units = ['B', 'KB', 'MB', 'GB'];
		const i = Math.floor(Math.log(bytes) / Math.log(1024));
		return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + units[i];
	}

	function formatDuration(seconds) {
		if (seconds < 60) return seconds + 's';
		const m = Math.floor(seconds / 60);
		const s = seconds % 60;
		if (m < 60) return m + 'm ' + s + 's';
		const h = Math.floor(m / 60);
		return h + 'h ' + (m % 60) + 'm';
	}
	// ─── Cleanup ────────────────────────────────────────

	async function cleanupAll() {
		if (!confirm('⚠️ Delete ALL converted directories (uploads_avif, uploads_webp, etc.) and reset everything? This cannot be undone.')) {
			return;
		}

		el.cleanupBtn.disabled = true;
		el.cleanupBtn.textContent = '⏳ Deleting...';
		el.cleanupResult.textContent = '';

		try {
			const response = await ajaxPost('hda_imgconv_cleanup');

			if (response.success) {
				el.cleanupResult.textContent = '✅ ' + response.data.message;
				el.cleanupResult.classList.add('hda-imgconv-cleanup-result--success');

				// Reload page after 2 seconds to update UI
				setTimeout(() => location.reload(), 2000);
			} else {
				el.cleanupResult.textContent = '❌ ' + (response.data?.message || 'Error');
				el.cleanupBtn.disabled = false;
				el.cleanupBtn.innerHTML = '🗑️ Delete All & Reset';
			}
		} catch (err) {
			el.cleanupResult.textContent = '❌ ' + err.message;
			el.cleanupBtn.disabled = false;
			el.cleanupBtn.innerHTML = '🗑️ Delete All & Reset';
		}
	}

	// ─── Boot ───────────────────────────────────────────

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
