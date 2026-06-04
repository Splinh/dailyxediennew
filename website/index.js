/**
 * Thảo Dược Thaphaco — Main JavaScript
 * Light theme with SVG icons
 */

// SVG Icon templates
const ICONS = {
  heart: `<svg class="icon" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`,
  heartFilled: `<svg class="icon" viewBox="0 0 24 24" style="fill:var(--color-accent-red);stroke:var(--color-accent-red)"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`,
  eye: `<svg class="icon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`,
  cart: `<svg class="icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>`,
  check: `<svg class="icon" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>`,
  leaf: `<svg class="icon" viewBox="0 0 24 24"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M14 21c0-3.5-2-7-2-7"/></svg>`,
  cup: `<svg class="icon" viewBox="0 0 24 24"><path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/></svg>`,
  cupcake: `<svg class="icon" viewBox="0 0 24 24"><path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/><line x1="6" y1="17" x2="18" y2="17"/></svg>`,
  droplet: `<svg class="icon" viewBox="0 0 24 24"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>`,
  flower: `<svg class="icon" viewBox="0 0 24 24"><path d="M12 20.94c1.5 0 2.75 1.06 4 1.06 3 0 6-8 6-12.22A4.91 4.91 0 0 0 17 5c-2.22 0-4 1.44-5 2-1-.56-2.78-2-5-2a4.9 4.9 0 0 0-5 4.78C2 14 5 22 8 22c1.25 0 2.5-1.06 4-1.06Z"/><path d="M10 2c1 .5 2 2 2 5"/></svg>`,
  sparkle: `<svg class="icon" viewBox="0 0 24 24"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>`,
};

// Product data
const FLASH_PRODUCTS = [
  { name: 'Trà Atiso Túi Lọc Premium', category: 'Trà Túi Lọc', price: '75.000₫', oldPrice: '100.000₫', badge: '-25%', img: 'images/product-tea.png' },
  { name: 'Bột Nghệ Nguyên Chất Đắk Lắk', category: 'Bột Nguyên Chất', price: '55.000₫', oldPrice: '80.000₫', badge: '-30%', img: 'images/product-powder.png' },
  { name: 'Tinh Dầu Tràm Thiên Nhiên 50ml', category: 'Tinh Dầu', price: '120.000₫', oldPrice: '150.000₫', badge: '-20%', img: 'images/product-oil.png' },
  { name: 'Thảo Dược Khô Hỗn Hợp Bổ Gan', category: 'Thảo Dược Khô', price: '85.000₫', oldPrice: '100.000₫', badge: '-15%', img: 'images/product-herbs.png' },
];

const FEATURED_PRODUCTS = [
  { name: 'Trà Hoa Cúc - Thanh Nhiệt Giải Độc', category: 'Trà Túi Lọc', price: '65.000₫', img: 'images/product-tea.png' },
  { name: 'Tinh Dầu Sả Chanh Nguyên Chất 30ml', category: 'Tinh Dầu', price: '95.000₫', img: 'images/product-oil.png' },
  { name: 'Bột Cỏ Ngọt Stevia Tự Nhiên 100g', category: 'Bột Nguyên Chất', price: '48.000₫', img: 'images/product-powder.png' },
  { name: 'Hoa Cúc La Mã Khô - Trị Mất Ngủ', category: 'Hoa Thảo Dược', price: '110.000₫', img: 'images/product-flower.png' },
  { name: 'Gia Vị Phở Truyền Thống Thaphaco', category: 'Gia Vị', price: '35.000₫', img: 'images/product-spice.png' },
  { name: 'Ngải Cứu Khô - Hỗ Trợ Xương Khớp', category: 'Thảo Dược Khô', price: '42.000₫', img: 'images/product-herbs.png' },
  { name: 'Trà Giảo Cổ Lam - Giảm Mỡ Máu', category: 'Trà Túi Lọc', price: '70.000₫', img: 'images/product-tea.png' },
  { name: 'Tinh Dầu Bạc Hà Nguyên Chất 50ml', category: 'Tinh Dầu', price: '88.000₫', img: 'images/product-oil.png' },
];

