/**
 * dailyxedien.vn — global UI interactions (header/footer).
 * Plain vanilla JS, enqueued directly (no build step needed).
 * Selectors: data-drawer*, data-cat-trigger, data-scroll-top, #back-to-top.
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

		/* ---------- ESC closes drawer ---------- */
		document.addEventListener( 'keydown', ( e ) => {
			if ( e.key === 'Escape' ) closeDrawer();
		} );
	} );
} )();
