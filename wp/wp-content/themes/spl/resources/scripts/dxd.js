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
			btn.addEventListener( 'click', () =>
				openDrawer( btn.hasAttribute( 'data-focus-search' ) )
			)
		);
		document
			.querySelectorAll( '[data-drawer-close]' )
			.forEach( ( btn ) => btn.addEventListener( 'click', closeDrawer ) );
		if ( overlay ) overlay.addEventListener( 'click', closeDrawer );
		// close drawer when a link inside is clicked
		if ( drawer )
			drawer.addEventListener( 'click', ( e ) => {
				if ( e.target.closest( 'a' ) ) closeDrawer();
			} );

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

		/* ---------- Category Slide-Up Panel ---------- */
		const catPanel = document.getElementById( 'category-panel' );
		const catPanelOverlay = document.getElementById( 'category-panel-overlay' );

		const openCatPanel = () => {
			if ( ! catPanel || ! catPanelOverlay ) return;
			catPanelOverlay.style.display = 'block';
			catPanel.style.display = 'block';
			body.classList.add( 'no-scroll' );
			requestAnimationFrame( () => {
				requestAnimationFrame( () => {
					catPanelOverlay.classList.add( 'open' );
					catPanel.classList.add( 'open' );
				} );
			} );
		};

		const closeCatPanel = () => {
			if ( ! catPanel || ! catPanelOverlay ) return;
			catPanelOverlay.classList.remove( 'open' );
			catPanel.classList.remove( 'open' );
			body.classList.remove( 'no-scroll' );
			setTimeout( () => {
				catPanelOverlay.style.display = 'none';
				catPanel.style.display = 'none';
			}, 300 );
		};

		document.querySelectorAll( '[data-cat-panel-open]' ).forEach( ( btn ) =>
			btn.addEventListener( 'click', openCatPanel )
		);

		document.querySelectorAll( '[data-cat-panel-close]' ).forEach( ( btn ) =>
			btn.addEventListener( 'click', closeCatPanel )
		);

		/* ---------- Single Product: Quantity Stepper ---------- */
		const qtyInput = document.getElementById( 'qty-input' );
		const qtyMinus = document.getElementById( 'qty-minus' );
		const qtyPlus = document.getElementById( 'qty-plus' );
		if ( qtyInput ) {
			const clamp = ( v ) => Math.min( parseInt( qtyInput.max || '99', 10 ), Math.max( parseInt( qtyInput.min || '1', 10 ), v || 1 ) );
			if ( qtyMinus ) qtyMinus.addEventListener( 'click', () => { qtyInput.value = clamp( parseInt( qtyInput.value, 10 ) - 1 ); } );
			if ( qtyPlus ) qtyPlus.addEventListener( 'click', () => { qtyInput.value = clamp( parseInt( qtyInput.value, 10 ) + 1 ); } );
		}

		/* ---------- Single Product: Variation Selector ---------- */
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

		/* ---------- Add to Cart (AJAX) ---------- */
		const getQty = () => qtyInput ? Math.max( 1, parseInt( qtyInput.value, 10 ) || 1 ) : 1;
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
					btn.innerHTML = origHtml;
					btn.disabled = false;
					btn.style.opacity = '';
					openCart();
				} )
				.catch( () => {
					window.location.href = location.origin + location.pathname + '?add-to-cart=' + ( variationId || productId ) + '&quantity=' + qty;
				} );
		};

		document.querySelectorAll( '.add-cart-btn, #sp-add-cart' ).forEach( ( btn ) => {
			btn.addEventListener( 'click', ( e ) => {
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

		/* ---------- ESC closes all panels ---------- */
		document.addEventListener( 'keydown', ( e ) => {
			if ( e.key === 'Escape' ) {
				closeDrawer();
				closeCart();
				closeCatPanel();
			}
		} );
	} );
} )();
