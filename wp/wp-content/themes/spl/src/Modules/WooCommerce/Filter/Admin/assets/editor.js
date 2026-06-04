(function ($) {
	var $repeater = $('#hd-filter-repeater');
	var rowCount = $repeater.children('.hd-filter-row').length;

	// Sortable
	$repeater.sortable({ handle: '.hd-filter-row__drag', placeholder: 'ui-sortable-placeholder', tolerance: 'pointer' });

	// Toggle row body
	$repeater.on('click', '.hd-filter-row__toggle, .hd-filter-row__title', function (e) {
		e.stopPropagation();
		$(this).closest('.hd-filter-row').find('.hd-filter-row__body').slideToggle(150);
	});

	// Remove row
	$repeater.on('click', '.hd-filter-row__remove', function () {
		if (confirm('Remove this filter?')) $(this).closest('.hd-filter-row').remove();
	});

	// Add row
	$('#hd-filter-add-row').on('click', function () {
		var tmpl = $('#tmpl-hd-filter-row')
			.html()
			.replace(/__INDEX__/g, rowCount++);
		var $row = $(tmpl).appendTo($repeater);
		$row.find('.hd-filter-row__body').show();
		$row.find('.hd-filter-type-select').trigger('change');
		initExcludeSelect2($row.find('.hd-exclude-terms-select'));
		$repeater.sortable('refresh');
	});

	// Conditional field visibility based on type
	$repeater.on('change', '.hd-filter-type-select', function () {
		var type = $(this).val();
		var $body = $(this).closest('.hd-filter-row__body');
		var isTaxonomy = type === 'taxonomy';
		var isAttribute = type === 'attribute';
		var hasTaxonomy = isTaxonomy || isAttribute;
		var isPriceRange = type === 'price_range';
		$body.find('.hd-cond-taxonomy-only').toggle(isTaxonomy);
		$body.find('.hd-cond-attribute-only').toggle(isAttribute);
		$body.find('.hd-cond-has-taxonomy').toggle(hasTaxonomy);
		$body.find('.hd-cond-display').toggle(hasTaxonomy);
		$body.find('.hd-taxonomy-select').prop('disabled', !isTaxonomy);
		$body.find('.hd-attribute-select').prop('disabled', !isAttribute);

		var mode = $body.find('select[name$="[mode]"]').val();
		$body.find('.hd-cond-slider').toggle(isPriceRange && mode === 'slider');
		$body.find('.hd-cond-price').toggle(isPriceRange && mode === 'custom_ranges');

		// Clear exclude-terms selection when type changes
		if (!hasTaxonomy) {
			$body.find('.hd-exclude-terms-select').val(null).trigger('change');
		}

		// Auto-generate ID for non-taxonomy types if empty or untouched
		var $row = $(this).closest('.hd-filter-row');
		var $idInput = $row.find('input[name$="[id]"]');
		if (!hasTaxonomy && type && (!$idInput.val() || $idInput.data('auto-generated'))) {
			$idInput.val(getUniqueId(type, $idInput)).data('auto-generated', true);
		}

		// Update type badge
		$(this).closest('.hd-filter-row').find('.hd-filter-row__type-badge').text(type);
	});

	// Toggle slider/custom ranges config when mode changes
	$repeater.on('change', 'select[name$="[mode]"]', function () {
		var $body = $(this).closest('.hd-filter-row__body');
		var isPriceRange = $body.closest('.hd-filter-row').find('.hd-filter-type-select').val() === 'price_range';
		var mode = $(this).val();
		$body.find('.hd-cond-slider').toggle(isPriceRange && mode === 'slider');
		$body.find('.hd-cond-price').toggle(isPriceRange && mode === 'custom_ranges');
	});

	// Update title on label change
	$repeater.on('input', 'input[name$="[label]"]', function () {
		// Only update title for the filter label, not price range labels
		if ($(this).hasClass('hd-price-range-label')) return;
		$(this)
			.closest('.hd-filter-row')
			.find('.hd-filter-row__title')
			.text($(this).val() || 'New Filter');
	});

	// Track manual edits to ID to prevent auto-overwriting
	$repeater.on('input', 'input[name$="[id]"]', function () {
		$(this).data('auto-generated', false);
	});

	// ── Price Range Sub-Repeater ──

	// Add price range row
	$repeater.on('click', '.hd-price-range-add', function () {
		var $row = $(this).closest('.hd-filter-row');
		var parentIndex = $row.data('index');
		var $td = $(this).closest('td');
		var $container = $td.find('.hd-price-ranges');
		var count = parseInt($container.attr('data-range-count') || '0', 10);

		var tmpl = $('#tmpl-hd-price-row')
			.html()
			.replace(/__INDEX__/g, parentIndex)
			.replace(/__RIDX__/g, count);

		$container.append(tmpl);
		$container.attr('data-range-count', count + 1);
	});

	// Remove price range row
	$repeater.on('click', '.hd-price-range-remove', function () {
		$(this).closest('.hd-price-range-row').remove();
	});

	// Init conditional visibility for existing rows
	$repeater.find('.hd-filter-type-select').trigger('change');

	// ── Select2 for Exclude Terms ──

	/**
	 * Init (or re-init) Select2 on an Exclude Terms select.
	 * @param {jQuery} $sel
	 */
	function initExcludeSelect2($sel) {
		if (!$sel.length || typeof $sel.select2 === 'undefined') return;

		var taxonomy = $sel.data('taxonomy') || '';
		var nonce = (window.wpApiSettings && window.wpApiSettings.nonce) || '';
		var apiBase = (window.wpApiSettings && window.wpApiSettings.root) || '/wp-json/';
		var endpoint = apiBase.replace(/\/$/, '') + '/hd/v1/wc-filter/terms';

		// Destroy previous instance if any
		if ($sel.hasClass('select2-hidden-accessible')) {
			$sel.select2('destroy');
		}

		$sel.select2({
			width: '100%',
			placeholder: $sel.data('placeholder') || 'Select terms…',
			allowClear: true,
			minimumInputLength: 0,
			ajax: taxonomy
				? {
						url: endpoint,
						headers: { 'X-WP-Nonce': nonce },
						data: function (params) {
							return { taxonomy: taxonomy, search: params.term || '', per_page: 50 };
						},
						processResults: function (resp) {
							var items = Array.isArray(resp) ? resp : resp.data || [];
							return { results: items };
						},
						cache: true,
					}
				: null,
			dropdownParent: $sel.closest('.hd-filter-row'),
		});
	}

	// Init on all existing rows
	$repeater.find('.hd-exclude-terms-select').each(function () {
		initExcludeSelect2($(this));
	});

	// Re-init when taxonomy or attribute select changes
	$repeater.on('change', '.hd-taxonomy-select, .hd-attribute-select', function () {
		var $body = $(this).closest('.hd-filter-row__body');
		var val = $(this).val();

		// Auto-generate ID for taxonomy types if empty or untouched
		var $idInput = $body.closest('.hd-filter-row').find('input[name$="[id]"]');
		if (val && (!$idInput.val() || $idInput.data('auto-generated'))) {
			$idInput.val(getUniqueId(val, $idInput)).data('auto-generated', true);
		}

		var $excl = $body.find('.hd-exclude-terms-select');
		$excl.data('taxonomy', val).val(null);
		initExcludeSelect2($excl);
	});

	// Helper: Ensure generated ID is unique within the repeater
	function getUniqueId(base, $skipInput) {
		var suffix = '';
		var counter = 1;
		var exists = true;
		var currentVal = '';

		while (exists) {
			currentVal = base + suffix;
			exists = false;
			$repeater.find('input[name$="[id]"]').each(function () {
				if ($(this).is($skipInput)) return;
				if ($(this).val() === currentVal) exists = true;
			});
			if (exists) {
				counter++;
				suffix = '_' + counter;
			}
		}
		return currentVal;
	}
})(jQuery);