const CATEGORIES = [
  { name: 'Thảo Dược Khô', count: '120+', icon: 'leaf' },
  { name: 'Trà Túi Lọc', count: '45+', icon: 'cup' },
  { name: 'Bột Nguyên Chất', count: '60+', icon: 'cupcake' },
  { name: 'Tinh Dầu', count: '30+', icon: 'droplet' },
  { name: 'Hoa Thảo Dược', count: '25+', icon: 'flower' },
  { name: 'Gia Vị', count: '40+', icon: 'sparkle' },
];

// Create a product card
function createProductCard(product) {
  const card = document.createElement('div');
  card.className = 'product-card reveal';
  const link = product.link || 'single-product.html';
  card.innerHTML = `
    <a href="${link}" class="product-card__link" style="text-decoration:none;color:inherit;display:block;">
      <div class="product-card__image">
        <img src="${product.img}" alt="${product.name}" loading="lazy" />
        ${product.badge ? `<span class="product-card__badge">${product.badge}</span>` : ''}
        <div class="product-card__actions">
          <button class="product-card__action-btn wishlist-btn" aria-label="Yêu thích" onclick="event.preventDefault();event.stopPropagation();">${ICONS.heart}</button>
          <button class="product-card__action-btn" aria-label="Xem nhanh" onclick="event.preventDefault();event.stopPropagation();">${ICONS.eye}</button>
        </div>
      </div>
      <div class="product-card__body">
        <span class="product-card__category">${product.category}</span>
        <h3 class="product-card__name">${product.name}</h3>
        <div class="product-card__price">
          <span class="product-card__price-current">${product.price}</span>
          ${product.oldPrice ? `<span class="product-card__price-old">${product.oldPrice}</span>` : ''}
        </div>
      </div>
    </a>
    <button class="product-card__add-to-cart add-cart-btn" aria-label="Thêm ${product.name} vào giỏ hàng">
      ${ICONS.cart} Thêm vào giỏ
    </button>
  `;
  return card;
}

// Create a category card
function createCategoryCard(cat) {
  const card = document.createElement('a');
  card.href = 'archive.html';
  card.className = 'category-card reveal';
  card.style.textDecoration = 'none';
  card.style.color = 'inherit';
  card.innerHTML = `
    <div class="category-card__icon">${ICONS[cat.icon]}</div>
    <div class="category-card__name">${cat.name}</div>
    <div class="category-card__count">${cat.count} sản phẩm</div>
  `;
  return card;
}

// ==================================================

