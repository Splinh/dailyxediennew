// core/modules/form/form-steps.js
// Multi-step form handler — splits [data-step] fieldsets into wizard steps.
//
// HTML API:
//   <form data-form="recruitment" data-multistep>
//     <div data-step-progress></div>
//     <fieldset data-step="1" data-step-title="Thông tin">
//       <input required>
//       <button type="button" data-action="next">Tiếp tục</button>
//     </fieldset>
//     <fieldset data-step="2" data-step-title="Chi tiết">
//       <button type="button" data-action="prev">Quay lại</button>
//       <button type="submit">Gửi</button>
//     </fieldset>
//   </form>

import './form-steps.scss';

/**
 * Initialize multi-step logic for a single form.
 *
 * @param {HTMLFormElement} form
 */
function initMultistep(form) {
	if (form._hdStepsInited) return;
	form._hdStepsInited = true;

	const steps = [...form.querySelectorAll('[data-step]')];
	if (steps.length < 2) return;

	let current = 0;

	// Build progress indicator.
	const progressContainer = form.querySelector('[data-step-progress]');
	if (progressContainer) {
		renderProgress(progressContainer, steps);
	}

	/**
	 * Show step at index, hide all others.
	 *
	 * @param {number} index
	 */
	function showStep(index) {
		steps.forEach((step, i) => {
			const isActive = i === index;
			step.classList.toggle('hd-step--active', isActive);
			step.hidden = !isActive;

			// Disable inputs in hidden steps to skip validation.
			step.querySelectorAll('input, select, textarea').forEach((el) => {
				if (isActive) {
					el.removeAttribute('disabled');
				} else {
					el.setAttribute('disabled', '');
				}
			});
		});

		current = index;
		updateProgress(progressContainer, current);

		// Scroll form into view.
		form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	}

	/**
	 * Validate all required fields in the current step.
	 *
	 * @returns {boolean}
	 */
	function validateCurrentStep() {
		const currentStep = steps[current];
		let valid = true;

		currentStep.querySelectorAll('[required]:not([disabled])').forEach((input) => {
			if (!input.reportValidity()) {
				valid = false;
			}
		});

		return valid;
	}

	// Bind navigation buttons (event delegation on form).
	function handleNavClick(e) {
		const btn = e.target.closest('[data-action]');
		if (!btn || !form.contains(btn)) return;

		const action = btn.dataset.action;

		if (action === 'next' && current < steps.length - 1) {
			if (validateCurrentStep()) {
				showStep(current + 1);
			}
		} else if (action === 'prev' && current > 0) {
			showStep(current - 1);
		}
	}

	form.addEventListener('click', handleNavClick);

	// Prevent Enter key from submitting form on intermediate steps.
	function handleKeyDown(e) {
		if (e.key !== 'Enter') return;
		if (e.target.tagName === 'TEXTAREA') return;
		if (current === steps.length - 1) return;
		e.preventDefault();
		if (validateCurrentStep()) showStep(current + 1);
	}

	form.addEventListener('keydown', handleKeyDown);

	// Before submit: enable all fields so they are included in FormData.
	function handleBeforeSubmit() {
		steps.forEach((step) => {
			step.querySelectorAll('input, select, textarea').forEach((el) => {
				el.removeAttribute('disabled');
			});
		});
	}

	form.addEventListener('submit', handleBeforeSubmit, { capture: true });

	// Initial state: show first step only.
	showStep(0);

	// Store cleanup.
	form._hdStepsCleanup = () => {
		form.removeEventListener('click', handleNavClick);
		form.removeEventListener('keydown', handleKeyDown);
		form.removeEventListener('submit', handleBeforeSubmit, { capture: true });
	};
}

/**
 * Render step progress dots.
 *
 * @param {HTMLElement} container
 * @param {HTMLElement[]} steps
 */
function renderProgress(container, steps) {
	container.innerHTML = '';
	container.classList.add('hd-step-progress');

	steps.forEach((step, i) => {
		const dot = document.createElement('div');
		dot.className = 'hd-step-progress__dot';
		dot.dataset.stepIndex = i;

		const label = document.createElement('span');
		label.className = 'hd-step-progress__label';
		label.textContent = step.dataset.stepTitle || `${i + 1}`;

		dot.appendChild(label);
		container.appendChild(dot);
	});
}

/**
 * Update active state on progress dots.
 *
 * @param {HTMLElement|null} container
 * @param {number} current
 */
function updateProgress(container, current) {
	if (!container) return;

	container.querySelectorAll('.hd-step-progress__dot').forEach((dot, i) => {
		dot.classList.toggle('hd-step-progress__dot--active', i === current);
		dot.classList.toggle('hd-step-progress__dot--done', i < current);
	});
}

/**
 * Destroy multi-step logic.
 *
 * @param {HTMLFormElement} form
 */
function destroyMultistep(form) {
	form._hdStepsCleanup?.();
	form._hdStepsInited = false;

	// Show all steps again.
	form.querySelectorAll('[data-step]').forEach((step) => {
		step.hidden = false;
		step.classList.remove('hd-step--active');
		step.querySelectorAll('input, select, textarea').forEach((el) => {
			el.removeAttribute('disabled');
		});
	});
}

// -- Module API (createLoader compatible) --

export default {
	initAll(root = document) {
		root.querySelectorAll('[data-form][data-multistep]').forEach(initMultistep);
	},

	destroyAll(root = document) {
		root.querySelectorAll('[data-form][data-multistep]').forEach(destroyMultistep);
	},
};
