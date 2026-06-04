// admin.js — WP Admin enhancements
import '../styles/admin.scss';

const run = () => {
	// ── Disable "Send user notification" on Create User ──

	const sendNotification = document.querySelector('#createuser #send_user_notification');
	if (sendNotification) {
		sendNotification.checked = false;
		sendNotification.disabled = true;
	}

	// ── Hide editor for specific page templates ──

	const HIDDEN_EDITOR_TEMPLATES = new Set(['templates/template-page-home.php']);
	const selectedTemplate = document.getElementById('page_template');
	const editorWrapper = document.getElementById('postdivrich');

	if (selectedTemplate && editorWrapper) {
		const toggleEditor = () => {
			const shouldHide = HIDDEN_EDITOR_TEMPLATES.has(selectedTemplate.value);
			editorWrapper.style.display = shouldHide ? 'none' : '';

			if (!shouldHide) {
				setTimeout(() => window.dispatchEvent(new Event('resize')), 10);
			}
		};

		toggleEditor();
		selectedTemplate.addEventListener('change', toggleEditor);
	}

	// ── Post title required ──

	const postTitle = document.querySelector('input[name="post_title"]');
	if (postTitle) {
		postTitle.required = true;
	}

	// ── Delegated click handlers (single listener) ──

	document.addEventListener('click', (e) => {
		// Notice dismiss with fade out
		const dismissBtn = e.target.closest('.notice-dismiss');
		if (dismissBtn) {
			const notice = dismissBtn.closest('.notice.is-dismissible');
			if (notice) {
				notice.style.transition = 'opacity .5s ease';
				notice.style.opacity = '0';
				setTimeout(() => notice.remove(), 500);
			}
			return;
		}

		// Trash action confirmation
		const trashLink = e.target.closest('a[href*="action=trash"]');
		if (trashLink) {
			if (!confirm('Are you sure you want to move this post to the trash?')) {
				e.preventDefault();
			}
		}
	});

	// ── jQuery-dependent WP Admin integration ──
	if (window.jQuery) {
		jQuery(() => {
			//
		});
	}
};

document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', run, { once: true }) : run();
