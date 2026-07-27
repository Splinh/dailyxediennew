/**
 * dailyxedien.vn — global UI interactions (header/footer).
 * Plain vanilla JS, enqueued directly (no build step needed).
 * Selectors: data-drawer*, data-cat-trigger, data-scroll-top, #back-to-top,
 *            data-cart-open/close, data-cat-panel-open/close.
 */
( function () {
	'use strict';

	const onReady = ( fn ) =>
		document.readyState !== 'loading'
			? fn()
			: document.addEventListener( 'DOMContentLoaded', fn );

	onReady( function () {
		const body = document.body;

		/* ---------- Mobile drawer ---------- */
		const drawer = document.querySelector( '[data-drawer]' );
		const overlay = document.querySelector( '[data-drawer-overlay]' );

		const openDrawer = ( focusSearch ) => {
			if ( ! drawer || ! overlay ) return;
			overlay.classList.remove( 'hidden' );
			// next frame for transition
			requestAnimationFrame( () => {
				overlay.classList.remove( 'opacity-0' );
				drawer.classList.remove( '-translate-x-full' );
			} );
			body.classList.add( 'no-scroll' );
			if ( focusSearch ) {
				const s = drawer.querySelector( '[data-drawer-search]' );
				if ( s ) setTimeout( () => s.focus(), 300 );
			}
		};

		const closeDrawer = () => {
			if ( ! drawer || ! overlay ) return;
			overlay.classList.add( 'opacity-0' );
			drawer.classList.add( '-translate-x-full' );
			body.classList.remove( 'no-scroll' );
			setTimeout( () => overlay.classList.add( 'hidden' ), 300 );
		};

		document.querySelectorAll( '[data-drawer-open]' ).forEach( ( btn ) =>
			btn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				openDrawer( btn.hasAttribute( 'data-focus-search' ) );
			} )
		);
		document
			.querySelectorAll( '[data-drawer-close]' )
			.forEach( ( btn ) => btn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				closeDrawer();
			} ) );
		if ( overlay ) overlay.addEventListener( 'click', closeDrawer );
		// close drawer when a link inside is clicked
		if ( drawer )
			drawer.addEventListener( 'click', ( e ) => {
				if ( e.target.closest( 'a' ) ) closeDrawer();
			} );

		/* ---------- Live AJAX Search ---------- */
		const setupLiveSearch = ( searchInput ) => {
			if ( ! searchInput || searchInput._liveSearchInited ) return;
			searchInput._liveSearchInited = true;

			const wrapper = searchInput.closest( '[role="search"]' ) || searchInput.parentElement;
			if ( ! wrapper ) return;

			// Ensure wrapper has relative positioning
			if ( getComputedStyle( wrapper ).position === 'static' ) {
				wrapper.style.position = 'relative';
			}

			// Create dropdown container
			let dropdown = wrapper.querySelector( '.ajax-search-dropdown' );
			if ( ! dropdown ) {
				dropdown = document.createElement( 'div' );
				dropdown.className = 'ajax-search-dropdown absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden z-50 transition-all duration-200 opacity-0 translate-y-2 pointer-events-none max-h-[420px] overflow-y-auto';
				wrapper.appendChild( dropdown );
			}

			let debounceTimer = null;
			let abortController = null;

			const showDropdown = () => {
				dropdown.classList.remove( 'opacity-0', 'translate-y-2', 'pointer-events-none' );
				dropdown.classList.add( 'opacity-100', 'translate-y-0', 'pointer-events-auto' );
			};

			const hideDropdown = () => {
				dropdown.classList.add( 'opacity-0', 'translate-y-2', 'pointer-events-none' );
				dropdown.classList.remove( 'opacity-100', 'translate-y-0', 'pointer-events-auto' );
			};

			const performSearch = async ( query ) => {
				if ( abortController ) abortController.abort();
				abortController = new AbortController();

				dropdown.innerHTML = `
					<div class="p-6 text-center text-slate-400 text-sm flex items-center justify-center gap-2">
						<svg class="w-4 h-4 animate-spin text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg>
						<span>Đang tìm kiếm sản phẩm...</span>
					</div>
				`;
				showDropdown();

				try {
					const apiUrl = ( window.hdConfig?.restApiUrl || '/wp-json/spl/v1/' ) + 'search?q=' + encodeURIComponent( query );
					const res = await fetch( apiUrl, { signal: abortController.signal } );
					const json = await res.json();

					if ( ! json.success || ! json.products || json.products.length === 0 ) {
						dropdown.innerHTML = `
							<div class="p-6 text-center text-slate-500 text-sm">
								<p class="font-bold text-slate-700">Không tìm thấy sản phẩm nào</p>
								<p class="text-xs text-slate-400 mt-1">Thử từ khóa khác như "xe 50cc", "xe điện"...</p>
							</div>
						`;
						return;
					}

					let itemsHtml = json.products.map( ( p ) => `
						<a href="${p.permalink}" class="flex items-center gap-3 py-2 px-3 hover:bg-slate-50 transition-colors group">
							<div class="w-10 h-10 bg-slate-50 border border-slate-100 rounded-md shrink-0 p-0.5 flex items-center justify-center overflow-hidden">
								${p.image ? `<img src="${p.image}" alt="${p.title}" class="max-h-full max-w-full object-contain" loading="lazy" />` : ''}
							</div>
							<div class="flex-grow min-w-0">
								<h4 class="text-xs font-bold text-slate-800 truncate group-hover:text-primary transition-colors leading-tight">${p.title}</h4>
								<div class="text-[11px] font-semibold text-slate-900 mt-0.5 flex items-center gap-1.5 [&_ins]:text-red-600 [&_ins]:no-underline [&_ins]:font-bold [&_del]:text-slate-400 [&_del]:font-normal">
									${p.price_html}
								</div>
							</div>
						</a>
					` ).join( '' );

					if ( json.all_url ) {
						itemsHtml += `
							<a href="${json.all_url}" class="block bg-slate-50 hover:bg-primary-50 text-primary font-bold text-xs py-2 px-3 text-center transition-colors border-t border-slate-100">
								Xem tất cả kết quả cho "${query}" &rarr;
							</a>
						`;
					}

					dropdown.innerHTML = itemsHtml;
				} catch ( err ) {
					if ( err.name !== 'AbortError' ) {
						hideDropdown();
					}
				}
			};

			searchInput.addEventListener( 'input', () => {
				const q = searchInput.value.trim();
				clearTimeout( debounceTimer );
				if ( q.length < 2 ) {
					hideDropdown();
					return;
				}
				debounceTimer = setTimeout( () => performSearch( q ), 250 );
			} );

			searchInput.addEventListener( 'focus', () => {
				const q = searchInput.value.trim();
				if ( q.length >= 2 ) {
					performSearch( q );
				}
			} );

			document.addEventListener( 'click', ( e ) => {
				if ( ! wrapper.contains( e.target ) ) {
					hideDropdown();
				}
			} );

			document.addEventListener( 'keydown', ( e ) => {
				if ( e.key === 'Escape' ) {
					hideDropdown();
				}
			} );
		};

		document.querySelectorAll( '#header-search, [data-drawer-search], input[type="search"][name="s"]' ).forEach( setupLiveSearch );

		/* ---------- Category dropdown (touch/click) ---------- */
		const catMenu = document.querySelector( '[data-cat-menu]' );
		const catTrigger = document.querySelector( '[data-cat-trigger]' );
		if ( catMenu && catTrigger ) {
			const panel = catMenu.querySelector( '[role="menu"]' );
			catTrigger.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				const open = catMenu.classList.toggle( 'is-open' );
				catTrigger.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				if ( panel ) {
					panel.classList.toggle( 'opacity-100', open );
					panel.classList.toggle( 'translate-y-0', open );
					panel.classList.toggle( 'pointer-events-auto', open );
				}
			} );
			document.addEventListener( 'click', ( e ) => {
				if ( ! catMenu.contains( e.target ) ) {
					catMenu.classList.remove( 'is-open' );
					catTrigger.setAttribute( 'aria-expanded', 'false' );
					if ( panel )
						panel.classList.remove(
							'opacity-100',
							'translate-y-0',
							'pointer-events-auto'
						);
				}
			} );
		}

		/* ---------- Back to top ---------- */
		const topBtn = document.querySelector( '#back-to-top, [data-scroll-top]' );
		if ( topBtn ) {
			const toggle = () =>
				topBtn.classList.toggle( 'show', window.scrollY > 600 );
			toggle();
			window.addEventListener( 'scroll', toggle, { passive: true } );
			topBtn.addEventListener( 'click', () =>
				window.scrollTo( { top: 0, behavior: 'smooth' } )
			);
		}

		/* ---------- Cart Modal ---------- */
		const cartModal = document.querySelector( '[data-cart-modal]' );
		const cartOverlay = document.querySelector( '.dxd-cart-overlay' );

		const openCart = () => {
			if ( ! cartModal ) return;
			cartModal.classList.add( 'is-open' );
			cartModal.setAttribute( 'aria-hidden', 'false' );
			if ( cartOverlay ) cartOverlay.classList.add( 'is-open' );
			body.classList.add( 'no-scroll' );
		};

		const closeCart = () => {
			if ( ! cartModal ) return;
			cartModal.classList.remove( 'is-open' );
			cartModal.setAttribute( 'aria-hidden', 'true' );
			if ( cartOverlay ) cartOverlay.classList.remove( 'is-open' );
			body.classList.remove( 'no-scroll' );
		};

		document.querySelectorAll( '[data-cart-open]' ).forEach( ( btn ) =>
			btn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				openCart();
			} )
		);

		document.querySelectorAll( '[data-cart-close]' ).forEach( ( btn ) =>
			btn.addEventListener( 'click', closeCart )
		);

		// Mini-cart AJAX quantity update (±1 buttons).
		if ( cartModal ) {
			cartModal.addEventListener( 'click', ( e ) => {
				const minus = e.target.closest( '[data-mini-cart-minus]' );
				const plus = e.target.closest( '[data-mini-cart-plus]' );
				if ( ! minus && ! plus ) return;

				const row = ( minus || plus ).closest( '[data-cart-key]' );
				if ( ! row ) return;

				const key = row.dataset.cartKey;
				const input = row.querySelector( 'input[type="number"]' );
				if ( ! input ) return;

				let qty = parseInt( input.value, 10 ) || 0;
				qty = minus ? Math.max( 0, qty - 1 ) : qty + 1;
				input.value = qty;

				const cfg = window.splMiniCart || {};
				if ( ! cfg.ajaxUrl ) return;

				const fd = new FormData();
				fd.append( 'action', 'spl_update_mini_cart_qty' );
				fd.append( 'nonce', cfg.nonce || '' );
				fd.append( 'cart_item_key', key );
				fd.append( 'quantity', qty );

				fetch( cfg.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } )
					.then( ( r ) => r.json() )
					.then( ( data ) => {
						if ( data && data.fragments ) {
							Object.entries( data.fragments ).forEach( ( [ selector, html ] ) => {
								document.querySelectorAll( selector ).forEach( ( el ) => {
									el.outerHTML = html;
								} );
							} );
						}
					} )
					.catch( () => {} );
			} );
		}

		/* ---------- Unified Slide-Up Panels Helper ---------- */
		const registerSlidePanel = ( panelId, openSelector, closeSelector ) => {
			const panel = document.getElementById( panelId );
			const overlay = document.getElementById( panelId + '-overlay' );
			if ( ! panel || ! overlay ) return () => {};

			const openPanel = () => {
				overlay.style.display = 'block';
				panel.style.display = 'block';
				body.classList.add( 'no-scroll' );
				requestAnimationFrame( () => {
					requestAnimationFrame( () => {
						overlay.classList.add( 'open' );
						panel.classList.add( 'open' );
					} );
				} );
			};

			const closePanel = () => {
				overlay.classList.remove( 'open' );
				panel.classList.remove( 'open' );
				
				setTimeout( () => {
					overlay.style.display = 'none';
					panel.style.display = 'none';
					
					const anyOpen = Array.from( document.querySelectorAll( '#category-panel, #news-panel, #dealer-panel, #contact-panel, [data-cart-modal]' ) ).some( p => p.classList.contains( 'open' ) || p.classList.contains( 'is-open' ) );
					if ( ! anyOpen ) {
						body.classList.remove( 'no-scroll' );
					}
				}, 300 );
			};

			document.querySelectorAll( openSelector ).forEach( btn => btn.addEventListener( 'click', ( e ) => { e.preventDefault(); openPanel(); } ) );
			document.querySelectorAll( closeSelector ).forEach( btn => btn.addEventListener( 'click', closePanel ) );
			overlay.addEventListener( 'click', closePanel );

			return closePanel;
		};

		const closeCatPanel = registerSlidePanel( 'category-panel', '[data-cat-panel-open]', '[data-cat-panel-close]' );
		const closeNewsPanel = registerSlidePanel( 'news-panel', '[data-news-panel-open]', '[data-news-panel-close]' );
		const closeDealerPanel = registerSlidePanel( 'dealer-panel', '[data-dealer-panel-open]', '[data-dealer-panel-close]' );
		const closeContactPanel = registerSlidePanel( 'contact-panel', '[data-contact-panel-open]', '[data-contact-panel-close]' );

		window.switchCategoryTab = function( e, tabId ) {
			e.preventDefault();
			const wrap = e.currentTarget.closest( '#category-panel' );
			if ( ! wrap ) return;

			wrap.querySelectorAll( '.cat-tab-item' ).forEach( item => item.classList.remove( 'active' ) );
			e.currentTarget.classList.add( 'active' );

			wrap.querySelectorAll( '.cat-tab-panel' ).forEach( panel => panel.classList.remove( 'active' ) );
			const target = document.getElementById( 'cat-tab-panel-' + tabId );
			if ( target ) {
				target.classList.add( 'active' );
				const scrollContainer = wrap.querySelector( '.cat-products-right' );
				if ( scrollContainer ) scrollContainer.scrollTop = 0;
			}
		};

		window.switchNewsTab = function( e, tabId ) {
			e.preventDefault();
			const wrap = e.currentTarget.closest( '#news-panel' );
			if ( ! wrap ) return;

			wrap.querySelectorAll( '.news-tab-item' ).forEach( item => item.classList.remove( 'active' ) );
			e.currentTarget.classList.add( 'active' );

			wrap.querySelectorAll( '.news-tab-panel' ).forEach( panel => panel.classList.remove( 'active' ) );
			const target = document.getElementById( 'news-tab-panel-' + tabId );
			if ( target ) {
				target.classList.add( 'active' );
				const scrollContainer = wrap.querySelector( '.news-articles-right' );
				if ( scrollContainer ) scrollContainer.scrollTop = 0;
			}
		};

		window.switchDealerTab = function( e, tabId ) {
			e.preventDefault();
			const wrap = e.currentTarget.closest( '#dealer-panel' );
			if ( ! wrap ) return;

			wrap.querySelectorAll( '.dealer-tab-item' ).forEach( item => item.classList.remove( 'active' ) );
			e.currentTarget.classList.add( 'active' );

			wrap.querySelectorAll( '.dealer-tab-panel' ).forEach( panel => panel.classList.remove( 'active' ) );
			const target = document.getElementById( 'dealer-tab-panel-' + tabId );
			if ( target ) {
				target.classList.add( 'active' );
				const scrollContainer = wrap.querySelector( '.dealer-stores-right' );
				if ( scrollContainer ) scrollContainer.scrollTop = 0;
			}
		};

		/* ---------- Single Product: Quantity Stepper ---------- */
		// Commented out to prevent conflict with woocommerce.js
		/*
		const qtyInput = document.getElementById( 'qty-input' );
		const qtyMinus = document.getElementById( 'qty-minus' );
		const qtyPlus = document.getElementById( 'qty-plus' );
		if ( qtyInput ) {
			const clamp = ( v ) => Math.min( parseInt( qtyInput.max || '99', 10 ), Math.max( parseInt( qtyInput.min || '1', 10 ), v || 1 ) );
			if ( qtyMinus ) qtyMinus.addEventListener( 'click', () => { qtyInput.value = clamp( parseInt( qtyInput.value, 10 ) - 1 ); } );
			if ( qtyPlus ) qtyPlus.addEventListener( 'click', () => { qtyInput.value = clamp( parseInt( qtyInput.value, 10 ) + 1 ); } );
		}
		*/

		/* ---------- Single Product: Variation Selector ---------- */
		// Commented out to prevent conflict with woocommerce.js
		/*
		const variationsWrap = document.getElementById( 'sp-variations' );
		if ( variationsWrap ) {
			const variations = JSON.parse( variationsWrap.dataset.variations || '[]' );
			const variationIdInput = document.getElementById( 'sp-variation-id' );
			const priceBox = document.getElementById( 'sp-price-box' );
			const resetWrap = document.getElementById( 'sp-variations-reset' );
			const originalPriceHtml = priceBox ? priceBox.innerHTML : '';

			const getSelections = () => {
				const sel = {};
				variationsWrap.querySelectorAll( '.sp-variations__options' ).forEach( ( group ) => {
					const attr = group.dataset.attribute;
					const active = group.querySelector( '.sp-variations__btn.active' );
					sel[ attr ] = active ? active.dataset.value : '';
				} );
				return sel;
			};

			const findVariation = ( selections ) => {
				return variations.find( ( v ) => {
					return Object.entries( selections ).every( ( [ key, val ] ) => {
						if ( ! val ) return true;
						const vAttr = v.attributes[ key ];
						return vAttr === '' || vAttr === val;
					} );
				} );
			};

			const updateVariation = () => {
				const selections = getSelections();
				const allSelected = Object.values( selections ).every( ( v ) => v !== '' );
				const matched = allSelected ? findVariation( selections ) : null;
				if ( matched && variationIdInput ) {
					variationIdInput.value = matched.variation_id;
					if ( priceBox ) {
						const priceHtml = matched.spl_price_html || matched.price_html || originalPriceHtml;
						const oldPriceHtml = matched.spl_old_price_html ? '<span class="sp-info__old-price">' + matched.spl_old_price_html + '</span>' : '';
						priceBox.innerHTML = '<span class="sp-info__price">' + priceHtml + '</span>' + oldPriceHtml;
					}
					if ( matched.image && matched.image.url ) {
						const mainImg = document.getElementById( 'sp-main-img' );
						if ( mainImg ) mainImg.src = matched.image.url;
					}
				} else {
					if ( variationIdInput ) variationIdInput.value = '';
					if ( priceBox ) priceBox.innerHTML = originalPriceHtml;
				}
				const hasSelection = Object.values( selections ).some( ( v ) => v !== '' );
				if ( resetWrap ) resetWrap.style.display = hasSelection ? '' : 'none';
			};

			variationsWrap.querySelectorAll( '.sp-variations__btn' ).forEach( ( btn ) => {
				btn.addEventListener( 'click', () => {
					const group = btn.closest( '.sp-variations__options' );
					const wasActive = btn.classList.contains( 'active' );
					group.querySelectorAll( '.sp-variations__btn' ).forEach( ( b ) => b.classList.remove( 'active' ) );
					if ( ! wasActive ) btn.classList.add( 'active' );
					updateVariation();
				} );
			} );

			const clearBtn = variationsWrap.querySelector( '.sp-variations__clear' );
			if ( clearBtn ) {
				clearBtn.addEventListener( 'click', () => {
					variationsWrap.querySelectorAll( '.sp-variations__btn' ).forEach( ( b ) => b.classList.remove( 'active' ) );
					updateVariation();
				} );
			}
			updateVariation();
		}
		*/

		/* ---------- Add to Cart (AJAX) ---------- */
		const getQty = () => {
			const el = document.getElementById( 'qty-input' ) || document.querySelector( 'input.qty, input[name="quantity"]' );
			return el ? Math.max( 1, parseInt( el.value, 10 ) || 1 ) : 1;
		};
		const getVariationId = () => {
			const el = document.getElementById( 'sp-variation-id' );
			return el ? el.value : '';
		};
		const getAttrObj = () => {
			const vw = document.getElementById( 'sp-variations' );
			if ( ! vw ) return {};
			const obj = {};
			vw.querySelectorAll( '.sp-variations__options' ).forEach( ( group ) => {
				const attr = group.dataset.attribute;
				const active = group.querySelector( '.sp-variations__btn.active' );
				if ( attr && active ) obj[ attr ] = active.dataset.value;
			} );
			return obj;
		};

		const wcAjaxAddToCart = ( productId, qty, variationId, attrs, btn ) => {
			const cfg = window.splConfig || {};
			const wcAjaxUrl = ( cfg.wcAjaxUrl || '/?wc-ajax=%%endpoint%%' ).replace( '%%endpoint%%', 'add_to_cart' );

			const fd = new URLSearchParams();
			fd.append( 'product_id', variationId || productId );
			fd.append( 'quantity', String( qty ) );
			if ( variationId ) {
				fd.append( 'variation_id', variationId );
				if ( attrs ) Object.entries( attrs ).forEach( ( [ k, v ] ) => fd.append( k, v ) );
			}

			const origHtml = btn.innerHTML;
			btn.disabled = true;
			btn.style.opacity = '0.7';
			btn.innerHTML = '<span class="spl-spinner"></span>';

			fetch( wcAjaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
				body: fd,
				credentials: 'same-origin',
			} )
				.then( ( r ) => {
					if ( ! r.ok ) throw new Error( r.status );
					return r.json();
				} )
				.then( ( data ) => {
					if ( data.error && data.product_url ) {
						window.location.href = data.product_url;
						return;
					}
					if ( data.fragments ) {
						Object.entries( data.fragments ).forEach( ( [ sel, html ] ) => {
							document.querySelectorAll( sel ).forEach( ( el ) => {
								el.outerHTML = html;
							} );
						} );
					}
					
					// Success state: show checkmark briefly, then restore original
					btn.innerHTML = '<svg class="w-3.5 h-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
					setTimeout( () => {
						btn.innerHTML = origHtml;
						btn.disabled = false;
						btn.style.opacity = '';
					}, 1200 );
					
					openCart();
				} )
				.catch( () => {
					btn.innerHTML = origHtml;
					btn.disabled = false;
					btn.style.opacity = '';
					window.location.href = location.origin + location.pathname + '?add-to-cart=' + ( variationId || productId ) + '&quantity=' + qty;
				} );
		};

		document.addEventListener( 'click', ( e ) => {
			const btn = e.target.closest( '.add-cart-btn, #sp-add-cart' );
			if ( ! btn ) return;

			e.preventDefault();
			const id = btn.dataset.productId;
			if ( ! id ) return;
			const qty = btn.id === 'sp-add-cart' ? getQty() : 1;
			const type = btn.dataset.productType || 'simple';
			if ( type === 'variable' ) {
				const vid = getVariationId();
				if ( ! vid ) {
					btn.style.animation = 'ring 0.5s';
					setTimeout( () => ( btn.style.animation = '' ), 600 );
					return;
				}
				wcAjaxAddToCart( id, qty, vid, getAttrObj(), btn );
			} else {
				wcAjaxAddToCart( id, qty, null, null, btn );
			}
		} );

		/* ---------- Buy Now ---------- */
		const buyNow = document.getElementById( 'sp-buy-now' );
		if ( buyNow ) {
			buyNow.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				const id = buyNow.dataset.productId;
				const checkout = buyNow.dataset.checkout || location.origin;
				const type = buyNow.dataset.productType || 'simple';
				if ( ! id ) return;
				let url = checkout + ( checkout.indexOf( '?' ) > -1 ? '&' : '?' ) + 'add-to-cart=' + id + '&quantity=' + getQty();
				if ( type === 'variable' ) {
					const vid = getVariationId();
					if ( ! vid ) {
						buyNow.style.animation = 'ring 0.5s';
						setTimeout( () => ( buyNow.style.animation = '' ), 600 );
						return;
					}
					url += '&variation_id=' + vid;
					const attrs = getAttrObj();
					Object.entries( attrs ).forEach( ( [ k, v ] ) => {
						url += '&' + encodeURIComponent( k ) + '=' + encodeURIComponent( v );
					} );
				}
				window.location.href = url;
			} );
		}

		/* ---------- Global Quantity Stepper (WC quantity-input override) ---------- */
		document.body.addEventListener( 'click', ( e ) => {
			const minus = e.target.closest( '.dxd-qty-minus' );
			const plus = e.target.closest( '.dxd-qty-plus' );
			if ( ! minus && ! plus ) return;

			const wrap = ( minus || plus ).closest( '.quantity' );
			if ( ! wrap ) return;

			const input = wrap.querySelector( '.qty, input[type="number"]' );
			if ( ! input ) return;

			const min = parseInt( input.min || '0', 10 );
			const max = parseInt( input.max || '9999', 10 );
			const step = parseInt( input.step || '1', 10 );
			let val = parseInt( input.value, 10 ) || min;

			val = minus ? val - step : val + step;
			val = Math.max( min, Math.min( max, val ) );
			input.value = val;

			// Trigger change event for WC JS (cart update button enable)
			input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		} );

		// Auto-update cart page when quantity changes
		if ( document.querySelector( '.woocommerce-cart-form' ) ) {
			let updateTimeout;
			document.body.addEventListener( 'change', ( e ) => {
				const qtyInput = e.target.closest( '.woocommerce-cart-form .qty' );
				if ( ! qtyInput ) return;

				clearTimeout( updateTimeout );
				updateTimeout = setTimeout( () => {
					const updateButton = document.querySelector( '[name="update_cart"]' );
					if ( updateButton ) {
						updateButton.disabled = false;
						updateButton.click();
					}
				}, 600 );
			} );
		}


		/* ---------- Single Product Tabs Switcher ---------- */
		const tabsNav = document.querySelector( '.sp-tabs__nav' );
		if ( tabsNav ) {
			const tabBtns = tabsNav.querySelectorAll( '.sp-tabs__tab' );
			tabBtns.forEach( ( btn ) => {
				btn.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					const targetId = btn.getAttribute( 'data-tab' );
					if ( ! targetId ) return;

					// Deactivate other tabs & panels
					tabBtns.forEach( ( b ) => {
						b.classList.remove( 'active' );
						b.setAttribute( 'aria-selected', 'false' );
					} );
					document.querySelectorAll( '.sp-tabs__panel' ).forEach( ( panel ) => {
						panel.classList.remove( 'active' );
					} );

					// Activate selected tab & panel
					btn.classList.add( 'active' );
					btn.setAttribute( 'aria-selected', 'true' );
					const targetPanel = document.getElementById( 'tab-' + targetId );
					if ( targetPanel ) {
						targetPanel.classList.add( 'active' );
					}
				} );
			} );
		}

		/* ---------- Scroll-triggered Header State ---------- */
		const headerEl = document.getElementById( 'header' );
		if ( headerEl ) {
			const handleHeaderScroll = () => {
				const isScrolled = window.scrollY > 10;
				headerEl.classList.toggle( 'shadow-md', isScrolled );
				headerEl.classList.toggle( 'shadow-sm', ! isScrolled );
			};
			window.addEventListener( 'scroll', handleHeaderScroll, { passive: true } );
			handleHeaderScroll();
		}

		/* ---------- ESC closes all panels ---------- */
		document.addEventListener( 'keydown', ( e ) => {
			if ( e.key === 'Escape' ) {
				closeDrawer();
				closeCart();
				if ( typeof closeCatPanel === 'function' ) closeCatPanel();
				if ( typeof closeNewsPanel === 'function' ) closeNewsPanel();
				if ( typeof closeDealerPanel === 'function' ) closeDealerPanel();
				if ( typeof closeContactPanel === 'function' ) closeContactPanel();
			}
		} );
	} );
} )();
