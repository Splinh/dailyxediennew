// core/modules/form/form-dropzone.js
// Drag & Drop file upload — progressive enhancement over <input type="file">.
//
// HTML API:
//   <div data-dropzone data-accept=".pdf,.doc" data-max-size="5">
//     <input type="file" name="cv" hidden>
//     <div class="hd-dropzone-area">
//       <p>Kéo thả file vào đây hoặc <strong>bấm để chọn</strong></p>
//       <span class="hd-dropzone-hint">PDF, DOC — tối đa 5MB</span>
//     </div>
//     <div class="hd-dropzone-preview"></div>
//   </div>

import './form-dropzone.scss';

/**
 * Validate file against accept and max size constraints.
 *
 * @param {File} file
 * @param {string} accept — comma-separated extensions (e.g. ".pdf,.doc")
 * @param {number} maxSizeMB
 * @returns {string|null} Error message or null if valid.
 */
function validateFile(file, accept, maxSizeMB) {
	// Extension check.
	if (accept) {
		const allowed = accept.split(',').map((ext) => ext.trim().toLowerCase());
		const fileName = file.name.toLowerCase();
		const hasValid = allowed.some((ext) => fileName.endsWith(ext));

		if (!hasValid) {
			return `File type not allowed. Accepted: ${accept}`;
		}
	}

	// Size check.
	if (maxSizeMB && file.size > maxSizeMB * 1024 * 1024) {
		return `File too large. Maximum: ${maxSizeMB}MB`;
	}

	return null;
}

/**
 * Format file size for display.
 *
 * @param {number} bytes
 * @returns {string}
 */
function formatSize(bytes) {
	if (bytes < 1024) return `${bytes} B`;
	if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
	return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

/**
 * Initialize dropzone for a single container.
 *
 * @param {HTMLElement} container
 */
function initDropzone(container) {
	if (container._hdDropzoneInited) return;
	container._hdDropzoneInited = true;

	const fileInput = container.querySelector('input[type="file"]');
	if (!fileInput) return;

	const dropArea = container.querySelector('.hd-dropzone-area');
	const preview = container.querySelector('.hd-dropzone-preview');
	const accept = container.dataset.accept || '';
	const maxSize = parseFloat(container.dataset.maxSize) || 10;

	/**
	 * Handle selected/dropped files.
	 *
	 * @param {FileList} fileList
	 */
	function handleFiles(fileList) {
		if (!fileList.length) return;

		const file = fileList[0]; // Single file for now.

		const error = validateFile(file, accept, maxSize);
		if (error) {
			// Clear any previously staged file so invalid state doesn't persist
			fileInput.value = '';
			renderError(preview, error);
			return;
		}

		// Assign file to the hidden input via DataTransfer.
		const dt = new DataTransfer();
		dt.items.add(file);
		fileInput.files = dt.files;

		renderPreview(preview, file);
		container.classList.add('hd-dropzone--has-file');
	}

	/**
	 * Render file preview.
	 *
	 * @param {HTMLElement} el
	 * @param {File} file
	 */
	function renderPreview(el, file) {
		el.innerHTML = '';

		const wrapper = document.createElement('div');
		wrapper.className = 'hd-dropzone-file';

		const nameSpan = document.createElement('span');
		nameSpan.className = 'hd-dropzone-file__name';
		nameSpan.textContent = file.name;

		const sizeSpan = document.createElement('span');
		sizeSpan.className = 'hd-dropzone-file__size';
		sizeSpan.textContent = formatSize(file.size);

		const removeBtn = document.createElement('button');
		removeBtn.type = 'button';
		removeBtn.className = 'hd-dropzone-file__remove';
		removeBtn.setAttribute('aria-label', 'Remove');
		removeBtn.textContent = '\u2715';
		removeBtn.addEventListener('click', () => {
			fileInput.value = '';
			el.innerHTML = '';
			container.classList.remove('hd-dropzone--has-file');
		});

		wrapper.append(nameSpan, sizeSpan, removeBtn);
		el.appendChild(wrapper);
	}

	/**
	 * Render validation error.
	 *
	 * @param {HTMLElement} el
	 * @param {string} msg
	 */
	function renderError(el, msg) {
		el.innerHTML = '';
		const div = document.createElement('div');
		div.className = 'hd-dropzone-error';
		div.textContent = msg;
		el.appendChild(div);
		setTimeout(() => (el.innerHTML = ''), 4000);
	}

	// Drag events.
	function onDragOver(e) {
		e.preventDefault();
		container.classList.add('hd-dropzone--dragover');
	}

	function onDragLeave() {
		container.classList.remove('hd-dropzone--dragover');
	}

	function onDrop(e) {
		e.preventDefault();
		container.classList.remove('hd-dropzone--dragover');
		handleFiles(e.dataTransfer.files);
	}

	// Click to open file picker.
	function onAreaClick() {
		fileInput.click();
	}

	// Keyboard activation (Enter/Space) for accessibility.
	function onAreaKeyDown(e) {
		if (e.key === 'Enter' || e.key === ' ') {
			e.preventDefault();
			fileInput.click();
		}
	}

	function onFileChange() {
		handleFiles(fileInput.files);
	}

	if (dropArea) {
		dropArea.setAttribute('role', 'button');
		dropArea.setAttribute('tabindex', '0');
		dropArea.addEventListener('dragover', onDragOver);
		dropArea.addEventListener('dragleave', onDragLeave);
		dropArea.addEventListener('drop', onDrop);
		dropArea.addEventListener('click', onAreaClick);
		dropArea.addEventListener('keydown', onAreaKeyDown);
	}

	fileInput.addEventListener('change', onFileChange);

	// Cleanup.
	container._hdDropzoneCleanup = () => {
		if (dropArea) {
			dropArea.removeAttribute('role');
			dropArea.removeAttribute('tabindex');
			dropArea.removeEventListener('dragover', onDragOver);
			dropArea.removeEventListener('dragleave', onDragLeave);
			dropArea.removeEventListener('drop', onDrop);
			dropArea.removeEventListener('click', onAreaClick);
			dropArea.removeEventListener('keydown', onAreaKeyDown);
		}
		fileInput.removeEventListener('change', onFileChange);
	};
}

/**
 * Destroy dropzone.
 *
 * @param {HTMLElement} container
 */
function destroyDropzone(container) {
	container._hdDropzoneCleanup?.();
	container._hdDropzoneInited = false;
}

// -- Module API (createLoader compatible) --

export default {
	initAll(root = document) {
		root.querySelectorAll('[data-dropzone]').forEach(initDropzone);
	},

	destroyAll(root = document) {
		root.querySelectorAll('[data-dropzone]').forEach(destroyDropzone);
	},
};
