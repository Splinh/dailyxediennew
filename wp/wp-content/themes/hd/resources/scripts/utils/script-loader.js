// utils/script-loader.js

const scriptLoader = (timeout = 4000, selector = 'script[data-type="lazy"]') => {
	return new Promise((resolve) => {
		const events = ['mouseover', 'keydown', 'touchstart', 'touchmove', 'wheel'];
		const eventOptions = { passive: true, capture: true };
		let done = false;
		let timer;

		const cleanup = () => {
			clearTimeout(timer);
			events.forEach((e) => window.removeEventListener(e, load, eventOptions));
		};

		const load = () => {
			if (done) return;
			done = true;

			document.querySelectorAll(selector).forEach((s) => {
				const src = s.dataset.src;
				if (!src) return;

				s.src = src;
				s.removeAttribute('data-src');
				s.removeAttribute('data-type');
			});

			cleanup();
			resolve();
		};

		timer = setTimeout(load, timeout);

		events.forEach((e) => window.addEventListener(e, load, eventOptions));
	});
};

export default scriptLoader;