document.addEventListener('DOMContentLoaded', () => {

  // 1. RENDER PRODUCTS
  const flashGrid = document.getElementById('flash-products');
  if (flashGrid) {
    FLASH_PRODUCTS.forEach(p => flashGrid.appendChild(createProductCard(p)));
  }

  const featuredGrid = document.getElementById('featured-products');
  if (featuredGrid) {
    FEATURED_PRODUCTS.forEach(p => featuredGrid.appendChild(createProductCard(p)));
  }

  // 2. RENDER CATEGORIES
  const catGrid = document.getElementById('categories-grid');
  if (catGrid) {
    CATEGORIES.forEach(c => catGrid.appendChild(createCategoryCard(c)));
  }

  // 3. STICKY HEADER
  const header = document.getElementById('header');
  window.addEventListener('scroll', () => {
    header.classList.toggle('scrolled', window.scrollY > 60);
  }, { passive: true });

  // 4. CATEGORY DROPDOWN
  const categoryToggle = document.getElementById('category-toggle');
  const categoryDropdown = document.getElementById('category-dropdown');
  if (categoryToggle && categoryDropdown) {
    categoryToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = categoryDropdown.classList.toggle('active');
      categoryToggle.setAttribute('aria-expanded', isOpen);
    });
    document.addEventListener('click', (e) => {
      if (!categoryDropdown.contains(e.target) && !categoryToggle.contains(e.target)) {
        categoryDropdown.classList.remove('active');
        categoryToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // 5. MOBILE MENU — auto-inject into all pages
  if (!document.getElementById('mobile-nav')) {
    const currentPage = location.pathname.split('/').pop() || 'index.html';
    const menuItems = [
      { href: 'index.html', label: 'Trang Chủ', icon: '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>' },
      { href: 'about.html', label: 'Giới Thiệu', icon: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>' },
      { href: 'archive.html', label: 'Sản Phẩm', icon: '<path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M14 21c0-3.5-2-7-2-7"/>' },
      { href: 'news.html', label: 'Tin Tức', icon: '<path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/>' },
      { href: 'contact.html', label: 'Liên Hệ', icon: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>' },
    ];
    const isActive = (href) => {
      if (href === 'index.html' && (currentPage === '' || currentPage === 'index.html')) return true;
      if (href === 'archive.html' && (currentPage === 'archive.html' || currentPage === 'single-product.html')) return true;
      if (href === 'news.html' && (currentPage === 'news.html' || currentPage === 'single-post.html')) return true;
      return currentPage === href;
    };
    const linksHTML = menuItems.map(item =>
      `<a href="${item.href}"${isActive(item.href) ? ' class="mobile-nav__active"' : ''}><svg class="icon" viewBox="0 0 24 24">${item.icon}</svg>${item.label}</a>`
    ).join('');

    const mobileHTML = `
      <div class="mobile-overlay" id="mobile-overlay"></div>
      <nav class="mobile-nav" id="mobile-nav" aria-label="Mobile navigation">
        <div class="mobile-nav__header">
          <a href="index.html" class="logo" style="text-decoration:none;">
            <div class="logo__icon"><svg class="icon" viewBox="0 0 24 24"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 17 3.5s1 2.5-1 6c-2 3.5-5 5.5-5 5.5"/><path d="M11.7 11.2a5.18 5.18 0 0 1 3.3-2.2c2.5-.4 4-1 4-1s-.3 2.3-2 4c-1.7 1.7-3.3 2.5-3.3 2.5"/><path d="M14 21c0-3.5-2-7-2-7"/></svg></div>
            <div class="logo__text"><span class="logo__name">Thảo Dược Thaphaco</span></div>
          </a>
          <button class="mobile-nav__close" id="mobile-nav-close" aria-label="Đóng menu">
            <svg class="icon" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="mobile-nav__links">${linksHTML}</div>
        <div class="mobile-nav__contact">
          <a href="tel:0901806930"><svg class="icon" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg> 0901 806 930</a>
          <a href="mailto:splworks.info@gmail.com"><svg class="icon" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg> splworks.info@gmail.com</a>
        </div>
      </nav>`;
    document.body.insertAdjacentHTML('beforeend', mobileHTML);
  }

  const mobileMenuBtn = document.getElementById('mobile-menu-btn');
  const mobileNav = document.getElementById('mobile-nav');
  const mobileOverlay = document.getElementById('mobile-overlay');
  const mobileNavClose = document.getElementById('mobile-nav-close');

  const openMobile = () => {
    mobileNav?.classList.add('active');
    mobileOverlay?.classList.add('active');
    mobileMenuBtn?.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  };
  const closeMobile = () => {
    mobileNav?.classList.remove('active');
    mobileOverlay?.classList.remove('active');
    mobileMenuBtn?.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  };

  mobileMenuBtn?.addEventListener('click', openMobile);
  mobileNavClose?.addEventListener('click', closeMobile);
  mobileOverlay?.addEventListener('click', closeMobile);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && mobileNav?.classList.contains('active')) {
      closeMobile();
      mobileMenuBtn?.focus();
    }
  });

  // 6. COUNTDOWN
  const cdHours = document.getElementById('cd-hours');
  const cdMins = document.getElementById('cd-mins');
  const cdSecs = document.getElementById('cd-secs');
  if (cdHours) {
    let total = 5 * 3600 + 30 * 60;
    const tick = () => {
      if (total <= 0) total = 5 * 3600 + 30 * 60;
      const h = Math.floor(total / 3600);
      const m = Math.floor((total % 3600) / 60);
      const s = total % 60;
      cdHours.textContent = String(h).padStart(2, '0');
      cdMins.textContent = String(m).padStart(2, '0');
      cdSecs.textContent = String(s).padStart(2, '0');
      total--;
    };
    tick();
    setInterval(tick, 1000);
  }

  // 7. SCROLL-TO-TOP
  const scrollTopBtn = document.getElementById('scroll-top-btn');
  if (scrollTopBtn) {
    window.addEventListener('scroll', () => {
      scrollTopBtn.classList.toggle('visible', window.scrollY > 400);
    }, { passive: true });
    scrollTopBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // 8. SCROLL REVEAL
  const revealEls = document.querySelectorAll('.reveal');
  if (revealEls.length) {
    const obs = new IntersectionObserver((entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
          obs.unobserve(e.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    revealEls.forEach((el, i) => {
      el.style.transitionDelay = `${Math.min(i * 0.04, 0.4)}s`;
      obs.observe(el);
    });
  }

  // 9. ADD-TO-CART
  const cartBadge = document.getElementById('cart-badge');
  let cartCount = 0;

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.add-cart-btn');
    if (!btn) return;

    cartCount++;
    if (cartBadge) cartBadge.textContent = cartCount;

    // Animate button
    const origHTML = btn.innerHTML;
    btn.innerHTML = `${ICONS.check} Đã thêm!`;
    btn.style.background = 'var(--color-primary)';
    btn.style.color = 'white';
    setTimeout(() => {
      btn.innerHTML = origHTML;
      btn.style.background = '';
      btn.style.color = '';
    }, 1500);

    // Animate cart icon
    const cartBtn = document.getElementById('cart-btn');
    if (cartBtn) {
      cartBtn.style.transform = 'scale(1.2)';
      cartBtn.style.background = 'var(--color-primary)';
      cartBtn.style.color = 'white';
      cartBtn.style.borderColor = 'var(--color-primary)';
      setTimeout(() => {
        cartBtn.style.transform = '';
        cartBtn.style.background = '';
        cartBtn.style.color = '';
        cartBtn.style.borderColor = '';
      }, 400);
    }
  });

  // 10. WISHLIST TOGGLE
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.wishlist-btn');
    if (!btn) return;

    const isLiked = btn.classList.toggle('liked');
    btn.innerHTML = isLiked ? ICONS.heartFilled : ICONS.heart;
  });

  // 11. SMOOTH SCROLL
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener('click', (e) => {
      const id = anchor.getAttribute('href');
      if (id === '#') return;
      const target = document.querySelector(id);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
        if (mobileNav.classList.contains('active')) closeMobile();
      }
    });
  });

  // 12. SEARCH
  const searchInput = document.getElementById('search-input');
  if (searchInput) {
    searchInput.addEventListener('keydown', (e) => {
      if (e.key !== 'Enter') return;
      e.preventDefault();
      const q = searchInput.value.trim().toLowerCase();
      if (!q) return;

      document.querySelectorAll('.product-card__name').forEach((name) => {
        const card = name.closest('.product-card');
        if (name.textContent.toLowerCase().includes(q)) {
          card.style.border = '2px solid var(--color-primary)';
          card.style.boxShadow = '0 0 20px rgba(96,179,1,0.25)';
          card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
          card.style.border = '';
          card.style.boxShadow = '';
        }
      });
    });
  }

});
