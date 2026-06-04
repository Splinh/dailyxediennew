/**
 * Thảo Dược Thaphaco — Pages JavaScript
 * Handles single product, single post, and archive pages
 */

document.addEventListener('DOMContentLoaded', () => {

  // ============================================
  // SINGLE PRODUCT PAGE
  // ============================================

  // 1. Gallery thumbnail switching
  const galleryThumbs = document.getElementById('sp-gallery-thumbs');
  const mainImg = document.getElementById('sp-main-img');
  if (galleryThumbs && mainImg) {
    galleryThumbs.addEventListener('click', (e) => {
      const thumb = e.target.closest('.sp-gallery__thumb');
      if (!thumb) return;
      
      // Update active thumb
      galleryThumbs.querySelectorAll('.sp-gallery__thumb').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
      
      // Swap image with fade
      mainImg.style.opacity = '0';
      setTimeout(() => {
        mainImg.src = thumb.dataset.img;
        mainImg.style.opacity = '1';
      }, 200);
    });
  }

  // 2. Zoom button (simple lightbox)
  const zoomBtn = document.getElementById('sp-zoom-btn');
  if (zoomBtn && mainImg) {
    zoomBtn.addEventListener('click', () => {
      const overlay = document.createElement('div');
      overlay.style.cssText = `
        position: fixed; inset: 0; z-index: 9999;
        background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        animation: fadeInUp 0.3s ease;
        cursor: zoom-out;
      `;
      const img = document.createElement('img');
      img.src = mainImg.src;
      img.alt = mainImg.alt;
      img.style.cssText = 'max-width: 90vw; max-height: 90vh; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.5);';
      overlay.appendChild(img);
      document.body.appendChild(overlay);
      document.body.style.overflow = 'hidden';
      
      overlay.addEventListener('click', () => {
        overlay.remove();
        document.body.style.overflow = '';
      });
    });
  }

  // 3. Quantity controls
  const qtyMinus = document.getElementById('qty-minus');
  const qtyPlus = document.getElementById('qty-plus');
  const qtyInput = document.getElementById('qty-input');
  if (qtyMinus && qtyPlus && qtyInput) {
    qtyMinus.addEventListener('click', () => {
      const val = parseInt(qtyInput.value) || 1;
      if (val > 1) qtyInput.value = val - 1;
    });
    qtyPlus.addEventListener('click', () => {
      const val = parseInt(qtyInput.value) || 1;
      if (val < 99) qtyInput.value = val + 1;
    });
    qtyInput.addEventListener('change', () => {
      let val = parseInt(qtyInput.value) || 1;
      val = Math.max(1, Math.min(99, val));
      qtyInput.value = val;
    });
  }

  // 4. Add to cart (single product page)
  const spAddCart = document.getElementById('sp-add-cart');
  const spBuyNow = document.getElementById('sp-buy-now');
  const cartBadge = document.getElementById('cart-badge');
  
  if (spAddCart) {
    spAddCart.addEventListener('click', () => {
      const qty = parseInt(qtyInput?.value) || 1;
      let count = parseInt(cartBadge?.textContent) || 0;
      count += qty;
      if (cartBadge) cartBadge.textContent = count;
      
      // Button animation
      const origHTML = spAddCart.innerHTML;
      spAddCart.innerHTML = `<svg class="icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Đã thêm ${qty} sản phẩm!`;
      spAddCart.style.background = 'var(--color-primary)';
      spAddCart.style.borderColor = 'var(--color-primary)';
      setTimeout(() => {
        spAddCart.innerHTML = origHTML;
        spAddCart.style.background = '';
        spAddCart.style.borderColor = '';
      }, 2000);

      // Cart badge pulse
      if (cartBadge) {
        cartBadge.style.transform = 'scale(1.4)';
        setTimeout(() => cartBadge.style.transform = '', 300);
      }
    });
  }

  if (spBuyNow) {
    spBuyNow.addEventListener('click', () => {
      const origHTML = spBuyNow.innerHTML;
      spBuyNow.innerHTML = `<svg class="icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Đang xử lý...`;
      setTimeout(() => { spBuyNow.innerHTML = origHTML; }, 2000);
    });
  }

  // 5. Wishlist toggle
  const spWishlist = document.getElementById('sp-wishlist');
  if (spWishlist) {
    spWishlist.addEventListener('click', () => {
      const liked = spWishlist.classList.toggle('liked');
      spWishlist.innerHTML = liked
        ? `<svg class="icon" viewBox="0 0 24 24" style="fill:var(--color-accent-red);stroke:var(--color-accent-red)"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`
        : `<svg class="icon" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`;
    });
  }

  // 6. Tabs
  const tabBtns = document.querySelectorAll('.sp-tabs__tab');
  const tabPanels = document.querySelectorAll('.sp-tabs__panel');
  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const tab = btn.dataset.tab;
      tabBtns.forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected', 'false'); });
      tabPanels.forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      btn.setAttribute('aria-selected', 'true');
      document.getElementById(`tab-${tab}`)?.classList.add('active');
    });
  });

  // 7. Related products
  const relatedGrid = document.getElementById('related-products');
  if (relatedGrid) {
    const RELATED = [
      { name: 'Ngải Cứu Khô 100g', category: 'Thảo Dược Khô', price: '42.000₫', img: 'images/product-herbs.png' },
      { name: 'Đỗ Trọng Sấy Khô 100g', category: 'Thảo Dược Khô', price: '55.000₫', img: 'images/product-powder.png' },
      { name: 'Ngưu Tất Khô 100g', category: 'Thảo Dược Khô', price: '48.000₫', img: 'images/product-herbs.png' },
      { name: 'Trà Giảo Cổ Lam Túi Lọc', category: 'Trà Túi Lọc', price: '70.000₫', img: 'images/product-tea.png' },
    ];
    RELATED.forEach(p => {
      if (typeof createProductCard === 'function') {
        relatedGrid.appendChild(createProductCard(p));
      }
    });
  }

  // ============================================
  // ARCHIVE PAGE
  // ============================================

  const archiveGrid = document.getElementById('archive-products');
  if (archiveGrid) {
    const ARCHIVE_PRODUCTS = [
      { name: 'Thiên Niên Kiện Sấy Khô 100g', category: 'Thảo Dược Khô', price: '36.000₫', oldPrice: '40.000₫', badge: '-10%', img: 'images/product-thien-nien-kien.png' },
      { name: 'Ngải Cứu Khô - Hỗ Trợ Xương Khớp', category: 'Thảo Dược Khô', price: '42.000₫', img: 'images/product-herbs.png' },
      { name: 'Đỗ Trọng Sấy Khô - Bổ Thận', category: 'Thảo Dược Khô', price: '55.000₫', oldPrice: '70.000₫', badge: '-21%', img: 'images/product-powder.png' },
      { name: 'Bạch Hoa Xà Thiệt Thảo 100g', category: 'Thảo Dược Khô', price: '38.000₫', img: 'images/product-herbs.png' },
      { name: 'Cỏ Xước Khô - Thông Kinh Lạc', category: 'Thảo Dược Khô', price: '32.000₫', img: 'images/product-powder.png' },
      { name: 'Hà Thủ Ô Đỏ Sấy Khô 100g', category: 'Thảo Dược Khô', price: '65.000₫', oldPrice: '80.000₫', badge: '-19%', img: 'images/product-herbs.png' },
      { name: 'Hoàng Kỳ Khô - Bổ Khí Huyết', category: 'Thảo Dược Khô', price: '78.000₫', img: 'images/product-powder.png' },
      { name: 'Đương Quy Khô - Bổ Máu', category: 'Thảo Dược Khô', price: '85.000₫', oldPrice: '100.000₫', badge: '-15%', img: 'images/product-herbs.png' },
      { name: 'Kim Tiền Thảo - Lợi Tiểu', category: 'Thảo Dược Khô', price: '35.000₫', img: 'images/product-tea.png' },
      { name: 'Diệp Hạ Châu - Bổ Gan', category: 'Thảo Dược Khô', price: '40.000₫', img: 'images/product-herbs.png' },
      { name: 'Xạ Đen Khô - Hỗ Trợ U Bướu', category: 'Thảo Dược Khô', price: '55.000₫', oldPrice: '65.000₫', badge: '-15%', img: 'images/product-powder.png' },
      { name: 'Cà Gai Leo Khô - Giải Độc Gan', category: 'Thảo Dược Khô', price: '45.000₫', img: 'images/product-herbs.png' },
    ];
    ARCHIVE_PRODUCTS.forEach(p => {
      if (typeof createProductCard === 'function') {
        archiveGrid.appendChild(createProductCard(p));
      }
    });
  }

  // View toggle (grid/list) 
  const viewBtns = document.querySelectorAll('.archive-view-btn');
  viewBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      viewBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      if (archiveGrid) {
        if (btn.dataset.view === 'list') {
          archiveGrid.style.gridTemplateColumns = '1fr';
        } else {
          archiveGrid.style.gridTemplateColumns = '';
        }
      }
    });
  });

  // Pagination (demo)
  document.querySelectorAll('.pagination__page').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.pagination__page').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  });

  // ============================================
  // SINGLE POST PAGE
  // ============================================

  // Copy link button
  const copyBtn = document.getElementById('copy-link-btn');
  if (copyBtn) {
    copyBtn.addEventListener('click', () => {
      navigator.clipboard.writeText(window.location.href).then(() => {
        const origHTML = copyBtn.innerHTML;
        copyBtn.innerHTML = `<svg class="icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>`;
        copyBtn.style.background = 'var(--color-primary)';
        setTimeout(() => {
          copyBtn.innerHTML = origHTML;
          copyBtn.style.background = '';
        }, 2000);
      });
    });
  }

  // ============================================
  // SHARED: Sticky header, scroll-to-top, reveal
  // (These are already handled by index.js, but
  //  we re-trigger reveal for dynamically added elements)
  // ============================================
  
  // Re-observe new reveals (from dynamically added products)
  setTimeout(() => {
    const newReveals = document.querySelectorAll('.reveal:not(.visible)');
    if (newReveals.length) {
      const obs = new IntersectionObserver((entries) => {
        entries.forEach((e) => {
          if (e.isIntersecting) {
            e.target.classList.add('visible');
            obs.unobserve(e.target);
          }
        });
      }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
      newReveals.forEach((el, i) => {
        el.style.transitionDelay = `${Math.min(i * 0.04, 0.4)}s`;
        obs.observe(el);
      });
    }
  }, 100);

});
