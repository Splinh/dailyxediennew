<?php
/**
 * Inline JavaScript — basic interactivity when Vite build is not available.
 *
 * @package SPL
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_footer', 'spl_inline_js', 99 );

/**
 * Output inline JS if Vite assets are not built yet.
 */
function spl_inline_js(): void {
	// Skip if Vite manifest exists.
	if ( is_file( get_template_directory() . '/assets/.vite/manifest.json' ) ) {
		return;
	}
	?>
	<script>
	(function(){
		// Mobile menu toggle
		const btn=document.getElementById('mobile-menu-btn');
		const nav=document.getElementById('mobile-nav');
		const overlay=document.getElementById('mobile-overlay');
		const close=document.getElementById('mobile-nav-close');
		if(btn&&nav){
			const toggle=()=>{
				const open=nav.classList.toggle('active');
				overlay&&overlay.classList.toggle('active',open);
				btn.setAttribute('aria-expanded',open);
				document.body.style.overflow=open?'hidden':'';
			};
			btn.addEventListener('click',toggle);
			close&&close.addEventListener('click',toggle);
			overlay&&overlay.addEventListener('click',toggle);
		}

		// Category dropdown
		const catBtn=document.getElementById('category-toggle');
		const catDd=document.getElementById('category-dropdown');
		if(catBtn&&catDd){
			catBtn.addEventListener('click',()=>{
				const open=catDd.classList.toggle('active');
				catBtn.setAttribute('aria-expanded',open);
			});
			document.addEventListener('click',(e)=>{
				if(!catBtn.contains(e.target)&&!catDd.contains(e.target)){
					catDd.classList.remove('active');
					catBtn.setAttribute('aria-expanded','false');
				}
			});
		}

		// ===== Mini cart off-canvas =====
		const miniCart=document.getElementById('mini-cart-offcanvas');
		const miniOverlay=document.querySelector('.mini-cart-overlay');
		const toggleMiniCart=(open)=>{
			if(!miniCart)return;
			miniCart.classList.toggle('active',open);
			miniCart.setAttribute('aria-hidden',open?'false':'true');
			miniOverlay&&miniOverlay.classList.toggle('active',open);
			document.body.classList.toggle('mini-cart-open',open);
			document.body.style.overflow=open?'hidden':'';
		};
		document.addEventListener('click',(e)=>{
			if(e.target.closest('[data-mini-cart-open]')){
				e.preventDefault();
				toggleMiniCart(true);
				return;
			}
			if(e.target.closest('[data-mini-cart-close]')){
				e.preventDefault();
				toggleMiniCart(false);
				return;
			}
			const qtyBtn=e.target.closest('[data-mini-cart-minus],[data-mini-cart-plus]');
			if(!qtyBtn)return;
			const control=qtyBtn.closest('.mini-cart-qty');
			const input=control&&control.querySelector('input');
			if(!control||!input)return;
			const next=Math.max(0,(parseInt(input.value,10)||0)+(qtyBtn.hasAttribute('data-mini-cart-plus')?1:-1));
			input.value=next;
			const cfg=window.splMiniCart||{};
			const body=new URLSearchParams({action:'spl_update_mini_cart_qty',nonce:cfg.nonce||'',cart_item_key:control.dataset.cartKey||'',quantity:String(next)});
			fetch(cfg.ajaxUrl||'/wp-admin/admin-ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body})
				.then((res)=>res.json())
				.then((data)=>{
					if(!data.fragments)return;
					Object.entries(data.fragments).forEach(([selector,html])=>{
						document.querySelectorAll(selector).forEach((el)=>{el.outerHTML=html;});
					});
				});
		});
		document.addEventListener('keydown',(e)=>{if(e.key==='Escape'){toggleMiniCart(false);}});

		// Sticky header
		const header=document.getElementById('header');
		if(header){
			window.addEventListener('scroll',()=>{
				header.classList.toggle('scrolled',window.scrollY>50);
			},{passive:true});
		}

		// Scroll to top
		const scrollBtn=document.getElementById('scroll-top-btn');
		if(scrollBtn){
			window.addEventListener('scroll',()=>{
				scrollBtn.style.opacity=window.scrollY>300?'1':'0';
				scrollBtn.style.pointerEvents=window.scrollY>300?'auto':'none';
			},{passive:true});
			scrollBtn.addEventListener('click',()=>{
				window.scrollTo({top:0,behavior:'smooth'});
			});
		}

		// Countdown (supports multiple instances)
		document.querySelectorAll('.countdown').forEach((cd)=>{
			const end=cd.dataset.end?new Date(cd.dataset.end).getTime():Date.now()+86400000;
			const h=cd.querySelector('[data-cd="hours"]');
			const m=cd.querySelector('[data-cd="minutes"]');
			const s=cd.querySelector('[data-cd="seconds"]');
			const tick=()=>{
				const diff=Math.max(0,end-Date.now());
				const hh=Math.floor(diff/3600000);
				const mm=Math.floor((diff%3600000)/60000);
				const ss=Math.floor((diff%60000)/1000);
				if(h)h.textContent=String(hh).padStart(2,'0');
				if(m)m.textContent=String(mm).padStart(2,'0');
				if(s)s.textContent=String(ss).padStart(2,'0');
			};
			tick();
			setInterval(tick,1000);
		});

		// Reveal on scroll
		const reveals=document.querySelectorAll('.reveal');
		if(reveals.length&&'IntersectionObserver' in window){
			const io=new IntersectionObserver((entries)=>{
				entries.forEach(e=>{
					if(e.isIntersecting){
						e.target.classList.add('visible');
						io.unobserve(e.target);
					}
				});
			},{threshold:0.1});
			reveals.forEach(el=>io.observe(el));
		}else{
			// Fallback: no IntersectionObserver -> show everything so content is never hidden.
			reveals.forEach(el=>el.classList.add('visible'));
		}

		// ===== Single product: tabs =====
		document.querySelectorAll('.sp-tabs__tab').forEach((tab)=>{
			tab.addEventListener('click',()=>{
				const id=tab.dataset.tab;
				document.querySelectorAll('.sp-tabs__tab').forEach((t)=>{
					const on=t===tab;
					t.classList.toggle('active',on);
					t.setAttribute('aria-selected',on?'true':'false');
				});
				document.querySelectorAll('.sp-tabs__panel').forEach((p)=>{
					p.classList.toggle('active',p.id==='tab-'+id);
				});
			});
		});

		// ===== Single product: gallery thumbnails =====
		const mainImg=document.getElementById('sp-main-img');
		const galleryMain=document.getElementById('sp-gallery-main');
		const galleryThumbs=Array.from(document.querySelectorAll('.sp-gallery__thumb'));
		let galleryIndex=0;
		const setGalleryImage=(index)=>{
			if(!galleryThumbs.length)return;
			galleryIndex=(index+galleryThumbs.length)%galleryThumbs.length;
			const thumb=galleryThumbs[galleryIndex];
			const src=thumb.dataset.img;
			if(mainImg&&src){mainImg.src=src;}
			galleryThumbs.forEach((item)=>item.classList.toggle('active',item===thumb));
		};
		galleryThumbs.forEach((thumb,index)=>{
			thumb.addEventListener('click',()=>{
				setGalleryImage(index);
			});
		});
		document.querySelector('.sp-gallery__nav--prev')?.addEventListener('click',()=>setGalleryImage(galleryIndex-1));
		document.querySelector('.sp-gallery__nav--next')?.addEventListener('click',()=>setGalleryImage(galleryIndex+1));
		if(galleryMain){
			let touchStart=0;
			galleryMain.addEventListener('touchstart',(e)=>{touchStart=e.changedTouches[0].clientX;},{passive:true});
			galleryMain.addEventListener('touchend',(e)=>{
				const distance=e.changedTouches[0].clientX-touchStart;
				if(Math.abs(distance)>45){setGalleryImage(galleryIndex+(distance<0?1:-1));}
			},{passive:true});
		}
		document.getElementById('sp-zoom-btn')?.addEventListener('click',()=>{
			if(!mainImg)return;
			const overlay=document.createElement('div');
			overlay.className='spl-lightbox';
			overlay.innerHTML='<button type="button" class="spl-lightbox__close" aria-label="Close">&times;</button><img src="'+mainImg.src+'" alt="'+mainImg.alt+'">';
			overlay.addEventListener('click',(e)=>{if(e.target===overlay||e.target.tagName==='BUTTON'){overlay.remove();}});
			document.body.appendChild(overlay);
		});

		// ===== Company activity gallery lightbox =====
		(()=>{
			const galleries=document.querySelectorAll('.company-activity .activity-grid');
			if(!galleries.length)return;
			let overlay=null,activeItems=[],activeIndex=0,keyHandler=null,touchX=0;

			const close=()=>{
				if(!overlay)return;
				overlay.remove();
				overlay=null;
				document.body.classList.remove('spl-lightbox-open');
				if(keyHandler)document.removeEventListener('keydown',keyHandler);
				keyHandler=null;
			};

			const render=()=>{
				if(!overlay||!activeItems.length)return;
				activeIndex=(activeIndex+activeItems.length)%activeItems.length;
				const item=activeItems[activeIndex];
				const img=overlay.querySelector('.spl-lightbox__image');
				const caption=overlay.querySelector('.spl-lightbox__caption');
				const counter=overlay.querySelector('.spl-lightbox__counter');
				const prev=overlay.querySelector('.spl-lightbox__nav--prev');
				const next=overlay.querySelector('.spl-lightbox__nav--next');
				img.src=item.src;
				img.alt=item.alt||'';
				caption.textContent=item.caption||item.alt||'';
				counter.textContent=(activeIndex+1)+' / '+activeItems.length;
				const hasMany=activeItems.length>1;
				prev.hidden=!hasMany;
				next.hidden=!hasMany;
			};

			const go=(step)=>{
				activeIndex+=step;
				render();
			};

			const open=(items,index)=>{
				activeItems=items;
				activeIndex=index;
				overlay=document.createElement('div');
				overlay.className='spl-lightbox spl-lightbox--gallery';
				overlay.innerHTML='<div class="spl-lightbox__dialog" role="dialog" aria-modal="true"><button type="button" class="spl-lightbox__close" aria-label="Close">&times;</button><button type="button" class="spl-lightbox__nav spl-lightbox__nav--prev" aria-label="Previous image"><svg class="icon" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg></button><img class="spl-lightbox__image" src="" alt=""><button type="button" class="spl-lightbox__nav spl-lightbox__nav--next" aria-label="Next image"><svg class="icon" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></button><div class="spl-lightbox__meta"><span class="spl-lightbox__caption"></span><span class="spl-lightbox__counter"></span></div></div>';
				overlay.addEventListener('click',(e)=>{if(e.target===overlay||e.target.closest('.spl-lightbox__close'))close();});
				overlay.querySelector('.spl-lightbox__nav--prev').addEventListener('click',(e)=>{e.stopPropagation();go(-1);});
				overlay.querySelector('.spl-lightbox__nav--next').addEventListener('click',(e)=>{e.stopPropagation();go(1);});
				overlay.addEventListener('touchstart',(e)=>{touchX=e.changedTouches[0].clientX;},{passive:true});
				overlay.addEventListener('touchend',(e)=>{
					const dx=e.changedTouches[0].clientX-touchX;
					if(Math.abs(dx)>45)go(dx<0?1:-1);
				},{passive:true});
				keyHandler=(e)=>{
					if(e.key==='Escape')close();
					if(e.key==='ArrowLeft')go(-1);
					if(e.key==='ArrowRight')go(1);
				};
				document.addEventListener('keydown',keyHandler);
				document.body.classList.add('spl-lightbox-open');
				document.body.appendChild(overlay);
				render();
			};

			galleries.forEach((gallery)=>{
				const links=Array.from(gallery.querySelectorAll('a.activity-card'));
				if(!links.length)return;
				const items=links.map((link)=>{
					const img=link.querySelector('img');
					return {
						src:link.href,
						alt:img?.alt||'',
						caption:link.dataset.caption||img?.alt||''
					};
				});
        links.forEach((link,index)=>{
          link.addEventListener('click',(e)=>{
            e.preventDefault();
            e.stopImmediatePropagation();
            open(items,index);
          });
        });
			});
		})();

		// ===== Product quick view =====
		document.querySelectorAll('[data-wc-quickview]').forEach((btn)=>{
			btn.addEventListener('click',async(e)=>{
				e.preventDefault();
				e.stopPropagation();
				if(btn.classList.contains('is-loading'))return;
				btn.classList.add('is-loading');
				try{
					const response=await fetch('/wp-json/spl/v1/wc-quickview/'+btn.dataset.productId,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
					const json=await response.json();
					if(!json.success||!json.data||!json.data.html)return;
					const overlay=document.createElement('div');
					overlay.className='spl-quickview';
					overlay.innerHTML='<div class="spl-quickview__dialog" role="dialog" aria-modal="true"><button type="button" class="spl-quickview__close" aria-label="Close">&times;</button>'+json.data.html+'</div>';
					overlay.addEventListener('click',(event)=>{if(event.target===overlay||event.target.closest('.spl-quickview__close')){overlay.remove();}});
					document.body.appendChild(overlay);
				}catch(error){
					console.error('[SPL QuickView]',error);
				}finally{
					btn.classList.remove('is-loading');
				}
			});
		});

		// ===== Single product: quantity stepper =====
		const qtyInput=document.getElementById('qty-input');
		const qtyMinus=document.getElementById('qty-minus');
		const qtyPlus=document.getElementById('qty-plus');
		if(qtyInput){
			const clamp=(v)=>Math.min(parseInt(qtyInput.max||'99',10),Math.max(parseInt(qtyInput.min||'1',10),v||1));
			qtyMinus&&qtyMinus.addEventListener('click',()=>{qtyInput.value=clamp(parseInt(qtyInput.value,10)-1);});
			qtyPlus&&qtyPlus.addEventListener('click',()=>{qtyInput.value=clamp(parseInt(qtyInput.value,10)+1);});
		}

		// ===== Single product: variation selector =====
		const variationsWrap=document.getElementById('sp-variations');
		if(variationsWrap){
			const variations=JSON.parse(variationsWrap.dataset.variations||'[]');
			const variationIdInput=document.getElementById('sp-variation-id');
			const priceBox=document.getElementById('sp-price-box');
			const resetWrap=document.getElementById('sp-variations-reset');
			const originalPriceHtml=priceBox?priceBox.innerHTML:'';

			// Get current selections.
			const getSelections=()=>{
				const sel={};
				variationsWrap.querySelectorAll('.sp-variations__options').forEach((group)=>{
					const attr=group.dataset.attribute;
					const active=group.querySelector('.sp-variations__btn.active');
					sel[attr]=active?active.dataset.value:'';
				});
				return sel;
			};

			// Find matching variation.
			const findVariation=(selections)=>{
				return variations.find((v)=>{
					return Object.entries(selections).every(([key,val])=>{
						if(!val)return true;
						const vAttr=v.attributes[key];
						return vAttr===''||vAttr===val;
					});
				});
			};

			// Update price and image.
			const updateVariation=()=>{
				const selections=getSelections();
				const allSelected=Object.values(selections).every((v)=>v!=='');
				const matched=allSelected?findVariation(selections):null;

				if(matched&&variationIdInput){
					variationIdInput.value=matched.variation_id;
					if(priceBox){
						const priceHtml=matched.spl_price_html||matched.price_html||originalPriceHtml;
						const oldPriceHtml=matched.spl_old_price_html?'<span class="sp-info__old-price">'+matched.spl_old_price_html+'</span>':'';
						priceBox.innerHTML='<span class="sp-info__price">'+priceHtml+'</span>'+oldPriceHtml;
					}
					// Update main image if variation has one.
					if(matched.image&&matched.image.url){
						const mainImg=document.getElementById('sp-main-img');
						if(mainImg)mainImg.src=matched.image.url;
					}
				}else{
					if(variationIdInput)variationIdInput.value='';
					if(priceBox)priceBox.innerHTML=originalPriceHtml;
				}

				// Show/hide reset.
				const hasSelection=Object.values(selections).some((v)=>v!=='');
				if(resetWrap)resetWrap.style.display=hasSelection?'':'none';
			};

			// Button click handler.
			variationsWrap.querySelectorAll('.sp-variations__btn').forEach((btn)=>{
				btn.addEventListener('click',()=>{
					const group=btn.closest('.sp-variations__options');
					const wasActive=btn.classList.contains('active');
					group.querySelectorAll('.sp-variations__btn').forEach((b)=>b.classList.remove('active'));
					if(!wasActive)btn.classList.add('active');
					updateVariation();
				});
			});

			// Clear button.
			const clearBtn=variationsWrap.querySelector('.sp-variations__clear');
			if(clearBtn){
				clearBtn.addEventListener('click',()=>{
					variationsWrap.querySelectorAll('.sp-variations__btn').forEach((b)=>b.classList.remove('active'));
					updateVariation();
				});
			}

			// Auto-select if only one option.
			updateVariation();
		}

		// ===== Add to cart (single + cards) =====
		const getQty=()=>qtyInput?Math.max(1,parseInt(qtyInput.value,10)||1):1;
		const getVariationId=()=>{
			const el=document.getElementById('sp-variation-id');
			return el?el.value:'';
		};
		const getAttributeParams=()=>{
			const vw=document.getElementById('sp-variations');
			if(!vw)return '';
			let params='';
			vw.querySelectorAll('.sp-variations__options').forEach((group)=>{
				const attr=group.dataset.attribute;
				const active=group.querySelector('.sp-variations__btn.active');
				if(attr&&active)params+='&'+encodeURIComponent(attr)+'='+encodeURIComponent(active.dataset.value);
			});
			return params;
		};
		document.querySelectorAll('.add-cart-btn, #sp-add-cart').forEach((btn)=>{
			btn.addEventListener('click',(e)=>{
				e.preventDefault();
				const id=btn.dataset.productId;
				if(!id)return;
				const qty=btn.id==='sp-add-cart'?getQty():1;
				const type=btn.dataset.productType||'simple';

				if(type==='variable'){
					const vid=getVariationId();
					if(!vid){
						btn.style.animation='ring 0.5s';
						setTimeout(()=>btn.style.animation='',600);
						return;
					}
					window.location.href=location.origin+location.pathname+'?add-to-cart='+id+'&variation_id='+vid+'&quantity='+qty+getAttributeParams();
				}else{
					window.location.href=location.origin+location.pathname+'?add-to-cart='+id+'&quantity='+qty;
				}
			});
		});
		const buyNow=document.getElementById('sp-buy-now');
		if(buyNow){
			buyNow.addEventListener('click',(e)=>{
				e.preventDefault();
				const id=buyNow.dataset.productId;
				const checkout=buyNow.dataset.checkout||location.origin;
				const type=buyNow.dataset.productType||'simple';
				if(!id)return;
				let url=checkout+(checkout.indexOf('?')>-1?'&':'?')+'add-to-cart='+id+'&quantity='+getQty();
				if(type==='variable'){
					const vid=getVariationId();
					if(!vid){
						buyNow.style.animation='ring 0.5s';
						setTimeout(()=>buyNow.style.animation='',600);
						return;
					}
					url+='&variation_id='+vid+getAttributeParams();
				}
				window.location.href=url;
			});
		}

		// ===== Archive: grid/list view toggle =====
		const grid=document.getElementById('archive-products');
		document.querySelectorAll('.archive-view-btn').forEach((vb)=>{
			vb.addEventListener('click',()=>{
				document.querySelectorAll('.archive-view-btn').forEach((b)=>b.classList.toggle('active',b===vb));
				if(grid){grid.classList.toggle('products-grid--list',vb.dataset.view==='list');}
			});
		});
		const filterToggle=document.getElementById('archive-filter-toggle');
		const filterSidebar=document.getElementById('archive-sidebar');
		filterToggle&&filterSidebar&&filterToggle.addEventListener('click',()=>{
			const open=filterSidebar.classList.toggle('active');
			filterToggle.setAttribute('aria-expanded',open?'true':'false');
		});

		// ===== Single post: build TOC from h2 =====
		const postBody=document.querySelector('.post-body');
		if(postBody&&!postBody.querySelector('.post-toc')){
			const heads=postBody.querySelectorAll('h2');
			if(heads.length>=3){
				const toc=document.createElement('div');
				toc.className='post-toc';
				let items='';
				heads.forEach((h,i)=>{
					if(!h.id){h.id='muc-'+(i+1);}
					items+='<li><a href="#'+h.id+'">'+h.textContent+'</a></li>';
				});
				toc.innerHTML='<h3><svg class="icon" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg> Mục lục bài viết</h3><ol>'+items+'</ol>';
				postBody.insertBefore(toc,heads[0]);
				toc.querySelectorAll('a').forEach((a)=>{
					a.addEventListener('click',(e)=>{
						const t=document.getElementById(a.getAttribute('href').slice(1));
						if(t){e.preventDefault();t.scrollIntoView({behavior:'smooth',block:'start'});}
					});
				});
			}
		}

		// ===== Copy link (single post) =====
		const copyBtn=document.getElementById('copy-link-btn');
		if(copyBtn){
			copyBtn.addEventListener('click',()=>{
				const url=copyBtn.dataset.url||location.href;
				if(navigator.clipboard){navigator.clipboard.writeText(url);}
				copyBtn.classList.add('copied');
				setTimeout(()=>copyBtn.classList.remove('copied'),1500);
			});
		}

		// ===== Header live product search =====
		(function(){
			const cfg=window.splSearch;
			const wraps=document.querySelectorAll('[data-search]');
			if(!cfg||!wraps.length)return;
			const esc=(s)=>String(s==null?'':s).replace(/[&<>"']/g,(c)=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
			const render=(box,items,isSeed)=>{
				if(!items.length){
					box.innerHTML='<div class="search-results__empty">'+(isSeed?'Chưa có sản phẩm.':'Không tìm thấy sản phẩm phù hợp.')+'</div>';
					box.hidden=false;return;
				}
				const head=isSeed?'<div class="search-results__head">Sản phẩm mới nhất</div>':'';
				box.innerHTML=head+'<ul class="search-results__list">'+items.map((it)=>(
					'<li><a class="search-results__item" href="'+esc(it.url)+'">'+
						'<span class="search-results__thumb"><img src="'+esc(it.image)+'" alt="'+esc(it.title)+'" loading="lazy"></span>'+
						'<span class="search-results__info"><span class="search-results__name">'+esc(it.title)+'</span>'+
						'<span class="search-results__price">'+(it.price||'')+'</span></span>'+
					'</a></li>'
				)).join('')+'</ul>';
				box.hidden=false;
			};
			wraps.forEach((wrap)=>{
				const input=wrap.querySelector('[data-search-input]');
				const box=wrap.querySelector('[data-search-results]');
				if(!input||!box)return;
				let timer=null,ctrl=null,seeded=false;
				const fetchResults=(term)=>{
					if(ctrl)ctrl.abort();
					ctrl=('AbortController' in window)?new AbortController():null;
					box.innerHTML='<div class="search-results__loading">Đang tìm...</div>';box.hidden=false;
					const url=(cfg.ajaxUrl||'/wp-admin/admin-ajax.php')+'?action=spl_search_products&nonce='+encodeURIComponent(cfg.nonce||'')+'&term='+encodeURIComponent(term);
					fetch(url,{headers:{'Accept':'application/json'},signal:ctrl?ctrl.signal:undefined})
						.then((r)=>r.json())
						.then((res)=>{
							if(!res||!res.success){box.hidden=true;return;}
							render(box,res.data.items||[],!!res.data.is_seed);
							if(res.data.is_seed)seeded=true;
						})
						.catch((e)=>{if(e.name!=='AbortError'){box.hidden=true;}});
				};
				input.addEventListener('input',()=>{
					const term=input.value.trim();
					clearTimeout(timer);
					timer=setTimeout(()=>fetchResults(term),250);
				});
				input.addEventListener('focus',()=>{
					if(input.value.trim()===''&&!seeded){fetchResults('');}
					else if(box.innerHTML.trim()!==''){box.hidden=false;}
				});
				document.addEventListener('click',(e)=>{if(!wrap.contains(e.target)){box.hidden=true;}});
				input.addEventListener('keydown',(e)=>{if(e.key==='Escape'){box.hidden=true;input.blur();}});
			});
		})();

		// ===== Related products slider =====
		document.querySelectorAll('.sp-related-slider').forEach((slider)=>{
			const track=slider.querySelector('.sp-related-slider__track');
			const prevBtn=slider.querySelector('.sp-related-slider__nav--prev');
			const nextBtn=slider.querySelector('.sp-related-slider__nav--next');
			if(!track)return;
			const slides=track.querySelectorAll('.sp-related-slider__slide');
			if(!slides.length)return;
			let current=0;

			const getVisibleCount=()=>{
				const sliderW=slider.offsetWidth;
				const slideW=slides[0].offsetWidth;
				return Math.round(sliderW/slideW)||1;
			};

			const maxIndex=()=>Math.max(0,slides.length-getVisibleCount());

			const goTo=(index)=>{
				current=Math.max(0,Math.min(index,maxIndex()));
				const slideW=slides[0].offsetWidth;
				const gap=parseInt(getComputedStyle(track).gap)||0;
				track.style.transform='translateX(-'+(current*(slideW+gap))+'px)';
			};

			prevBtn&&prevBtn.addEventListener('click',()=>goTo(current-1));
			nextBtn&&nextBtn.addEventListener('click',()=>goTo(current+1));

			// Touch swipe.
			let touchX=0;
			track.addEventListener('touchstart',(e)=>{touchX=e.changedTouches[0].clientX;},{passive:true});
			track.addEventListener('touchend',(e)=>{
				const dx=e.changedTouches[0].clientX-touchX;
				if(Math.abs(dx)>40)goTo(current+(dx<0?1:-1));
			},{passive:true});

			// Reset on resize.
			window.addEventListener('resize',()=>goTo(current),{passive:true});
		});

		// ===== Mobile search button =====
		const mobileSearchBtn=document.getElementById('mobile-search-btn');
		if(mobileSearchBtn){
			mobileSearchBtn.addEventListener('click',()=>{
				const bar=document.getElementById('mobile-search-bar');
				if(!bar)return;
				const isOpen=bar.classList.toggle('active');
				mobileSearchBtn.classList.toggle('active',isOpen);
				if(isOpen){
					const input=bar.querySelector('[data-search-input]');
					if(input)setTimeout(()=>input.focus(),200);
				}
			});
			document.addEventListener('keydown',(e)=>{
				if(e.key==='Escape'){
					const bar=document.getElementById('mobile-search-bar');
					if(bar&&bar.classList.contains('active')){
						bar.classList.remove('active');
						mobileSearchBtn.classList.remove('active');
					}
				}
			});
		}
	})();
	</script>
	<?php
}
