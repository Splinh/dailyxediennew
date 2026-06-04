/* tailwind/plugins/clamp.js - Fluid typography with CSS clamp() */

export default (opts = {}) => {
	const root = Number(opts.root ?? 16);
	const defaults = { base: 0, minw: 380, maxw: 1920, ...(opts.defaults || {}) };
	const presets = opts.presets || {};

	// Build clamp() value for font-size (+ optional line-height)
	const buildClamp = (min, max, base = defaults.base, minw = defaults.minw, maxw = defaults.maxw) => {
		min = Number(min);
		max = Number(max);
		base = Number(base);
		minw = Number(minw);
		maxw = Number(maxw);

		if (min > max) [min, max] = [max, min];
		if (min === max || !(maxw > minw)) {
			const fsRem = min / root;
			const out = { fontSize: `${fsRem}rem` };
			if (base > 0) out.lineHeight = `${fsRem * base}rem`;
			return out;
		}

		const minRem = min / root;
		const maxRem = max / root;
		const minwRem = minw / root;
		const maxwRem = maxw / root;
		const slope = ((maxRem - minRem) / (maxwRem - minwRem)) * 100;
		const intercept = minRem - (slope * minwRem) / 100;

		const out = { fontSize: `clamp(${minRem}rem, calc(${intercept}rem + ${slope}vw), ${maxRem}rem)` };
		if (base > 0) {
			out.lineHeight = `clamp(${minRem * base}rem, calc(${intercept * base}rem + ${slope * base}vw), ${maxRem * base}rem)`;
		}
		return out;
	};

	return ({ matchUtilities, addUtilities }) => {
		// Arbitrary: p-clamp-[min,max,base?,minw?,maxw?]
		matchUtilities(
			{
				'p-clamp': (raw) => {
					const p = String(raw)
						.split(',')
						.map((s) => s.trim());
					if (p.length < 2) return {};
					const [min, max, base, minw, maxw] = p.map((value) => (value === '' ? undefined : Number(value)));
					const b = base ?? defaults.base;
					const mw = minw ?? defaults.minw;
					const xw = maxw ?? defaults.maxw;
					if (![min, max, b, mw, xw].every(Number.isFinite)) return {};
					return buildClamp(min, max, b, mw, xw);
				},
			},
			{ values: {}, type: 'any' },
		);

		// Presets: p-clamp-h1, p-clamp-h2, etc.
		const presetUtilities = Object.fromEntries(
			Object.entries(presets).map(([name, conf]) => {
				const { min, max, base, minw, maxw } = conf;
				return [`.p-clamp-${name}`, buildClamp(min, max, base, minw, maxw)];
			}),
		);
		addUtilities(presetUtilities);
	};
};
