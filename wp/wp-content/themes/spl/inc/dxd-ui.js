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
