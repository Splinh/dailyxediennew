// modules/form/config.js
// Form lazy-load config — base form + optional feature sub-modules.

export default {
	form: {
		selector: '[data-form]',
		loader: () => import('./form.js'),
	},
	formTracking: {
		selector: '[data-form][data-tracking]',
		loader: () => import('./form-tracking.js'),
	},
	formLogic: {
		selector: '[data-form][data-logic]',
		loader: () => import('./form-logic.js'),
	},
	formSteps: {
		selector: '[data-form][data-multistep]',
		loader: () => import('./form-steps.js'),
	},
	formRepeater: {
		selector: '[data-form] [data-repeater]',
		loader: () => import('./form-repeater.js'),
	},
	formDropzone: {
		selector: '[data-form] [data-dropzone]',
		loader: () => import('./form-dropzone.js'),
	},
	formDynamic: {
		selector: '[data-form] [data-source]',
		loader: () => import('./form-dynamic.js'),
	},
};
