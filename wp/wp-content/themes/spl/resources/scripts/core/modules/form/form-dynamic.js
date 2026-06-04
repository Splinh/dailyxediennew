// core/modules/form/form-dynamic.js
// Dynamic content dropdowns — load post/page/category options via REST API.
//
// HTML API:
//   <!-- Static IDs -->
//   <select name="branch" data-source="page" data-ids="10,20,30">
//
//   <!-- Cascade: children of selected parent -->
//   <select name="service" data-source="page" data-parent-field="branch">
//
//   <!-- Category list -->
//   <select name="category" data-source="category" data-taxonomy="product_cat">
//
//   <!-- Posts filtered by selected category -->
//   <select name="product" data-source="post" data-post-type="product"
//           data-filter-field="category" data-filter-taxonomy="product_cat">

const API_BASE = `${window.hdConfig?.restApiUrl || '/wp-json/hd/v1/'}form/dynamic-options`;

/** @type {Map<string, Array>} Simple cache to avoid re-fetching. */
const cache = new Map();

/**
 * Build cache key from params.
 *
 * @param {Object} params
 * @returns {string}
 */
function cacheKey(params) {
	return JSON.stringify(params);
}

/**
 * Fetch options from the REST API.
 *
 * @param {Object} params Query parameters.
 * @returns {Promise<Array<{id: number, title: string}>>}
 */
async function fetchOptions(params) {
	const key = cacheKey(params);
	if (cache.has(key)) return cache.get(key);

	const url = new URL(API_BASE, window.location.origin);
	Object.entries(params).forEach(([k, v]) => {
		if (v !== undefined && v !== '') url.searchParams.set(k, v);
	});

	try {
		const res = await fetch(url.toString());
		if (!res.ok) return [];

		const json = await res.json();
		const data = Array.isArray(json?.data) ? json.data : Array.isArray(json) ? json : [];
		cache.set(key, data);
		return data;
	} catch {
		return [];
	}
}

/**
 * Populate a <select> with options.
 *
 * @param {HTMLSelectElement} select
 * @param {Array<{id: number, title: string}>} items
 */
function populateSelect(select, items) {
	// Keep the first placeholder option.
	const placeholder = select.querySelector('option[value=""]');
	select.innerHTML = '';
	if (placeholder) select.appendChild(placeholder);

	items.forEach((item) => {
		const opt = document.createElement('option');
		opt.value = item.id;
		opt.textContent = item.title;
		select.appendChild(opt);
	});
}

/**
 * Initialize a single dynamic select.
 *
 * @param {HTMLSelectElement} select
 */
function initDynamicField(select) {
	if (select._hdDynamicInited) return;
	select._hdDynamicInited = true;

	const source = select.dataset.source;
	const ids = select.dataset.ids;
	const postType = select.dataset.postType;
	const taxonomy = select.dataset.taxonomy;
	const parentField = select.dataset.parentField;
	const filterField = select.dataset.filterField;
	const filterTaxonomy = select.dataset.filterTaxonomy;

	const form = select.closest('[data-form]');
	if (!form) return;

	// Static IDs — load once.
	if (ids) {
		fetchOptions({ source, ids }).then((items) => populateSelect(select, items));
		return;
	}

	// Cascade: parent page → child pages.
	if (parentField) {
		const parentSelect = form.querySelector(`[name="${parentField}"]`);
		if (!parentSelect) return;

		function loadChildren() {
			const parentId = parentSelect.value;
			if (!parentId) {
				populateSelect(select, []);
				return;
			}
			fetchOptions({ source, parent: parentId }).then((items) => populateSelect(select, items));
		}

		parentSelect.addEventListener('change', loadChildren);
		select._hdDynamicCleanup = () => parentSelect.removeEventListener('change', loadChildren);

		// Load initial if parent already has a value.
		if (parentSelect.value) loadChildren();
		return;
	}

	// Filter: category select → posts by term.
	if (filterField) {
		const filterSelect = form.querySelector(`[name="${filterField}"]`);
		if (!filterSelect) return;

		function loadFiltered() {
			const termId = filterSelect.value;
			if (!termId) {
				populateSelect(select, []);
				return;
			}
			fetchOptions({
				source,
				post_type: postType || 'post',
				taxonomy: filterTaxonomy || 'category',
				term_id: termId,
			}).then((items) => populateSelect(select, items));
		}

		filterSelect.addEventListener('change', loadFiltered);
		select._hdDynamicCleanup = () => filterSelect.removeEventListener('change', loadFiltered);

		if (filterSelect.value) loadFiltered();
		return;
	}

	// Default: load all (category list or all posts).
	const params = { source };
	if (postType) params.post_type = postType;
	if (taxonomy) params.taxonomy = taxonomy;

	fetchOptions(params).then((items) => populateSelect(select, items));
}

/**
 * Destroy dynamic field.
 *
 * @param {HTMLSelectElement} select
 */
function destroyDynamicField(select) {
	select._hdDynamicCleanup?.();
	select._hdDynamicInited = false;
}

// -- Module API (createLoader compatible) --

export default {
	initAll(root = document) {
		root.querySelectorAll('[data-source]').forEach(initDynamicField);
	},

	destroyAll(root = document) {
		root.querySelectorAll('[data-source]').forEach(destroyDynamicField);
	},
};
