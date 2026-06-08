/**
 * dailyxedien.vn — Homepage specific interactions.
 * Plain vanilla JS, enqueued directly (no build step needed).
 * Handles hero slider, testimonials vertical loop, tech tabs, store filtering, lightbox.
 */

// Define functions in global scope so they are callable by inline HTML onclick handlers
let moveHeroSlide, setHeroSlide, switchTechTab, switchTab, filterCategory;
let scrollProvinces, filterProvince, switchStoreTab;
let scrollTestimonials, pauseTestimonial, resumeTestimonial;
let openVideoModal, closeVideoModal;
let openLightbox, closeLightbox, navigateLightbox;
let handleConsultSubmit, showToast;

( function () {
	'use strict';

	const $  = ( sel, ctx = document ) => ctx.querySelector( sel );
	const $$ = ( sel, ctx = document ) => Array.from( ctx.querySelectorAll( sel ) );

	const onReady = ( fn ) =>
		document.readyState !== 'loading'
			? fn()
			: document.addEventListener( 'DOMContentLoaded', fn );

	onReady( function () {
		const body = document.body;

		// ----------------------------------------------------
		// A. Data hydration from DOM
		// ----------------------------------------------------
		let mockStores = [];
		try {
			const storesEl = $( '#dxd-stores-data' );
			if ( storesEl ) {
				mockStores = JSON.parse( storesEl.textContent );
			}
		} catch ( e ) {
			console.error( 'Error parsing stores data:', e );
		}

		let eventImages = [];
		const hydrateEventImages = () => {
			eventImages = $$( '[data-lightbox-src]' ).map( ( el ) => ( {
				url: el.getAttribute( 'data-lightbox-src' ),
				caption: el.getAttribute( 'data-lightbox-cap' ) || ''
			} ) );
		};
		hydrateEventImages();

		// Lock/Unlock scroll utilities
		let openOverlays = 0;
		function lockScroll() {
			openOverlays++;
			body.classList.add( 'no-scroll' );
		}
		function unlockScroll() {
			openOverlays = Math.max( 0, openOverlays - 1 );
			if ( openOverlays === 0 ) body.classList.remove( 'no-scroll' );
		}

		// ----------------------------------------------------
		// B. HERO SLIDER
		// ----------------------------------------------------
		let currentHeroSlide = 0;
		let heroTimer = null;

		function updateHeroSlider() {
			const slides = $$( '#hero-slider .hero-slide' );
			const dots   = $$( '.hero-dot' );
			if ( ! slides.length ) return;

			slides.forEach( ( slide, i ) => {
				slide.classList.toggle( 'opacity-0', i !== currentHeroSlide );
				slide.classList.toggle( 'opacity-100', i === currentHeroSlide );
				slide.classList.toggle( 'z-10', i === currentHeroSlide );
				slide.classList.toggle( 'z-0', i !== currentHeroSlide );
				slide.classList.toggle( 'pointer-events-none', i !== currentHeroSlide );
				slide.setAttribute( 'aria-hidden', i !== currentHeroSlide ? 'true' : 'false' );
			} );

			dots.forEach( ( dot, i ) => {
				const active = i === currentHeroSlide;
				dot.setAttribute( 'data-active', active ? 'true' : 'false' );
				dot.setAttribute( 'aria-selected', active ? 'true' : 'false' );
			} );
		}

		setHeroSlide = function ( idx ) {
			currentHeroSlide = idx;
			updateHeroSlider();
			restartHeroAutoplay();
		};

		moveHeroSlide = function ( direction ) {
			const total = $$( '#hero-slider .hero-slide' ).length;
			if ( ! total ) return;
			currentHeroSlide = ( currentHeroSlide + direction + total) % total;
			updateHeroSlider();
			restartHeroAutoplay();
		};

		function startHeroAutoplay() {
			if ( $$( '#hero-slider .hero-slide' ).length > 1 ) {
				heroTimer = setInterval( () => moveHeroSlide( 1 ), 6000 );
			}
		}

		function restartHeroAutoplay() {
			clearInterval( heroTimer );
			startHeroAutoplay();
		}

		// Init Hero
		updateHeroSlider();
		startHeroAutoplay();

		// ----------------------------------------------------
		// C. TECHNOLOGY SPOTLIGHT TABS
		// ----------------------------------------------------
		switchTechTab = function ( tabId, btn ) {
			const activeBtnCls = 'bg-gradient-to-r from-primary to-indigo-600 border-primary text-white shadow-lg shadow-primary/20';
			const inactiveBtnCls = 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10 hover:text-white';

			const container = btn.closest( '[role="tablist"]' );
			if ( ! container ) return;

			$$( 'button[role="tab"]', container ).forEach( ( b ) => {
				b.className = b.className.replace( activeBtnCls, '' ).replace( inactiveBtnCls, '' ) + ' ' + inactiveBtnCls;
				b.setAttribute( 'aria-selected', 'false' );
			} );

			btn.className = btn.className.replace( inactiveBtnCls, '' ) + ' ' + activeBtnCls;
			btn.setAttribute( 'aria-selected', 'true' );

			const panels = $$( '#ai-tab-content .ai-tab-panel' );
			panels.forEach( ( p ) => {
				p.classList.add( 'hidden' );
				p.classList.remove( 'flex' );
			} );

			const target = $( '#panel-' + tabId );
			if ( target ) {
				target.classList.remove( 'hidden' );
				target.classList.add( 'flex' );
			}
		};

		// ----------------------------------------------------
		// D. BEST SELLERS PRODUCT TABS
		// ----------------------------------------------------
		switchTab = function ( tabId, clickedBtn ) {
			const container = $( '#tab-container' );
			if ( ! container ) return;

			$$( '.tab-panel', container ).forEach( ( p ) => p.classList.add( 'hidden' ) );
			const target = $( '#' + tabId );
			if ( target ) target.classList.remove( 'hidden' );

			const inactive = 'tab-btn px-4 md:px-6 py-2.5 md:py-3 text-xs font-bold rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 transition-all whitespace-nowrap';
			const active   = 'tab-btn active px-4 md:px-6 py-2.5 md:py-3 text-xs font-black rounded-xl transition-all whitespace-nowrap bg-gradient-to-r from-primary to-primary-hover text-white shadow-md shadow-primary/30';

			const tablist = clickedBtn ? clickedBtn.closest( '[role="tablist"]' ) : $( '#best-sellers [role="tablist"]' );
			if ( ! tablist ) return;

			const btns = $$( '.tab-btn', tablist );
			btns.forEach( ( b ) => {
				b.className = inactive;
				b.setAttribute( 'aria-selected', 'false' );
			} );

			const btn = clickedBtn || btns.find( ( b ) => b.getAttribute( 'data-tab' ) === tabId );
			if ( btn ) {
				btn.className = active;
				btn.setAttribute( 'aria-selected', 'true' );
			}
		};

		filterCategory = function ( tabId ) {
			switchTab( tabId );
			const section = $( '#best-sellers' );
			if ( section ) section.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		};

		// ----------------------------------------------------
		// E. STORE LOCATOR
		// ----------------------------------------------------
		let currentProvince = '';
		let currentStoreTab = 'authorized';

		// Auto set first active province button on load
		const firstProvBtn = $( '#province-scroll .prov-btn' );
		if ( firstProvBtn ) {
			currentProvince = firstProvBtn.textContent.trim();
		}

		scrollProvinces = function ( direction ) {
			const container = $( '#province-scroll' );
			if ( container ) {
				container.scrollBy( { left: direction === 'left' ? -200 : 200, behavior: 'smooth' } );
			}
		};

		filterProvince = function ( provName, btn ) {
			currentProvince = provName;
			$$( '.prov-btn' ).forEach( ( b ) => {
				const isActive = b === btn;
				b.className = isActive
					? 'prov-btn active px-4 py-2 bg-emerald-500 text-white shadow-md hover:bg-emerald-600 text-xs font-bold rounded-full transition-all whitespace-nowrap'
					: 'prov-btn px-4 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-bold rounded-full transition-all whitespace-nowrap';
			} );
			renderStores();
		};

		switchStoreTab = function ( type ) {
			currentStoreTab = type;
			const authBtn = $( '#tab-auth-btn' );
			const regBtn  = $( '#tab-reg-btn' );
			if ( ! authBtn || ! regBtn ) return;

			const on  = 'flex items-center gap-2 text-sm font-bold text-emerald-600 border-b-2 border-emerald-600 pb-2 transition-all cursor-pointer focus:outline-none';
			const off = 'flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-slate-600 transition-all pb-2 cursor-pointer focus:outline-none';
			authBtn.className = type === 'authorized' ? on : off;
			regBtn.className  = type === 'authorized' ? off : on;
			renderStores();
		};

		function renderStores() {
			const container = $( '#store-list-container' );
			if ( ! container ) return;
			container.innerHTML = '';

			const filtered = mockStores.filter(
				( s ) => s.province.toUpperCase().trim() === currentProvince.toUpperCase().trim() && s.type === currentStoreTab
			);

			if ( filtered.length === 0 ) {
				container.innerHTML = `
					<div class="col-span-full py-12 text-center text-slate-400">
						<svg class="w-12 h-12 mx-auto mb-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
						<p class="font-medium">Chưa có đại lý hoặc cửa hàng ủy quyền tại khu vực này.</p>
						<p class="text-xs text-slate-400 mt-1">Hệ thống đang mở rộng dịch vụ, vui lòng liên hệ hotline tư vấn mua xe online.</p>
					</div>`;
				return;
			}

			filtered.forEach( ( store ) => {
				const card = document.createElement( 'div' );
				card.className = 'bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-premium group hover:shadow-hover-card transition-all duration-300 flex flex-col justify-between';
				
				const phone = store.phone || '0933 505 222';
				const phoneUrl = 'tel:' + phone.replace( /[^0-9+]/g, '' );
				const mapUrl = store.map_url || '#';

				card.innerHTML = `
					<div>
						<div class="h-44 bg-gradient-to-r from-primary to-indigo-600 p-5 flex flex-col justify-between relative overflow-hidden text-white">
							<div class="absolute inset-0 bg-black/5"></div>
							<div class="relative z-10 flex justify-between items-start">
								<span class="bg-white/20 text-white font-bold text-[10px] px-2.5 py-1 rounded-full shadow-sm">Bluera Việt Nhật</span>
								<svg class="w-5 h-5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
							</div>
							<div class="relative z-10">
								<h4 class="font-black text-sm tracking-wide text-amber-300 uppercase">Hệ Thống Đại Lý</h4>
								<h3 class="font-black text-base uppercase leading-tight mt-1">BLUERA VIỆT NHẬT</h3>
								<p class="text-[10px] text-indigo-100 tracking-wider mt-0.5">Xe điện chính hãng cho cuộc sống xanh</p>
							</div>
						</div>
						<div class="p-5 space-y-4">
							<div>
								<h3 class="font-black text-slate-800 text-sm leading-snug group-hover:text-primary transition-colors">${store.name}</h3>
								<div class="mt-2.5">
									<span class="bg-emerald-50 text-emerald-600 text-[10px] font-bold px-3 py-1 rounded-full border border-emerald-100 inline-flex items-center gap-1">
										<svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
										${store.type === 'authorized' ? 'Đại Lý Ủy Quyền' : 'Cửa Hàng Ủy Quyền'}
									</span>
								</div>
							</div>
							<div class="flex flex-wrap gap-x-4 gap-y-1.5 text-xs font-semibold py-1 border-y border-slate-50">
								<a href="${phoneUrl}" class="flex items-center gap-1.5 hover:underline text-slate-700">
									<svg class="w-3.5 h-3.5 text-blue-500 fill-current" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
									${phone}
								</a>
							</div>
						</div>
					</div>
					<div class="p-5 pt-0 space-y-3.5">
						<a href="${mapUrl}" target="_blank" rel="noopener" class="w-full bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold text-xs py-3 rounded-xl transition-all shadow-md flex items-center justify-center gap-2">
							<svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg> Chỉ đường
						</a>
						<p class="text-[10px] text-slate-400 flex items-start gap-1.5 leading-relaxed">
							<svg class="w-3.5 h-3.5 text-primary shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
							<span>${store.address}</span>
						</p>
					</div>`;
				container.appendChild( card );
			} );
		}

		renderStores();

		// ----------------------------------------------------
		// F. TESTIMONIALS VERTICAL SCROLLER
		// ----------------------------------------------------
		let testimonialIndex = 0;
		let testimonialTimer = null;

		function getTestimonialOffset( index ) {
			const scroller = $( '#testimonial-scroller' );
			if ( ! scroller ) return 0;
			const items = $$( '#testimonial-scroller > *' );
			let offset = 0;
			for ( let i = 0; i < index && i < items.length; i++ ) {
				offset += items[i].offsetHeight + 16; // Height + gap (space-y-4 = 16px)
			}
			return offset;
		}

		scrollTestimonials = function ( direction ) {
			const scroller = $( '#testimonial-scroller' );
			if ( ! scroller ) return;
			const total = $$( '#testimonial-scroller > *' ).length;
			if ( ! total ) return;

			testimonialIndex = ( testimonialIndex + direction + total ) % total;
			scroller.style.transform = `translateY(-${getTestimonialOffset( testimonialIndex )}px)`;
		};

		function startTestimonialAutoplay() {
			if ( $( '#testimonial-scroller' ) ) {
				testimonialTimer = setInterval( () => scrollTestimonials( 1 ), 4500 );
			}
		}

		startTestimonialAutoplay();

		pauseTestimonial = function () {
			clearInterval( testimonialTimer );
		};

		resumeTestimonial = function () {
			clearInterval( testimonialTimer );
			startTestimonialAutoplay();
		};

		// ----------------------------------------------------
		// G. VIDEO MODAL POPUP
		// ----------------------------------------------------
		openVideoModal = function ( url ) {
			const modal  = $( '#video-modal' );
			const iframe = $( '#video-iframe' );
			if ( ! modal || ! iframe ) return;

			iframe.src = url;
			modal.classList.remove( 'hidden' );
			modal.classList.add( 'flex' );
			lockScroll();
		};

		closeVideoModal = function () {
			const modal  = $( '#video-modal' );
			const iframe = $( '#video-iframe' );
			if ( ! modal || ! iframe ) return;

			iframe.src = '';
			modal.classList.add( 'hidden' );
			modal.classList.remove( 'flex' );
			unlockScroll();
		};

		// ----------------------------------------------------
		// H. EVENT LIGHTBOX GALLERY
		// ----------------------------------------------------
		let currentLightboxIndex = 0;

		function renderLightbox() {
			const img = $( '#lightbox-img' );
			const cap = $( '#lightbox-caption' );
			if ( ! img || ! cap || ! eventImages.length ) return;

			img.src = eventImages[currentLightboxIndex].url;
			cap.textContent = eventImages[currentLightboxIndex].caption;
		}

		openLightbox = function ( index ) {
			currentLightboxIndex = index;
			renderLightbox();
			const modal = $( '#lightbox-modal' );
			if ( ! modal ) return;

			modal.classList.remove( 'hidden' );
			modal.classList.add( 'flex' );
			lockScroll();
		};

		closeLightbox = function () {
			const modal = $( '#lightbox-modal' );
			if ( ! modal ) return;

			modal.classList.add( 'hidden' );
			modal.classList.remove( 'flex' );
			unlockScroll();
		};

		navigateLightbox = function ( direction ) {
			const len = eventImages.length;
			if ( ! len ) return;
			currentLightboxIndex = ( currentLightboxIndex + direction + len ) % len;
			renderLightbox();
		};

		// Keyboard Navigation (ESC to close overlays, left/right for lightbox)
		document.addEventListener( 'keydown', ( e ) => {
			if ( e.key === 'Escape' ) {
				const lightbox = $( '#lightbox-modal' );
				if ( lightbox && ! lightbox.classList.contains( 'hidden' ) ) closeLightbox();

				const video = $( '#video-modal' );
				if ( video && ! video.classList.contains( 'hidden' ) ) closeVideoModal();
			}
			const lightbox = $( '#lightbox-modal' );
			if ( lightbox && ! lightbox.classList.contains( 'hidden' ) && eventImages.length ) {
				if ( e.key === 'ArrowRight' ) navigateLightbox( 1 );
				if ( e.key === 'ArrowLeft' )  navigateLightbox( -1 );
			}
		} );

		// ----------------------------------------------------
		// I. CONSULTATION FORM & TOAST NOTIFICATION
		// ----------------------------------------------------
		let toastTimer = null;

		showToast = function ( message, type = 'success' ) {
			const toast = $( '#toast-notify' );
			const icon  = $( '#toast-icon' );
			const msg   = $( '#toast-msg' );
			if ( ! toast || ! icon || ! msg ) return;

			msg.textContent = message;

			const ok = type === 'success';
			icon.className = ok
				? 'w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xs shrink-0'
				: 'w-5 h-5 rounded-full bg-red-500 flex items-center justify-center text-white text-xs shrink-0';
			icon.innerHTML = ok
				? '<svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>'
				: '<svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

			toast.classList.remove( 'opacity-0', 'translate-y-5', 'pointer-events-none' );
			toast.classList.add( 'opacity-100', 'translate-y-0' );

			clearTimeout( toastTimer );
			toastTimer = setTimeout( () => {
				toast.classList.remove( 'opacity-100', 'translate-y-0' );
				toast.classList.add( 'opacity-0', 'translate-y-5', 'pointer-events-none' );
			}, 3500 );
		};

		handleConsultSubmit = function () {
			const nameInput = $( '#consult-name' );
			const phoneInput = $( '#consult-phone' );
			const interestSelect = $( '#consult-interest' );

			if ( ! nameInput || ! phoneInput || ! interestSelect ) return;

			const name = nameInput.value.trim();
			const phone = phoneInput.value.trim();
			const interest = interestSelect.value;

			// simple VN phone validation
			if ( ! /^0\d{9,10}$/.test( phone ) ) {
				showToast( 'Số điện thoại chưa hợp lệ, vui lòng kiểm tra lại.', 'error' );
				phoneInput.focus();
				return;
			}

			showToast( `Cảm ơn ${name}! Chúng tôi sẽ liên hệ tư vấn dòng xe ${interest} sớm nhất.` );
			nameInput.value = '';
			phoneInput.value = '';
			const messageInput = $( '#consult-message' );
			if ( messageInput ) messageInput.value = '';
		};
	} );
} )();
