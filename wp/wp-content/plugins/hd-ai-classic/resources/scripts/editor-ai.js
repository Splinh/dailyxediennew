/**
 * HD AI Classic - Classic Editor AI Assistant
 *
 * Injects AI buttons next to Title, Excerpt, and Term Description fields.
 * Each button opens a popup with preset prompt selection, custom input,
 * and result display with Apply/Regenerate actions.
 */

import '../styles/editor-ai.scss';

(function () {
	'use strict';

	// Bail if localized data is missing.
	if (typeof window.hdacData === 'undefined') {
		return;
	}

	const { features, presets, i18n } = window.hdacData;

	// SVG icon.

	const AI_ICON = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.64 3.64-1.28-1.28a1.21 1.21 0 0 0-1.72 0L2.36 18.64a1.21 1.21 0 0 0 0 1.72l1.28 1.28a1.21 1.21 0 0 0 1.72 0L21.64 5.36a1.21 1.21 0 0 0 0-1.72Z"/><path d="m14 7 3 3"/><path d="M5 6v1"/><path d="M19 17v1"/><path d="M20 8v1"/><path d="M17.5 14h-1"/><path d="M2.5 12h1"/><path d="M12 2v1"/><path d="M19 3.5v1"/><path d="M4.5 16.5v1"/></svg>`;

	// Feature config.

	const FEATURE_CONFIG = {
		title: {
			targetSelector: '#title',
			injectSelector: '#titlewrap',
			injectPosition: 'beforeend',
			getContext: () => ({
				post_id: getPostId(),
				content: getEditorContent(),
			}),
		},
		excerpt: {
			targetSelector: '#excerpt',
			injectSelector: '#postexcerpt .hndle, #postexcerpt > h2',
			injectPosition: 'beforeend',
			getContext: () => ({
				post_id: getPostId(),
				title: getFieldValue('#title'),
				content: getEditorContent(),
			}),
		},
		content: {
			targetSelector: '#content',
			injectSelector: '#wp-content-editor-tools .wp-media-buttons, #wp-content-editor-tools',
			injectPosition: 'beforeend',
			getContext: () => ({
				post_id: getPostId(),
				title: getFieldValue('#title'),
				content: getEditorContent(),
			}),
		},
		'long-content': {
			targetSelector: '#content',
			injectSelector: '#wp-content-editor-tools .wp-media-buttons, #wp-content-editor-tools',
			injectPosition: 'beforeend',
			getContext: () => ({
				post_id: getPostId(),
				title: getFieldValue('#title'),
				content: getEditorContent(),
			}),
		},
		image: {
			targetSelector: '#postimagediv',
			injectSelector: '#postimagediv .hndle, #postimagediv > h2',
			injectPosition: 'beforeend',
			getContext: () => ({
				post_id: getPostId(),
				title: getFieldValue('#title'),
				content: getEditorContent(),
			}),
		},
		'term-description': {
			targetSelector: '#tag-description, textarea[name="description"]',
			injectSelector: '.term-description-wrap label, label[for="tag-description"], label[for="description"]',
			injectPosition: 'beforeend',
			getContext: () => {
				const form = document.querySelector('#edittag, #addtag');
				return {
					term_name: getFieldValue('#name, input[name="tag-name"]') || '',
					taxonomy: form?.querySelector('input[name="taxonomy"]')?.value || 'category',
					term_id: form?.querySelector('input[name="tag_ID"]')?.value || '0',
				};
			},
		},
	};

	// Helpers.

	function getPostId() {
		return document.querySelector('#post_ID')?.value || '0';
	}

	function getFieldValue(selector) {
		const el = document.querySelector(selector);
		return el ? el.value.trim() : '';
	}

	function normalizeHttpUrl(value) {
		if (typeof value !== 'string' || !value.trim()) {
			return '';
		}

		try {
			const url = new URL(value, window.location.href);
			return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
		} catch (e) {
			return '';
		}
	}

	function renderImageSuccess(resultEl, response) {
		const fragment = document.createDocumentFragment();
		const imageUrl = normalizeHttpUrl(response.url || '');

		if (imageUrl) {
			const wrapper = document.createElement('div');
			wrapper.className = 'hdac-popup__preview-wrapper';

			const img = document.createElement('img');
			img.className = 'hdac-popup__image-preview';
			img.src = imageUrl;
			img.alt = 'Preview';

			wrapper.appendChild(img);
			fragment.appendChild(wrapper);
		}

		const success = document.createElement('p');
		success.className = 'hdac-popup__success-msg';
		success.textContent = i18n.imageSuccess || '';
		fragment.appendChild(success);

		const prompt = document.createElement('div');
		prompt.className = 'hdac-popup__prompt-text';

		const label = document.createElement('strong');
		label.textContent = 'Prompt:';
		prompt.appendChild(label);
		prompt.appendChild(document.createTextNode(` ${response.result || ''}`));
		fragment.appendChild(prompt);

		resultEl.textContent = '';
		resultEl.appendChild(fragment);
	}

	function updatePostThumbnail(attachmentId) {
		if (!attachmentId || !window.wp || !window.wp.ajax) {
			return;
		}

		wp.ajax
			.post('get-post-thumbnail-html', {
				post_id: getPostId(),
				thumbnail_id: attachmentId,
			})
			.done((html) => {
				if (typeof window.WPSetThumbnailHTML === 'function') {
					window.WPSetThumbnailHTML(html);
				}
			});
	}

	function formatMessage(template, ...values) {
		let index = 0;
		return String(template || '').replace(/%(\d+\$)?d/g, (match, position) => {
			const valueIndex = position ? parseInt(position, 10) - 1 : index++;
			return String(values[valueIndex] ?? '');
		});
	}

	function setProgress(progressEl, message) {
		if (!progressEl) {
			return;
		}

		progressEl.textContent = message || '';
		progressEl.style.display = message ? 'block' : 'none';
	}

	function getEditorContent() {
		// TinyMCE (Visual mode).
		if (typeof tinymce !== 'undefined') {
			const editor = tinymce.get('content');
			if (editor && !editor.isHidden()) {
				return editor.getContent({ format: 'html' }).trim();
			}
		}

		// Fallback: raw textarea.
		const textarea = document.querySelector('#content');
		return textarea ? textarea.value.trim() : '';
	}

	function insertAtCursor(textarea, text) {
		const start = textarea.selectionStart;
		const end = textarea.selectionEnd;
		const val = textarea.value;
		textarea.value = val.substring(0, start) + text + val.substring(end);
		textarea.selectionStart = textarea.selectionEnd = start + text.length;
		textarea.focus();
	}

	function copyToClipboard(text, btn) {
		if (!navigator.clipboard) {
			const textarea = document.createElement('textarea');
			textarea.value = text;
			textarea.style.position = 'fixed';
			document.body.appendChild(textarea);
			textarea.select();
			try {
				document.execCommand('copy');
				if (btn) {
					const originalText = btn.textContent;
					btn.textContent = i18n.copied || 'Copied!';
					setTimeout(() => {
						btn.textContent = originalText;
					}, 2000);
				}
			} catch (err) {
				// fail
			}
			document.body.removeChild(textarea);
			return;
		}
		navigator.clipboard.writeText(text).then(() => {
			if (btn) {
				const originalText = btn.textContent;
				btn.textContent = i18n.copied || 'Copied!';
				setTimeout(() => {
					btn.textContent = originalText;
				}, 2000);
			}
		});
	}

	// AI button.

	function createAiButton(feature) {
		const btn = document.createElement('button');
		btn.type = 'button';
		btn.className = feature === 'long-content' ? 'hdac-btn hdac-btn--long' : 'hdac-btn';
		btn.title = feature === 'long-content' ? i18n.longContent : i18n.generate;
		btn.setAttribute('aria-label', btn.title);
		btn.dataset.feature = feature;
		btn.innerHTML = feature === 'long-content' ? `${AI_ICON}<span>Long</span>` : AI_ICON;

		btn.addEventListener('click', (e) => {
			e.preventDefault();
			e.stopPropagation();
			openPopup(feature);
		});

		return btn;
	}

	// Popup.

	let activePopup = null;
	let activeGenerationRun = null;
	let lastGeneratedResult = '';

	function openPopup(feature) {
		// Close existing popup.
		if (activePopup) {
			closePopup();
		}

		const config = FEATURE_CONFIG[feature];
		if (!config) {
			return;
		}

		const featurePresets = presets[feature] || [];
		const overlay = document.createElement('div');
		overlay.className = 'hdac-popup-overlay';

		const dialog = document.createElement('div');
		dialog.className = 'hdac-popup';
		dialog.setAttribute('role', 'dialog');
		dialog.setAttribute('aria-modal', 'true');

		const isLongContent = feature === 'long-content';
		const popupTitle = isLongContent ? i18n.generateLong : i18n.generate;
		const generateLabel = isLongContent ? i18n.generateLong : i18n.generate;

		dialog.innerHTML = `
			<div class="hdac-popup__header">
				<h3 class="hdac-popup__title">${AI_ICON} ${popupTitle}</h3>
				<button type="button" class="hdac-popup__close" aria-label="${i18n.close}">&times;</button>
			</div>
			<div class="hdac-popup__body">
				${
					feature === 'image'
						? `
				<div class="hdac-popup__field">
					<label class="hdac-popup__label">${i18n.directImageLabel} / ${i18n.promptOnlyLabel}</label>
					<div class="hdac-popup__radio-group">
						<label class="hdac-popup__radio-label">
							<input type="radio" name="hdac-image-mode" value="image" checked>
							<span>${i18n.directImageLabel}</span>
						</label>
						<label class="hdac-popup__radio-label">
							<input type="radio" name="hdac-image-mode" value="image-prompt">
							<span>${i18n.promptOnlyLabel}</span>
						</label>
					</div>
				</div>
				`
						: ''
				}
				<div class="hdac-popup__field">
					<label class="hdac-popup__label">${i18n.presetLabel}</label>
					<select class="hdac-popup__preset-select" id="hdac-preset">
						${featurePresets.map((p, idx) => `<option value="${p.id}"${idx === 0 ? ' selected' : ''}>${p.label}</option>`).join('')}
					</select>
				</div>
				<div class="hdac-popup__field">
					<label class="hdac-popup__label">${i18n.customLabel}</label>
					<textarea class="hdac-popup__custom-input" id="hdac-custom" rows="2" placeholder="${i18n.customPlaceholder || ''}"></textarea>
				</div>
				${
					isLongContent
						? `
				<div class="hdac-popup__field">
					<label class="hdac-popup__check-label">
						<input type="checkbox" id="hdac-include-image" value="1">
						<span>${i18n.includeImage || ''}</span>
					</label>
				</div>
				<div class="hdac-popup__progress" id="hdac-progress" aria-live="polite" style="display:none;"></div>
				`
						: ''
				}
				<div class="hdac-popup__result-area" id="hdac-result-area" style="display:none;">
					<label class="hdac-popup__label">${i18n.resultLabel}</label>
					<div class="hdac-popup__result" id="hdac-result"></div>
				</div>
				<div class="hdac-popup__error" id="hdac-error" style="display:none;"></div>
			</div>
			<div class="hdac-popup__footer">
				<button type="button" class="button hdac-popup__btn-secondary" id="hdac-close">${i18n.close}</button>
				<button type="button" class="button hdac-popup__btn-secondary" id="hdac-apply" style="display:none;">${i18n.apply}</button>
				<button type="button" class="button hdac-popup__btn-secondary" id="hdac-replace" style="display:none;">${i18n.replaceContent}</button>
				<button type="button" class="button hdac-popup__btn-secondary" id="hdac-insert" style="display:none;">${i18n.insertContent}</button>
				<button type="button" class="button hdac-popup__btn-secondary" id="hdac-copy-prompt" style="display:none;">${i18n.copyPrompt}</button>
				<button type="button" class="button hdac-popup__btn-secondary" id="hdac-cancel" style="display:none;">${i18n.cancel}</button>
				<button type="button" class="button button-primary hdac-popup__btn-primary" id="hdac-generate">${generateLabel}</button>
			</div>
		`;

		overlay.appendChild(dialog);
		document.body.appendChild(overlay);
		activePopup = { overlay, dialog, feature, config };

		// Events.
		const closeBtn = dialog.querySelector('.hdac-popup__close');
		const closeBtnAlt = dialog.querySelector('#hdac-close');
		const generateBtn = dialog.querySelector('#hdac-generate');
		const applyBtn = dialog.querySelector('#hdac-apply');
		const replaceBtn = dialog.querySelector('#hdac-replace');
		const insertBtn = dialog.querySelector('#hdac-insert');
		const copyPromptBtn = dialog.querySelector('#hdac-copy-prompt');
		const cancelBtn = dialog.querySelector('#hdac-cancel');

		closeBtn.addEventListener('click', closePopup);
		closeBtnAlt.addEventListener('click', closePopup);
		overlay.addEventListener('click', (e) => {
			if (e.target === overlay) closePopup();
		});

		generateBtn.addEventListener('click', () => doGenerate(feature));
		cancelBtn.addEventListener('click', () => {
			if (activePopup?.run) {
				activePopup.run.cancelled = true;
			} else if (activeGenerationRun) {
				activeGenerationRun.cancelled = true;
			}
		});
		applyBtn.addEventListener('click', () => doApply(feature, 'apply'));
		replaceBtn.addEventListener('click', () => doApply(feature, 'replace'));
		insertBtn.addEventListener('click', () => doApply(feature, 'insert'));
		if (copyPromptBtn) {
			copyPromptBtn.addEventListener('click', (e) => {
				e.preventDefault();
				copyToClipboard(lastGeneratedResult, copyPromptBtn);
			});
		}

		// Keyboard.
		document.addEventListener('keydown', handleEscape);

		// Focus trap.
		dialog.querySelector('#hdac-preset')?.focus();
	}

	function closePopup() {
		if (!activePopup) return;

		if (activePopup.run) {
			activePopup.run.cancelled = true;
		}
		if (activeGenerationRun) {
			activeGenerationRun.cancelled = true;
		}

		activePopup.overlay.remove();
		activePopup = null;
		document.removeEventListener('keydown', handleEscape);
	}

	function handleEscape(e) {
		if (e.key === 'Escape') {
			closePopup();
		}
	}

	// Generate.

	async function doGenerate(feature) {
		const config = FEATURE_CONFIG[feature];
		if (!config) return;

		const presetSelect = document.querySelector('#hdac-preset');
		const customInput = document.querySelector('#hdac-custom');
		const generateBtn = document.querySelector('#hdac-generate');
		const resultArea = document.querySelector('#hdac-result-area');
		const resultEl = document.querySelector('#hdac-result');
		const errorEl = document.querySelector('#hdac-error');
		const applyBtn = document.querySelector('#hdac-apply');
		const replaceBtn = document.querySelector('#hdac-replace');
		const insertBtn = document.querySelector('#hdac-insert');
		const copyPromptBtn = document.querySelector('#hdac-copy-prompt');
		const cancelBtn = document.querySelector('#hdac-cancel');
		const progressEl = document.querySelector('#hdac-progress');

		// Reset.
		errorEl.style.display = 'none';
		errorEl.className = 'hdac-popup__error';
		errorEl.textContent = '';
		setProgress(progressEl, '');
		resultEl.classList.remove('hdac-popup__result--long');
		lastGeneratedResult = '';

		// Get context.
		const context = config.getContext();

		// Validate content exists (for post features).
		if (feature !== 'term-description' && !context.content && !context.post_id) {
			errorEl.textContent = i18n.noContent;
			errorEl.style.display = 'block';
			return;
		}

		if (feature === 'long-content') {
			await doGenerateLongContent({
				context,
				presetId: presetSelect?.value || '',
				customPrompt: customInput?.value?.trim() || '',
				generateBtn,
				cancelBtn,
				resultArea,
				resultEl,
				errorEl,
				progressEl,
				replaceBtn,
				insertBtn,
			});
			return;
		}

		// Determine active feature based on selected mode.
		let activeFeature = feature;
		if (feature === 'image') {
			const modeRadio = document.querySelector('input[name="hdac-image-mode"]:checked');
			if (modeRadio) {
				activeFeature = modeRadio.value;
			}
		}

		// Loading state.
		generateBtn.disabled = true;
		generateBtn.textContent = activeFeature === 'image' ? i18n.generatingImage : i18n.generating;
		resultArea.style.display = 'none';
		applyBtn.style.display = 'none';
		if (replaceBtn) replaceBtn.style.display = 'none';
		if (insertBtn) insertBtn.style.display = 'none';
		if (copyPromptBtn) copyPromptBtn.style.display = 'none';
		if (cancelBtn) cancelBtn.style.display = 'none';

		try {
			const response = await wp.apiFetch({
				path: 'hd-ai-classic/v1/generate',
				method: 'POST',
				data: {
					feature: activeFeature,
					context,
					prompt_preset: presetSelect?.value || '',
					custom_prompt: customInput?.value?.trim() || '',
				},
			});

			if (response.success) {
				lastGeneratedResult = response.result || '';
				if (activeFeature === 'image') {
					if (response.fallback) {
						errorEl.className = 'hdac-popup__error hdac-popup__error--warning';
						errorEl.textContent = response.message || '';
						errorEl.style.display = 'block';

						resultEl.textContent = response.result || '';
						if (copyPromptBtn) copyPromptBtn.style.display = '';
						resultArea.style.display = 'block';
					} else {
						renderImageSuccess(resultEl, response);

						if (copyPromptBtn) copyPromptBtn.style.display = '';
						resultArea.style.display = 'block';

						updatePostThumbnail(response.attachment_id);
					}
				} else if (activeFeature === 'image-prompt') {
					resultEl.textContent = response.result || '';
					if (copyPromptBtn) copyPromptBtn.style.display = '';
					resultArea.style.display = 'block';
				} else if (feature === 'content') {
					resultEl.innerHTML = response.result || '';
					if (replaceBtn) replaceBtn.style.display = '';
					if (insertBtn) insertBtn.style.display = '';
					resultArea.style.display = 'block';
				} else {
					resultEl.textContent = response.result || '';
					if (applyBtn) applyBtn.style.display = '';
					resultArea.style.display = 'block';
				}
				generateBtn.textContent = i18n.regenerate;
			} else {
				errorEl.textContent = response.message || i18n.error;
				errorEl.style.display = 'block';
			}
		} catch (err) {
			errorEl.textContent = err?.message || i18n.error;
			errorEl.style.display = 'block';
		} finally {
			generateBtn.disabled = false;
			if (generateBtn.textContent === i18n.generating || generateBtn.textContent === i18n.generatingImage) {
				generateBtn.textContent = i18n.generate;
			}
		}
	}

	async function doGenerateLongContent({
		context,
		presetId,
		customPrompt,
		generateBtn,
		cancelBtn,
		resultArea,
		resultEl,
		errorEl,
		progressEl,
		replaceBtn,
		insertBtn,
	}) {
		const run = { cancelled: false };
		activeGenerationRun = run;
		if (activePopup) {
			activePopup.run = run;
		}

		const includeImage = document.querySelector('#hdac-include-image')?.checked || false;
		const htmlParts = [];
		const previousHeadings = [];

		generateBtn.disabled = true;
		generateBtn.textContent = i18n.generating;
		if (cancelBtn) cancelBtn.style.display = '';
		if (replaceBtn) replaceBtn.style.display = 'none';
		if (insertBtn) insertBtn.style.display = 'none';

		resultEl.classList.add('hdac-popup__result--long');
		resultEl.textContent = '';
		resultArea.style.display = 'block';
		setProgress(progressEl, i18n.outlineProgress);

		try {
			const outlineResponse = await wp.apiFetch({
				path: 'hd-ai-classic/v1/generate',
				method: 'POST',
				data: {
					feature: 'long-content-outline',
					context,
					prompt_preset: presetId,
					custom_prompt: customPrompt,
				},
			});

			if (!outlineResponse.success) {
				throw new Error(outlineResponse.message || i18n.error);
			}

			if (run.cancelled) {
				throw new Error(i18n.cancelled);
			}

			const sections = Array.isArray(outlineResponse.sections) ? outlineResponse.sections : [];
			if (!sections.length) {
				throw new Error(i18n.error);
			}

			setProgress(progressEl, formatMessage(i18n.outlineReady, sections.length));

			for (let index = 0; index < sections.length; index++) {
				if (run.cancelled) {
					throw new Error(i18n.cancelled);
				}

				const section = sections[index];
				setProgress(progressEl, formatMessage(i18n.sectionProgress, index + 1, sections.length));

				const sectionResponse = await wp.apiFetch({
					path: 'hd-ai-classic/v1/generate',
					method: 'POST',
					data: {
						feature: 'long-content-section',
						context: {
							...context,
							outline: sections,
							section,
							previous_headings: previousHeadings,
						},
						prompt_preset: presetId,
						custom_prompt: customPrompt,
					},
				});

				if (!sectionResponse.success) {
					throw new Error(sectionResponse.message || i18n.error);
				}

				if (run.cancelled) {
					throw new Error(i18n.cancelled);
				}

				const sectionHtml = sectionResponse.result || '';
				htmlParts.push(sectionHtml);
				previousHeadings.push(section.heading || '');
				lastGeneratedResult = htmlParts.join('\n\n');
				resultEl.innerHTML = lastGeneratedResult;
				setProgress(progressEl, formatMessage(i18n.sectionComplete, index + 1, sections.length));
			}

			if (includeImage && !run.cancelled) {
				await generateLongContentFeaturedImage({ ...context, content: lastGeneratedResult }, presetId, customPrompt, errorEl);
			}

			if (run.cancelled) {
				throw new Error(i18n.cancelled);
			}

			setProgress(progressEl, i18n.longContentDone);
			if (replaceBtn) replaceBtn.style.display = '';
			if (insertBtn) insertBtn.style.display = '';
			generateBtn.textContent = i18n.regenerate;
		} catch (err) {
			const message = err?.message || i18n.error;
			errorEl.className = message === i18n.cancelled ? 'hdac-popup__error hdac-popup__error--warning' : 'hdac-popup__error';
			errorEl.textContent = message;
			errorEl.style.display = 'block';
			if (message === i18n.cancelled) {
				setProgress(progressEl, i18n.cancelled);
			}
		} finally {
			if (activePopup?.run === run) {
				delete activePopup.run;
			}
			if (activeGenerationRun === run) {
				activeGenerationRun = null;
			}
			generateBtn.disabled = false;
			if (cancelBtn) cancelBtn.style.display = 'none';
			if (generateBtn.textContent === i18n.generating) {
				generateBtn.textContent = i18n.generateLong || i18n.generate;
			}
		}
	}

	async function generateLongContentFeaturedImage(context, presetId, customPrompt, errorEl) {
		try {
			const response = await wp.apiFetch({
				path: 'hd-ai-classic/v1/generate',
				method: 'POST',
				data: {
					feature: 'image',
					context,
					prompt_preset: presetId,
					custom_prompt: customPrompt,
				},
			});

			if (!response.success || response.fallback) {
				throw new Error(response.message || i18n.imageError);
			}

			updatePostThumbnail(response.attachment_id);
		} catch (err) {
			errorEl.className = 'hdac-popup__error hdac-popup__error--warning';
			errorEl.textContent = err?.message || i18n.imageWarning;
			errorEl.style.display = 'block';
		}
	}

	// Apply.

	function doApply(feature, mode = 'apply') {
		const result = lastGeneratedResult.trim();
		if (!result) return;

		const config = FEATURE_CONFIG[feature];
		if (!config) return;

		if (feature === 'content' || feature === 'long-content') {
			// TinyMCE (Visual mode).
			if (typeof tinymce !== 'undefined') {
				const editor = tinymce.get('content');
				if (editor && !editor.isHidden()) {
					if (mode === 'insert') {
						editor.insertContent(result);
					} else {
						editor.setContent(result);
					}
					closePopup();
					return;
				}
			}

			// Fallback: raw textarea.
			const textarea = document.querySelector('#content');
			if (textarea) {
				if (mode === 'insert') {
					insertAtCursor(textarea, result);
				} else {
					textarea.value = result;
					textarea.dispatchEvent(new Event('input', { bubbles: true }));
					textarea.dispatchEvent(new Event('change', { bubbles: true }));
				}
			}
		} else {
			const target = document.querySelector(config.targetSelector);
			if (!target) return;

			// Set value.
			target.value = result;

			// Trigger change event for WP listeners.
			target.dispatchEvent(new Event('input', { bubbles: true }));
			target.dispatchEvent(new Event('change', { bubbles: true }));

			// For TinyMCE excerpt or other textareas.
			if (typeof jQuery !== 'undefined') {
				jQuery(target).trigger('change');
			}
		}

		closePopup();
	}

	// Init.

	function init() {
		if (!features || !features.length) {
			return;
		}

		features.forEach((feature) => {
			const config = FEATURE_CONFIG[feature];
			if (!config) return;

			const selectors = config.injectSelector.split(',');
			let injectTarget = null;
			for (const sel of selectors) {
				injectTarget = document.querySelector(sel.trim());
				if (injectTarget) break;
			}
			if (!injectTarget) return;

			// Don't inject twice.
			if (injectTarget.querySelector(`.hdac-btn[data-feature="${feature}"]`)) return;

			const btn = createAiButton(feature);

			if (config.injectPosition === 'beforeend') {
				injectTarget.appendChild(btn);
			} else {
				injectTarget.insertAdjacentElement(config.injectPosition, btn);
			}
		});
	}

	// Wait for DOM ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
