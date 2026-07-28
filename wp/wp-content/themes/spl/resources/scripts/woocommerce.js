// woocommerce.js — WooCommerce-specific custom scripts
// Conditionally loaded on WC pages (not a chunk — a separate entry point).
// Module chunks (gallery, swatches, etc.) are lazy-loaded via core/modules/woocommerce/.
// CSS: woocommerce.scss is a separate Vite entry, enqueued by PHP independently.

const run = () => {
	// ── Vanilla JS (no jQuery dependency) ──

	// ── 1. PRODUCT GALLERY INTERACTIONS ──
	const galleryThumbs = document.getElementById('sp-gallery-thumbs');
	const mainImg = document.getElementById('sp-main-img');
	if (galleryThumbs && mainImg) {
		const thumbs = galleryThumbs.querySelectorAll('.sp-gallery__thumb');
		const prevBtn = document.querySelector('.sp-gallery__nav--prev');
		const nextBtn = document.querySelector('.sp-gallery__nav--next');

		// Handle thumb click
		thumbs.forEach(thumb => {
			thumb.addEventListener('click', (e) => {
				e.preventDefault();
				e.stopPropagation(); // Prevent opening lightbox directly when thumb is clicked

				thumbs.forEach(t => t.classList.remove('active'));
				thumb.classList.add('active');
				if (thumb.dataset.img) {
					mainImg.style.opacity = '0.3';
					mainImg.src = thumb.dataset.img;
					
					// Sync lightbox link href and pswp dimensions
					const mainLink = document.getElementById('sp-main-link');
					if (mainLink) {
						mainLink.href = thumb.getAttribute('href') || thumb.dataset.img;
						if (thumb.dataset.pswpWidth) mainLink.dataset.pswpWidth = thumb.dataset.pswpWidth;
						if (thumb.dataset.pswpHeight) mainLink.dataset.pswpHeight = thumb.dataset.pswpHeight;
					}

					setTimeout(() => {
						mainImg.style.opacity = '1';
					}, 150);
				}
			});
		});

		// Handle Zoom button click
		const zoomBtn = document.getElementById('sp-zoom-btn');
		const mainLink = document.getElementById('sp-main-link');
		if (zoomBtn && mainLink) {
			zoomBtn.addEventListener('click', (e) => {
				e.preventDefault();
				mainLink.click();
			});
		}

		// Handle prev/next arrows
		const cycleThumb = (direction) => {
			const activeIndex = Array.from(thumbs).findIndex(t => t.classList.contains('active'));
			if (activeIndex === -1) return;

			let nextIndex = activeIndex + direction;
			if (nextIndex >= thumbs.length) nextIndex = 0;
			if (nextIndex < 0) nextIndex = thumbs.length - 1;

			thumbs[nextIndex].click();
			
			if (galleryThumbs.swiper) {
				galleryThumbs.swiper.slideTo(nextIndex);
			} else {
				thumbs[nextIndex].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
			}
		};

		if (prevBtn) prevBtn.addEventListener('click', () => cycleThumb(-1));
		if (nextBtn) nextBtn.addEventListener('click', () => cycleThumb(1));
	}

	// ── 2. QUANTITY SELECTOR ──
	const qtyInput = document.getElementById('qty-input');
	const qtyMinus = document.getElementById('qty-minus');
	const qtyPlus = document.getElementById('qty-plus');
	if (qtyInput) {
		if (qtyMinus) {
			qtyMinus.addEventListener('click', (e) => {
				e.preventDefault();
				const val = parseInt(qtyInput.value, 10) || 1;
				if (val > 1) {
					qtyInput.value = val - 1;
					qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
				}
			});
		}
		if (qtyPlus) {
			qtyPlus.addEventListener('click', (e) => {
				e.preventDefault();
				const val = parseInt(qtyInput.value, 10) || 1;
				qtyInput.value = val + 1;
				qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
			});
		}
	}

	// ── 3. PRODUCT TABS TOGGLE ──
	const tabsContainer = document.querySelector('.sp-tabs');
	if (tabsContainer) {
		const tabs = tabsContainer.querySelectorAll('.sp-tabs__tab');
		const panels = tabsContainer.querySelectorAll('.sp-tabs__panel');

		tabs.forEach(tab => {
			tab.addEventListener('click', () => {
				const targetTab = tab.dataset.tab;
				if (!targetTab) return;

				// Toggle tabs active
				tabs.forEach(t => {
					t.classList.remove('active');
					t.setAttribute('aria-selected', 'false');
				});
				tab.classList.add('active');
				tab.setAttribute('aria-selected', 'true');

				// Toggle panels visibility
				panels.forEach(p => {
					if (p.id === `tab-${targetTab}`) {
						p.classList.add('active');
					} else {
						p.classList.remove('active');
					}
				});
			});
		});
	}

	// ── 4. VARIABLE PRODUCT SELECTOR ──
	const variationsContainer = document.getElementById('sp-variations');
	if (variationsContainer) {
		const productId = variationsContainer.dataset.productId;
		const variations = JSON.parse(variationsContainer.dataset.variations || '[]');
		const variationIdInput = document.getElementById('sp-variation-id');
		const priceBox = document.getElementById('sp-price-box');
		const resetContainer = document.getElementById('sp-variations-reset');

		const fields = variationsContainer.querySelectorAll('.sp-variations__options');

		const updateSelectedVariation = () => {
			const selections = {};
			let allSelected = true;

			fields.forEach(field => {
				const attrName = field.dataset.attribute;
				const activeBtn = field.querySelector('.sp-variations__btn.active');
				const parentField = field.closest('.sp-variations__field');
				const labelValueEl = parentField ? parentField.querySelector('.sp-variations__label-value') : null;
				if (activeBtn) {
					selections[attrName] = activeBtn.dataset.value;

					// Update active label value display
					if (labelValueEl) {
						labelValueEl.textContent = activeBtn.textContent || activeBtn.innerText;
					}

					// Dynamically sync selected battery option text to specs strip (concise)
					if (attrName.includes('ac-quy') || attrName.includes('pin')) {
						const btnText = activeBtn.textContent || activeBtn.innerText;
						const shortText = btnText.replace(/^(Ắc-quy:|Pin Lithium:|Ắc quy:|Pin:)\s*/i, '').trim();
						const specBatteryVal = document.getElementById('spec-battery-val');
						if (specBatteryVal) {
							specBatteryVal.textContent = shortText;
						}
					}
				} else {
					if (labelValueEl) {
						labelValueEl.textContent = '';
					}
					allSelected = false;
				}
			});

			if (!allSelected) return;

			// Find matching variation
			const matched = variations.find(v => {
				return Object.keys(v.attributes).every(key => {
					return !v.attributes[key] || v.attributes[key] === selections[key];
				});
			});

			if (matched) {
				if (variationIdInput) variationIdInput.value = matched.variation_id;

				// Update price
				if (priceBox) {
					const priceRow = priceBox.querySelector('.price-row');
					if (priceRow) {
						let priceHtml = `<span class="sp-info__price">${matched.spl_price_html}</span>`;
						if (matched.spl_old_price_html) {
							priceHtml += ` <span class="sp-info__old-price">${matched.spl_old_price_html}</span>`;
							const regVal = parseFloat(matched.display_regular_price);
							const curVal = parseFloat(matched.display_price);
							if (regVal > curVal) {
								const savings = regVal - curVal;
								const savingsFormatted = savings >= 1000000 
									? (savings / 1000000).toFixed(1).replace('.0', '') + 'tr'
									: savings.toLocaleString('vi-VN') + 'đ';
								priceHtml += ` <span class="sp-info__discount-tag">Tiết kiệm ${savingsFormatted}</span>`;
							}
						}
						priceRow.innerHTML = priceHtml;
					}
				}

				// Update main image if variation has an image
				if (mainImg && matched.image && matched.image.src) {
					mainImg.style.opacity = '0.3';
					mainImg.src = matched.image.src;

					// Sync lightbox link href
					const mainLink = document.getElementById('sp-main-link');
					if (mainLink) {
						mainLink.href = matched.image.src;
					}

					setTimeout(() => {
						mainImg.style.opacity = '1';
					}, 150);

					// Also toggle thumbnail active state if thumbnail matches
					if (galleryThumbs) {
						const thumbs = galleryThumbs.querySelectorAll('.sp-gallery__thumb');
						thumbs.forEach(t => {
							if (t.dataset.img === matched.image.src) {
								thumbs.forEach(other => other.classList.remove('active'));
								t.classList.add('active');
								
								if (galleryThumbs.swiper) {
									const idx = Array.from(thumbs).indexOf(t);
									if (idx !== -1) {
										galleryThumbs.swiper.slideTo(idx);
									}
								} else {
									t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
								}
							}
						});
					}
				}

				if (resetContainer) resetContainer.style.display = 'flex';
			} else {
				if (variationIdInput) variationIdInput.value = '0';
				if (resetContainer) resetContainer.style.display = 'none';
			}
		};

		// Handle button click
		fields.forEach(field => {
			const buttons = field.querySelectorAll('.sp-variations__btn');
			buttons.forEach(btn => {
				btn.addEventListener('click', () => {
					buttons.forEach(b => b.classList.remove('active'));
					btn.classList.add('active');
					updateSelectedVariation();
				});
			});
		});

		// Reset selection
		if (resetContainer) {
			const clearBtn = resetContainer.querySelector('button');
			if (clearBtn) {
				clearBtn.addEventListener('click', () => {
					fields.forEach(field => {
						const buttons = field.querySelectorAll('.sp-variations__btn');
						buttons.forEach((b, idx) => {
							if (idx === 0) {
								b.classList.add('active');
							} else {
								b.classList.remove('active');
							}
						});
					});
					updateSelectedVariation();
				});
			}
		}

		// Initial check on load
		updateSelectedVariation();
	}

	// Handle Grid/List View Toggle
	const viewBtns = document.querySelectorAll('.archive-view-btn');
	const productsGrid = document.querySelector('.products-grid');
	if (viewBtns.length && productsGrid) {
		viewBtns.forEach(btn => {
			btn.addEventListener('click', (e) => {
				e.preventDefault();
				viewBtns.forEach(b => b.classList.remove('active'));
				btn.classList.add('active');
				const view = btn.dataset.view;
				if (view === 'list') {
					productsGrid.classList.add('is-list-view');
				} else {
					productsGrid.classList.remove('is-list-view');
				}
			});
		});
	}

	// ── 4. COLLAPSE/EXPAND DESCRIPTION ──
	const descWrapper = document.getElementById('sp-desc-wrapper');
	const descContent = document.getElementById('sp-desc-content');
	const descToggle = document.getElementById('sp-desc-toggle-btn');

	if (descWrapper && descContent && descToggle) {
		const btn = descToggle.querySelector('.btn-show-more');
		
		if (btn) {
			btn.addEventListener('click', (e) => {
				e.preventDefault();
				const currentHeight = descContent.scrollHeight;
				if (descWrapper.classList.contains('is-collapsed')) {
					descWrapper.classList.remove('is-collapsed');
					descWrapper.style.maxHeight = (currentHeight + 100) + 'px'; // Expand fully
					btn.textContent = 'Thu gọn';
					descToggle.classList.add('is-expanded');
				} else {
					descWrapper.classList.add('is-collapsed');
					descWrapper.style.maxHeight = '400px'; // Collapse back
					btn.textContent = 'Xem thêm';
					descToggle.classList.remove('is-expanded');
					
					// Smooth scroll to top of description wrapper
					descWrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
				}
			});

			// Use ResizeObserver to detect image loading/height updates
			const ro = new ResizeObserver((entries) => {
				for (let entry of entries) {
					const contentHeight = entry.target.scrollHeight;
					if (contentHeight > 450) {
						if (!descToggle.classList.contains('is-expanded') && !descWrapper.classList.contains('is-collapsed')) {
							descWrapper.classList.add('is-collapsed');
							descWrapper.style.maxHeight = '400px';
							descToggle.style.display = 'block';
						}
					} else {
						if (!descToggle.classList.contains('is-expanded')) {
							descWrapper.classList.remove('is-collapsed');
							descWrapper.style.maxHeight = '';
							descToggle.style.display = 'none';
						}
					}
				}
			});
			ro.observe(descContent);
		}
	}

	// ── jQuery-dependent WC integration ──
	if (window.jQuery) {
		jQuery(() => {
			// jQuery-dependent code here if needed
		});
	}
};

document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', run, { once: true }) : run();
