/* ==========================================================================
   dailyxedien.vn — Main script
   Vanilla JS, không phụ thuộc thư viện ngoài. Tổ chức theo từng khối tính năng.
   ========================================================================== */
'use strict';

/* --------------------------------------------------------------------------
   Tiện ích chung
   -------------------------------------------------------------------------- */
const $  = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

const formatVND = (n) => n.toLocaleString('vi-VN') + ' đ';

// Đếm số overlay đang mở để khoá/mở scroll body chính xác
let openOverlays = 0;
function lockScroll() {
    openOverlays++;
    document.body.classList.add('no-scroll');
}
function unlockScroll() {
    openOverlays = Math.max(0, openOverlays - 1);
    if (openOverlays === 0) document.body.classList.remove('no-scroll');
}

/* --------------------------------------------------------------------------
   Dữ liệu mẫu — Hệ thống cửa hàng
   -------------------------------------------------------------------------- */
const mockStores = [
    { province: 'AN GIANG', type: 'authorized', name: 'Đại lý Bluera Việt Nhật An Duy', tag: 'Đại lý Uỷ Quyền', phones: ['0838149149', '0773373739'], address: 'Ngã 3 chợ cũ, Thôn 7, Xã Lộc An, Huyện Bảo Lâm, Tỉnh Lâm Đồng' },
    { province: 'AN GIANG', type: 'authorized', name: 'Đại lý Bluera Việt Nhật Bảo Thắng', tag: 'Đại lý Uỷ Quyền', phones: ['0918224868'], address: 'A254/5 Bàu Bàng, Chánh Nghĩa, TP. Thủ Dầu Một, Bình Dương' },
    { province: 'AN GIANG', type: 'authorized', name: 'Đại lý Bluera Việt Nhật Chung Chín', tag: 'Đại lý Uỷ Quyền', phones: ['0975000151', '0916657799'], address: 'C11/1 Quốc Lộ 1A, Khu phố 3, Thị Trấn Tân Túc, Bình Chánh, Tp. HCM' },
    { province: 'BÀ RỊA - VŨNG TÀU', type: 'authorized', name: 'Đại lý Bluera Việt Nhật Vũng Tàu Xanh', tag: 'Đại lý Uỷ Quyền', phones: ['0933505222'], address: '150 Ba Cu, Phường 3, Thành phố Vũng Tàu, Bà Rịa - Vũng Tàu' },
    { province: 'BÌNH DƯƠNG', type: 'authorized', name: 'Cửa hàng Bluera Thuận An', tag: 'Đại lý Uỷ Quyền', phones: ['0909123456'], address: '45 Đại Lộ Bình Dương, Thuận Giao, Thuận An, Bình Dương' },
    { province: 'BÌNH PHƯỚC', type: 'authorized', name: 'Đại lý Đồng Xoài Motor', tag: 'Đại lý Uỷ Quyền', phones: ['0911223344'], address: '888 Phú Riềng Đỏ, Tân Xuân, Đồng Xoài, Bình Phước' },
    { province: 'TP. HỒ CHÍ MINH', type: 'authorized', name: 'Showroom Bluera Việt Nhật Thủ Đức', tag: 'Đại lý Uỷ Quyền', phones: ['0933505222'], address: '466 Nguyễn Duy Trinh, P. Bình Trưng Đông, TP. Thủ Đức, TP.HCM' },
    { province: 'TP. HỒ CHÍ MINH', type: 'authorized', name: 'Showroom Bluera Việt Nhật Gò Vấp', tag: 'Đại lý Uỷ Quyền', phones: ['0938123456'], address: '539 Quang Trung, P. 10, Q. Gò Vấp, TP.HCM' },
    { province: 'TP. HỒ CHÍ MINH', type: 'authorized', name: 'Showroom Bluera Việt Nhật Bình Tân', tag: 'Đại lý Uỷ Quyền', phones: ['0901224567'], address: '621 Tên Lửa, P. Bình Trị Đông B, Q. Bình Tân, TP.HCM' }
];

const eventImages = [
    { url: 'https://dailyxedien.vn/wp-content/uploads/2026/03/top-5-ly-do-nen-chuyen-sang-xe-dien-trong-nam-2026.jpg', caption: 'Sự kiện khai trương hệ thống đại lý mới tại TP.HCM' },
    { url: 'https://dailyxedien.vn/wp-content/uploads/2026/03/gia-xang-dau-2026-xu-huong-chuyen-dich-sang-xe-dien-2.jpg', caption: 'Lễ trao giải tri ân khách hàng thân thiết hàng năm' },
    { url: 'https://dailyxedien.vn/wp-content/uploads/2026/02/khai-truong-dai-ly-xe-dien-bluera-viet-nhat-ron-bike-pro-tai-can-tho-dlxd.jpg', caption: 'Chương trình lái thử xe điện Bluera thế hệ mới' },
    { url: 'https://dailyxedien.vn/wp-content/uploads/2026/01/chi-tiet-lo-trinh-han-che-xe-xang-dau-vao-trung-tam-tphcm.jpg', caption: 'Ngày hội bảo dưỡng xe điện miễn phí cho khách hàng' },
    { url: 'https://dailyxedien.vn/wp-content/uploads/2026/01/dap-xe-dien-don-xuan-quay-qua-cuc-da-mua-xe-dien-bluera-viet-nhat-dlxd.jpg', caption: 'Ngày hội công nghệ xanh - Giao lưu cùng chuyên gia xe điện' },
    { url: 'https://dailyxedien.vn/wp-content/uploads/2026/01/cung-nguoi-thuong-tren-chiec-xe-dap-dien-2-cho-cuc-em.jpg', caption: 'Giới thiệu các tính năng kết nối trên xe điện' }
];

/* --------------------------------------------------------------------------
   Trạng thái toàn cục
   -------------------------------------------------------------------------- */
let currentProvince   = 'AN GIANG';
let currentStoreTab   = 'authorized';
let cart              = [];
let currentLightboxIndex = 0;
let currentHeroSlide  = 0;
let heroTimer         = null;
let testimonialIndex  = 0;
let testimonialTimer  = null;

/* ==========================================================================
   HERO SLIDER (đếm slide động, không hardcode)
   ========================================================================== */
function updateHeroSlider() {
    const slides = $$('#hero-slider .hero-slide');
    const dots   = $$('.hero-dot');
    slides.forEach((slide, i) => {
        slide.classList.toggle('opacity-0', i !== currentHeroSlide);
        slide.classList.toggle('opacity-100', i === currentHeroSlide);
        slide.classList.toggle('z-10', i === currentHeroSlide);
        slide.classList.toggle('z-0', i !== currentHeroSlide);
        slide.classList.toggle('pointer-events-none', i !== currentHeroSlide);
        slide.setAttribute('aria-hidden', i !== currentHeroSlide ? 'true' : 'false');
    });
    dots.forEach((dot, i) => {
        const active = i === currentHeroSlide;
        dot.className = active
            ? 'hero-dot w-6 h-2 rounded-full bg-white cursor-pointer transition-all'
            : 'hero-dot w-2 h-2 rounded-full bg-white/50 hover:bg-white cursor-pointer transition-all';
        dot.setAttribute('aria-current', active);
    });
}

function setHeroSlide(idx) {
    currentHeroSlide = idx;
    updateHeroSlider();
    restartHeroAutoplay();
}

function moveHeroSlide(direction) {
    const total = $$('#hero-slider .hero-slide').length;
    currentHeroSlide = (currentHeroSlide + direction + total) % total;
    updateHeroSlider();
    restartHeroAutoplay();
}

function startHeroAutoplay() {
    heroTimer = setInterval(() => moveHeroSlide(1), 6000);
}
function restartHeroAutoplay() {
    clearInterval(heroTimer);
    startHeroAutoplay();
}

/* ==========================================================================
   Technology spotlight — Tabs tính năng
   ========================================================================== */
function initAiTabs() {
    const btns   = $$('.ai-tab-btn');
    const panels = $$('.ai-tab-panel');

    btns.forEach((btn) => {
        btn.addEventListener('click', () => {
            btns.forEach((b) => {
                b.classList.remove('active', 'bg-white/10', 'border-accent-500');
                b.classList.add('bg-white/5', 'border-transparent');
                b.setAttribute('aria-selected', 'false');
            });
            panels.forEach((p) => p.classList.add('hidden'));

            btn.classList.add('active', 'bg-white/10', 'border-accent-500');
            btn.classList.remove('bg-white/5', 'border-transparent');
            btn.setAttribute('aria-selected', 'true');

            const target = $('#panel-' + btn.dataset.tab);
            if (target) target.classList.remove('hidden');
        });
    });
}

/* ==========================================================================
   MOBILE DRAWER + ACCORDION
   ========================================================================== */
function openMobileMenu() {
    const drawer  = $('#mobile-drawer');
    const overlay = $('#mobile-drawer-overlay');
    overlay.classList.remove('hidden');
    lockScroll();
    requestAnimationFrame(() => {
        overlay.classList.add('opacity-100');
        drawer.classList.remove('-translate-x-full');
    });
}

function closeMobileMenu() {
    const drawer  = $('#mobile-drawer');
    const overlay = $('#mobile-drawer-overlay');
    drawer.classList.add('-translate-x-full');
    overlay.classList.remove('opacity-100');
    unlockScroll();
    setTimeout(() => overlay.classList.add('hidden'), 300);
}

function toggleMobileAccordion(id) {
    const acc  = $('#' + id);
    const icon = $('#icon-' + id);
    const hidden = acc.classList.toggle('hidden');
    if (icon) icon.classList.toggle('rotate-180', !hidden);
}

/* ==========================================================================
   PRODUCT TABS (sửa bug event.currentTarget)
   ========================================================================== */
function switchTab(tabId, clickedBtn) {
    $$('.tab-panel').forEach((p) => p.classList.add('hidden'));
    const target = $('#' + tabId);
    if (target) target.classList.remove('hidden');

    const inactive = 'tab-btn px-4 md:px-6 py-2.5 md:py-3 text-xs font-bold rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 transition-all whitespace-nowrap';
    const active   = 'tab-btn active px-4 md:px-6 py-2.5 md:py-3 text-xs font-black rounded-xl transition-all whitespace-nowrap bg-gradient-to-r from-primary-500 to-primary-600 text-white shadow-md shadow-primary-500/30';

    const btns = $$('.tab-btn');
    btns.forEach((b) => { b.className = inactive; b.setAttribute('aria-selected', 'false'); });

    // Nút được click trực tiếp, hoặc tìm theo data-tab (khi gọi từ menu)
    const btn = clickedBtn || btns.find((b) => b.dataset.tab === tabId);
    if (btn) { btn.className = active; btn.setAttribute('aria-selected', 'true'); }
}

// Gọi từ mega menu / mobile menu — cuộn tới khu sản phẩm rồi đổi tab
function filterCategory(tabId) {
    switchTab(tabId);
    const section = $('#best-sellers');
    if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/* ==========================================================================
   STORE LOCATOR
   ========================================================================== */
function scrollProvinces(direction) {
    const container = $('#province-scroll');
    container.scrollBy({ left: direction === 'left' ? -200 : 200, behavior: 'smooth' });
}

function filterProvince(provName, btn) {
    currentProvince = provName;
    $$('.prov-btn').forEach((b) => {
        const isActive = b === btn;
        b.classList.toggle('bg-emerald-500', isActive);
        b.classList.toggle('text-white', isActive);
        b.classList.toggle('shadow-md', isActive);
        b.classList.toggle('bg-slate-100', !isActive);
        b.classList.toggle('text-slate-700', !isActive);
    });
    renderStores();
}

function switchStoreTab(type) {
    currentStoreTab = type;
    const authBtn = $('#tab-auth-btn');
    const regBtn  = $('#tab-reg-btn');
    const on  = 'flex items-center gap-2 text-sm font-bold text-emerald-600 border-b-2 border-emerald-600 pb-2 transition-all';
    const off = 'flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-slate-600 transition-all pb-2';
    authBtn.className = type === 'authorized' ? on : off;
    regBtn.className  = type === 'authorized' ? off : on;
    renderStores();
}

function renderStores() {
    const container = $('#store-list-container');
    if (!container) return;
    container.innerHTML = '';

    const filtered = mockStores.filter(
        (s) => s.province === currentProvince && s.type === currentStoreTab
    );

    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="col-span-full py-12 text-center text-slate-400">
                <i class="fa-solid fa-map-location-dot text-4xl mb-3 text-slate-300"></i>
                <p class="font-medium">Chưa có đại lý hoặc cửa hàng ủy quyền tại khu vực này.</p>
                <p class="text-xs text-slate-400 mt-1">Hệ thống đang mở rộng dịch vụ, vui lòng liên hệ tư vấn hỗ trợ mua xe online.</p>
            </div>`;
        return;
    }

    filtered.forEach((store) => {
        const phonesHtml = store.phones.map((phone, idx) => {
            const icon  = idx === 0 ? 'fa-solid fa-phone' : 'fa-solid fa-mobile-screen-button';
            const color = idx === 0 ? 'text-blue-500' : 'text-amber-500';
            return `<a href="tel:${phone}" class="flex items-center gap-1.5 hover:underline text-slate-700"><i class="${icon} ${color}"></i> ${phone}</a>`;
        }).join('');

        const mapQuery = encodeURIComponent(store.address);
        const card = document.createElement('div');
        card.className = 'bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-premium group hover:shadow-hover-card transition-all duration-300 flex flex-col justify-between animate-fade-in';
        card.innerHTML = `
            <div>
                <div class="h-44 bg-gradient-to-r from-blue-500 to-blue-600 p-5 flex flex-col justify-between relative overflow-hidden text-white">
                    <div class="absolute inset-0 bg-black/5"></div>
                    <div class="relative z-10 flex justify-between items-start">
                        <span class="bg-white/20 text-white font-bold text-[10px] px-2.5 py-1 rounded-full shadow-sm">Bluera Việt Nhật</span>
                        <i class="fa-solid fa-circle-check text-emerald-300 text-lg" title="Đã xác thực"></i>
                    </div>
                    <div class="relative z-10">
                        <h4 class="font-black text-sm tracking-wide text-amber-300">HỆ THỐNG ĐẠI LÝ CỬA HÀNG</h4>
                        <h3 class="font-black text-base uppercase leading-tight mt-1">BLUERA VIỆT NHẬT</h3>
                        <p class="text-[10px] text-blue-100 tracking-wider mt-0.5">Xe điện chính hãng cho cuộc sống xanh</p>
                    </div>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <h3 class="font-black text-slate-800 text-sm leading-snug group-hover:text-primary-500 transition-colors">${store.name}</h3>
                        <div class="mt-2.5">
                            <span class="bg-emerald-50 text-emerald-600 text-[10px] font-bold px-3 py-1 rounded-full border border-emerald-100">
                                <i class="fa-regular fa-star mr-1"></i> ${store.tag}
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-x-4 gap-y-1.5 text-xs font-semibold py-1 border-y border-slate-50">
                        ${phonesHtml}
                    </div>
                </div>
            </div>
            <div class="p-5 pt-0 space-y-3.5">
                <a href="https://www.google.com/maps/search/?api=1&query=${mapQuery}" target="_blank" rel="noopener" class="w-full bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold text-xs py-3 rounded-xl transition-all shadow-md flex items-center justify-center gap-2">
                    <i class="fa-solid fa-location-arrow"></i> Chỉ đường
                </a>
                <p class="text-[10px] text-slate-400 flex items-start gap-1.5 leading-relaxed">
                    <i class="fa-solid fa-map-pin text-primary-500 shrink-0 mt-0.5"></i>
                    <span>${store.address}</span>
                </p>
            </div>`;
        container.appendChild(card);
    });
}

/* ==========================================================================
   TESTIMONIALS — trượt dọc theo chiều cao thật từng item
   ========================================================================== */
function getTestimonialOffset(index) {
    const items = $$('#testimonial-scroller > *');
    let offset = 0;
    for (let i = 0; i < index && i < items.length; i++) {
        // chiều cao item + gap 16px (space-y-4)
        offset += items[i].offsetHeight + 16;
    }
    return offset;
}

function scrollTestimonials(direction) {
    const total = $$('#testimonial-scroller > *').length;
    if (!total) return;
    testimonialIndex = (testimonialIndex + direction + total) % total;
    const scroller = $('#testimonial-scroller');
    scroller.style.transform = `translateY(-${getTestimonialOffset(testimonialIndex)}px)`;
}

function startTestimonialAutoplay() {
    testimonialTimer = setInterval(() => scrollTestimonials(1), 4000);
}
function pauseTestimonial() {
    clearInterval(testimonialTimer);
}
function resumeTestimonial() {
    startTestimonialAutoplay();
}

/* ==========================================================================
   GIỎ HÀNG
   ========================================================================== */
function updateCartCount() {
    const count = cart.length;
    $('#cart-count').textContent = count;
    $('#cart-count-mobile').textContent = count;
    // Ẩn badge khi rỗng cho gọn
    $$('#cart-count, #cart-count-mobile').forEach((el) => {
        el.classList.toggle('hidden', count === 0);
    });
}

function addToCart(name, price) {
    cart.push({ name, price });
    updateCartCount();
    showToast(`Đã thêm "${name}" vào giỏ hàng!`);
}

// Vẽ lại danh sách trong giỏ (không đụng tới trạng thái mở/đóng)
function renderCart() {
    const itemsContainer = $('#cart-items');
    itemsContainer.innerHTML = '';

    if (cart.length === 0) {
        itemsContainer.innerHTML = `
            <div class="text-center py-8">
                <i class="fa-solid fa-cart-shopping text-3xl text-slate-200 mb-3"></i>
                <p class="text-xs text-slate-400">Chưa có sản phẩm nào trong giỏ hàng</p>
            </div>`;
        $('#cart-total').textContent = '0 đ';
        return;
    }

    let total = 0;
    cart.forEach((item, index) => {
        total += item.price;
        const div = document.createElement('div');
        div.className = 'flex justify-between items-center bg-slate-50 p-2.5 rounded-xl border border-slate-100 text-xs';
        div.innerHTML = `
            <div>
                <span class="font-bold text-slate-800 block">${item.name}</span>
                <span class="text-slate-400 font-semibold">${formatVND(item.price)}</span>
            </div>
            <button onclick="removeFromCart(${index})" class="text-red-500 hover:text-red-700 font-semibold px-2 py-1" aria-label="Xóa ${item.name}">Xóa</button>`;
        itemsContainer.appendChild(div);
    });
    $('#cart-total').textContent = formatVND(total);
}

function openCart() {
    const modal = $('#cart-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    lockScroll();
    renderCart();
}

function closeCart() {
    const modal = $('#cart-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    unlockScroll();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartCount();
    renderCart(); // giỏ đang mở sẵn — chỉ cần vẽ lại
}

function checkout() {
    if (cart.length === 0) {
        showToast('Giỏ hàng đang trống!');
        return;
    }
    showToast('Đang kết nối cổng thanh toán an toàn...');
    setTimeout(() => {
        cart = [];
        updateCartCount();
        closeCart();
        showToast('Đặt hàng thành công! DailyXeDien sẽ liên hệ lại ngay.');
    }, 1000);
}

/* ==========================================================================
   VIDEO MODAL
   ========================================================================== */
function openVideoModal(url) {
    const modal  = $('#video-modal');
    const iframe = $('#video-iframe');
    iframe.src = url;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    lockScroll();
}

function closeVideoModal() {
    const modal  = $('#video-modal');
    const iframe = $('#video-iframe');
    iframe.src = '';
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    unlockScroll();
}

/* ==========================================================================
   LIGHTBOX SỰ KIỆN
   ========================================================================== */
function renderLightbox() {
    $('#lightbox-img').src = eventImages[currentLightboxIndex].url;
    $('#lightbox-caption').textContent = eventImages[currentLightboxIndex].caption;
}

function openLightbox(index) {
    currentLightboxIndex = index;
    renderLightbox();
    const modal = $('#lightbox-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    lockScroll();
}

function closeLightbox() {
    const modal = $('#lightbox-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    unlockScroll();
}

function navigateLightbox(direction) {
    const len = eventImages.length;
    currentLightboxIndex = direction === 'next'
        ? (currentLightboxIndex + 1) % len
        : (currentLightboxIndex - 1 + len) % len;
    renderLightbox();
}

/* ==========================================================================
   FORM TƯ VẤN
   ========================================================================== */
function submitConsultForm(e) {
    e.preventDefault();
    const name     = $('#consult-name').value.trim();
    const phone    = $('#consult-phone').value.trim();
    const interest = $('#consult-interest').value;

    // Validate SĐT Việt Nam đơn giản
    if (!/^0\d{9,10}$/.test(phone)) {
        showToast('Số điện thoại chưa hợp lệ, vui lòng kiểm tra lại.', 'error');
        $('#consult-phone').focus();
        return;
    }

    showToast(`Cảm ơn ${name}! Chúng tôi sẽ liên hệ tư vấn dòng xe ${interest} sớm nhất.`);
    e.target.reset();
}

/* ==========================================================================
   TOAST
   ========================================================================== */
let toastTimer = null;
function showToast(message, type = 'success') {
    const toast = $('#toast-notify');
    const icon  = $('#toast-icon');
    $('#toast-msg').textContent = message;

    const ok = type === 'success';
    icon.className = ok
        ? 'w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xs'
        : 'w-5 h-5 rounded-full bg-red-500 flex items-center justify-center text-white text-xs';
    icon.innerHTML = ok ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-xmark"></i>';

    toast.classList.remove('opacity-0', 'translate-y-5', 'pointer-events-none');
    toast.classList.add('opacity-100', 'translate-y-0');

    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.classList.remove('opacity-100', 'translate-y-0');
        toast.classList.add('opacity-0', 'translate-y-5', 'pointer-events-none');
    }, 3500);
}

/* ==========================================================================
   BACK TO TOP
   ========================================================================== */
function initBackToTop() {
    const btn = $('#back-to-top');
    if (!btn) return;
    window.addEventListener('scroll', () => {
        btn.classList.toggle('show', window.scrollY > 600);
    }, { passive: true });
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

/* ==========================================================================
   PHÍM TẮT — Esc đóng overlay, mũi tên điều hướng lightbox
   ========================================================================== */
function initKeyboard() {
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (!$('#lightbox-modal').classList.contains('hidden')) closeLightbox();
            if (!$('#video-modal').classList.contains('hidden'))   closeVideoModal();
            if (!$('#cart-modal').classList.contains('hidden'))    closeCart();
            if (!$('#mobile-drawer').classList.contains('-translate-x-full')) closeMobileMenu();
            
            const newsPanel = $('#news-panel');
            if (newsPanel && newsPanel.classList.contains('open')) closeNewsPanel();
            
            const categoryPanel = $('#category-panel');
            if (categoryPanel && categoryPanel.classList.contains('open')) closeCategoryPanel();
        }
        // Điều hướng lightbox bằng phím mũi tên khi đang mở
        if (!$('#lightbox-modal').classList.contains('hidden')) {
            if (e.key === 'ArrowRight') navigateLightbox('next');
            if (e.key === 'ArrowLeft')  navigateLightbox('prev');
        }
    });
}

/* ==========================================================================
   MOBILE BOTTOM NAVIGATION & NEWS PANEL
   ========================================================================== */
function initMobileBottomNav() {
    const bottomNav = $('#mobile-bottom-nav');
    if (!bottomNav) return;

    const links = Array.from(bottomNav.querySelectorAll('a'));
    const catLink = links.find(a => a.textContent.includes('Danh mục'));
    if (catLink) {
        // Ensure Category link opens the category panel on mobile
        catLink.setAttribute('href', '#');
        catLink.setAttribute('onclick', 'openCategoryPanel(); return false;');

        // Check if "Tin tức" link already exists
        const newsLinkExists = links.some(a => a.textContent.includes('Tin tức'));
        if (!newsLinkExists) {
            const newsLink = document.createElement('a');
            newsLink.setAttribute('href', '#');
            newsLink.setAttribute('onclick', 'openNewsPanel(); return false;');
            newsLink.innerHTML = '<i class="fa-solid fa-newspaper"></i><span>Tin tức</span>';
            
            // Insert right next to Category link
            catLink.parentNode.insertBefore(newsLink, catLink.nextSibling);
        }
    }

    // Rewrite category panel dynamically if present
    const categoryPanel = $('#category-panel');
    if (categoryPanel && !categoryPanel.querySelector('.cat-content-layout')) {
        categoryPanel.innerHTML = `
        <div class="cat-header">
            <h3>Danh mục sản phẩm</h3>
            <button onclick="closeCategoryPanel()" aria-label="Đóng"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="cat-content-layout">
            <!-- Cột trái: Danh mục cha -->
            <div class="cat-sidebar-left">
                <button class="cat-tab-item active" onclick="switchCategoryTab(event, 'xedien')">Xe điện</button>
                <button class="cat-tab-item" onclick="switchCategoryTab(event, 'xemaydien')">Xe máy điện</button>
                <button class="cat-tab-item" onclick="switchCategoryTab(event, 'xe50cc')">Xe 50cc</button>
                <button class="cat-tab-item" onclick="switchCategoryTab(event, 'xedapdien')">Xe đạp điện</button>
                <button class="cat-tab-item" onclick="switchCategoryTab(event, 'xe3banh')">Xe 3 bánh</button>
                <button class="cat-tab-item" onclick="switchCategoryTab(event, 'phukien')">Phụ kiện</button>
            </div>
            
            <!-- Cột phải: Danh mục con & sản phẩm nổi bật -->
            <div class="cat-products-right">
                <!-- Tab: Xe điện -->
                <div id="cat-tab-panel-xedien" class="cat-tab-panel active">
                    <div class="cat-subcats-section">
                        <h4>Danh mục con</h4>
                        <div class="cat-subcats-grid">
                            <a href="san-pham.html" class="cat-subcat-card">Xe đạp điện</a>
                            <a href="san-pham.html" class="cat-subcat-card">Xe máy điện</a>
                            <a href="san-pham.html" class="cat-subcat-card">Xe điện 3 bánh</a>
                            <a href="san-pham.html" class="cat-subcat-card">Xe máy 50cc</a>
                        </div>
                    </div>
                    <div class="cat-popular-section">
                        <h4>Sản phẩm nổi bật</h4>
                        <div class="cat-popular-list">
                            <a href="chi-tiet-san-pham.html" class="cat-product-row">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/03/top-5-ly-do-nen-chuyen-sang-xe-dien-trong-nam-2026.jpg" alt="Xe điện">
                                <div class="cat-product-info">
                                    <h5>Xe máy điện Vespa Roma Lite thế hệ mới</h5>
                                    <span class="cat-product-price">18.200.000 đ</span>
                                </div>
                            </a>
                            <a href="chi-tiet-san-pham.html" class="cat-product-row">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/02/khai-truong-dai-ly-xe-dien-bluera-viet-nhat-ron-bike-pro-tai-can-tho-dlxd.jpg" alt="Xe điện">
                                <div class="cat-product-info">
                                    <h5>Xe đạp điện Bluera Cap X Pro chính hãng</h5>
                                    <span class="cat-product-price">12.500.000 đ</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Xe máy điện -->
                <div id="cat-tab-panel-xemaydien" class="cat-tab-panel">
                    <div class="cat-subcats-section">
                        <h4>Danh mục con</h4>
                        <div class="cat-subcats-grid">
                            <a href="san-pham.html" class="cat-subcat-card">Vespa điện</a>
                            <a href="san-pham.html" class="cat-subcat-card">Gogo điện</a>
                            <a href="san-pham.html" class="cat-subcat-card">Xmen điện</a>
                            <a href="san-pham.html" class="cat-subcat-card">Jeek điện</a>
                        </div>
                    </div>
                    <div class="cat-popular-section">
                        <h4>Sản phẩm nổi bật</h4>
                        <div class="cat-popular-list">
                            <a href="chi-tiet-san-pham.html" class="cat-product-row">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/02/khai-truong-dai-ly-xe-dien-bluera-viet-nhat-ron-bike-pro-tai-can-tho-dlxd.jpg" alt="Xe điện">
                                <div class="cat-product-info">
                                    <h5>Xe máy điện Bluera Gogo S4 thông minh</h5>
                                    <span class="cat-product-price">16.900.000 đ</span>
                                </div>
                            </a>
                            <a href="chi-tiet-san-pham.html" class="cat-product-row">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/03/top-5-ly-do-nen-chuyen-sang-xe-dien-trong-nam-2026.jpg" alt="Xe điện">
                                <div class="cat-product-info">
                                    <h5>Xe máy điện Xmen Neo thể thao phong cách</h5>
                                    <span class="cat-product-price">15.500.000 đ</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tab: Xe 50cc -->
                <div id="cat-tab-panel-xe50cc" class="cat-tab-panel">
                    <div class="cat-subcats-section">
                        <h4>Danh mục con</h4>
                        <div class="cat-subcats-grid">
                            <a href="san-pham.html" class="cat-subcat-card">Xe ga 50cc</a>
                            <a href="san-pham.html" class="cat-subcat-card">Xe số 50cc</a>
                            <a href="san-pham.html" class="cat-subcat-card">Xe Cub 50cc</a>
                            <a href="san-pham.html" class="cat-subcat-card">Wave 50cc</a>
                        </div>
                    </div>
                    <div class="cat-popular-section">
                        <h4>Sản phẩm nổi bật</h4>
                        <div class="cat-popular-list">
                            <a href="chi-tiet-san-pham.html" class="cat-product-row">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/03/gia-xang-dau-2026-xu-huong-chuyen-dich-sang-xe-dien-2.jpg" alt="Xe điện">
                                <div class="cat-product-info">
                                    <h5>Xe ga 50cc Giorno Smile nhập khẩu</h5>
                                    <span class="cat-product-price">19.000.000 đ</span>
                                </div>
                            </a>
                            <a href="chi-tiet-san-pham.html" class="cat-product-row">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/01/chi-tiet-lo-trinh-han-che-xe-xang-dau-vao-trung-tam-tphcm.jpg" alt="Xe điện">
                                <div class="cat-product-info">
                                    <h5>Xe máy Cub 50cc Classic hoài cổ</h5>
                                    <span class="cat-product-price">14.800.000 đ</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tab: Xe đạp điện -->
                <div id="cat-tab-panel-xedapdien" class="cat-tab-panel">
                    <div class="cat-subcats-section">
                        <h4>Danh mục con</h4>
                        <div class="cat-subcats-grid">
                            <a href="san-pham.html" class="cat-subcat-card">Xe đạp điện mini</a>
                            <a href="san-pham.html" class="cat-subcat-card">Xe đạp điện gấp</a>
                            <a href="san-pham.html" class="cat-subcat-card">Xe đạp điện 2 yên</a>
                        </div>
                    </div>
                    <div class="cat-popular-section">
                        <h4>Sản phẩm nổi bật</h4>
                        <div class="cat-popular-list">
                            <a href="chi-tiet-san-pham.html" class="cat-product-row">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/02/khai-truong-dai-ly-xe-dien-bluera-viet-nhat-ron-bike-pro-tai-can-tho-dlxd.jpg" alt="Xe điện">
                                <div class="cat-product-info">
                                    <h5>Xe đạp điện Bluera Cap X 2026</h5>
                                    <span class="cat-product-price">11.500.000 đ</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tab: Xe 3 bánh -->
                <div id="cat-tab-panel-xe3banh" class="cat-tab-panel">
                    <div class="cat-subcats-section">
                        <h4>Danh mục con</h4>
                        <div class="cat-subcats-grid">
                            <a href="san-pham.html" class="cat-subcat-card">Xe 3 bánh chở hàng</a>
                            <a href="san-pham.html" class="cat-subcat-card">Xe 3 bánh chở khách</a>
                            <a href="san-pham.html" class="cat-subcat-card">Xe 3 bánh mini</a>
                        </div>
                    </div>
                    <div class="cat-popular-section">
                        <h4>Sản phẩm nổi bật</h4>
                        <div class="cat-popular-list">
                            <a href="chi-tiet-san-pham.html" class="cat-product-row">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/01/dap-xe-dien-don-xuan-quay-qua-cuc-da-mua-xe-dien-bluera-viet-nhat-dlxd.jpg" alt="Xe điện">
                                <div class="cat-product-info">
                                    <h5>Xe máy điện 3 bánh Super nhập khẩu</h5>
                                    <span class="cat-product-price">19.500.000 đ</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tab: Phụ kiện -->
                <div id="cat-tab-panel-phukien" class="cat-tab-panel">
                    <div class="cat-subcats-section">
                        <h4>Danh mục con</h4>
                        <div class="cat-subcats-grid">
                            <a href="san-pham.html" class="cat-subcat-card">Ắc quy & Sạc</a>
                            <a href="san-pham.html" class="cat-subcat-card">Lốp & Săm</a>
                            <a href="san-pham.html" class="cat-subcat-card">Mũ bảo hiểm</a>
                            <a href="san-pham.html" class="cat-subcat-card">Động cơ điện</a>
                        </div>
                    </div>
                    <div class="cat-popular-section">
                        <h4>Sản phẩm nổi bật</h4>
                        <div class="cat-popular-list">
                            <a href="chi-tiet-san-pham.html" class="cat-product-row">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/01/cung-nguoi-thuong-tren-chiec-xe-dap-dien-2-cho-cuc-em.jpg" alt="Xe điện">
                                <div class="cat-product-info">
                                    <h5>Sạc xe máy điện thông minh 60V-20Ah</h5>
                                    <span class="cat-product-price">350.000 đ</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        `;
    }

    // Inject news panel if not already present
    if (!document.getElementById('news-panel')) {
        const newsPanelHTML = `
        <!-- MOBILE NEWS SLIDE-UP PANEL -->
        <div id="news-panel-overlay" onclick="closeNewsPanel()"></div>
        <div id="news-panel">
            <div class="news-header">
                <h3>Tin tức & Tư vấn</h3>
                <button onclick="closeNewsPanel()" aria-label="Đóng"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <div class="news-content-layout">
                <!-- Cột trái: Danh mục (Tabs dọc) -->
                <div class="news-sidebar-left">
                    <button class="news-tab-item active" onclick="switchNewsTab(event, 'all')">Tất cả</button>
                    <button class="news-tab-item" onclick="switchNewsTab(event, 'xedien')">Tin Xe Điện</button>
                    <button class="news-tab-item" onclick="switchNewsTab(event, 'khuyenmai')">Khuyến Mãi</button>
                    <button class="news-tab-item" onclick="switchNewsTab(event, 'kinhnghiem')">Kinh Nghiệm</button>
                    <button class="news-tab-item" onclick="switchNewsTab(event, 'thitruong')">Thị Trường</button>
                </div>
                
                <!-- Cột phải: Danh sách bài viết -->
                <div class="news-articles-right">
                    <!-- Tab: Tất cả -->
                    <div id="news-tab-panel-all" class="news-tab-panel active">
                        <div class="news-list-vertical">
                            <a href="bai-viet.html" class="news-row-item">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/03/top-5-ly-do-nen-chuyen-sang-xe-dien-trong-nam-2026.jpg" alt="Bài viết">
                                <div class="news-row-info">
                                    <h5>Hướng dẫn sạc pin xe điện an toàn và tăng tuổi thọ pin</h5>
                                    <span>14 Tháng 7, 2026</span>
                                </div>
                            </a>
                            <a href="bai-viet.html" class="news-row-item">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/03/gia-xang-dau-2026-xu-huong-chuyen-dich-sang-xe-dien-2.jpg" alt="Bài viết">
                                <div class="news-row-info">
                                    <h5>Top 5 xe máy điện bán chạy nhất nửa đầu năm 2026</h5>
                                    <span>10 Tháng 7, 2026</span>
                                </div>
                            </a>
                            <a href="bai-viet.html" class="news-row-item">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/02/khai-truong-dai-ly-xe-dien-bluera-viet-nhat-ron-bike-pro-tai-can-tho-dlxd.jpg" alt="Bài viết">
                                <div class="news-row-info">
                                    <h5>Chính sách bảo hành và bảo dưỡng định kỳ xe điện tại Đại Lý</h5>
                                    <span>05 Tháng 7, 2026</span>
                                </div>
                            </a>
                            <a href="tin-tuc.html" class="news-see-more-link">Xem thêm tin tức <i class="fa-solid fa-chevron-right"></i></a>
                        </div>
                    </div>
                    
                    <!-- Tab: Tin Xe Điện -->
                    <div id="news-tab-panel-xedien" class="news-tab-panel">
                        <div class="news-list-vertical">
                            <a href="bai-viet.html" class="news-row-item">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/03/top-5-ly-do-nen-chuyen-sang-xe-dien-trong-nam-2026.jpg" alt="Bài viết">
                                <div class="news-row-info">
                                    <h5>Hướng dẫn sạc pin xe điện an toàn và tăng tuổi thọ pin</h5>
                                    <span>14 Tháng 7, 2026</span>
                                </div>
                            </a>
                            <a href="bai-viet.html" class="news-row-item">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/03/gia-xang-dau-2026-xu-huong-chuyen-dich-sang-xe-dien-2.jpg" alt="Bài viết">
                                <div class="news-row-info">
                                    <h5>Công nghệ pin LFP mới cho xe máy điện Bluera Việt Nhật</h5>
                                    <span>01 Tháng 7, 2026</span>
                                </div>
                            </a>
                            <a href="tin-tuc.html" class="news-see-more-link">Xem tất cả Tin Xe Điện <i class="fa-solid fa-chevron-right"></i></a>
                        </div>
                    </div>

                    <!-- Tab: Khuyến Mãi -->
                    <div id="news-tab-panel-khuyenmai" class="news-tab-panel">
                        <div class="news-list-vertical">
                            <a href="bai-viet.html" class="news-row-item">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/01/dap-xe-dien-don-xuan-quay-qua-cuc-da-mua-xe-dien-bluera-viet-nhat-dlxd.jpg" alt="Bài viết">
                                <div class="news-row-info">
                                    <h5>Chương trình khuyến mãi hè 2026: Mua xe điện, nhận quà khủng</h5>
                                    <span>28 Tháng 6, 2026</span>
                                </div>
                            </a>
                            <a href="bai-viet.html" class="news-row-item">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/01/cung-nguoi-thuong-tren-chiec-xe-dap-dien-2-cho-cuc-em.jpg" alt="Bài viết">
                                <div class="news-row-info">
                                    <h5>Giảm giá lên đến 20% phụ kiện xe điện trong tháng 7</h5>
                                    <span>15 Tháng 6, 2026</span>
                                </div>
                            </a>
                            <a href="tin-tuc.html" class="news-see-more-link">Xem tất cả Khuyến Mãi <i class="fa-solid fa-chevron-right"></i></a>
                        </div>
                    </div>

                    <!-- Tab: Kinh Nghiệm -->
                    <div id="news-tab-panel-kinhnghiem" class="news-tab-panel">
                        <div class="news-list-vertical">
                            <a href="bai-viet.html" class="news-row-item">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/03/top-5-ly-do-nen-chuyen-sang-xe-dien-trong-nam-2026.jpg" alt="Bài viết">
                                <div class="news-row-info">
                                    <h5>Mẹo bảo quản ắc quy xe đạp điện không bị chai phồng</h5>
                                    <span>12 Tháng 6, 2026</span>
                                </div>
                            </a>
                            <a href="bai-viet.html" class="news-row-item">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/01/chi-tiet-lo-trinh-han-che-xe-xang-dau-vao-trung-tam-tphcm.jpg" alt="Bài viết">
                                <div class="news-row-info">
                                    <h5>Cách xử lý nhanh khi xe máy điện bị ngập nước mùa mưa</h5>
                                    <span>04 Tháng 6, 2026</span>
                                </div>
                            </a>
                            <a href="tin-tuc.html" class="news-see-more-link">Xem tất cả Kinh Nghiệm <i class="fa-solid fa-chevron-right"></i></a>
                        </div>
                    </div>

                    <!-- Tab: Thị Trường -->
                    <div id="news-tab-panel-thitruong" class="news-tab-panel">
                        <div class="news-list-vertical">
                            <a href="bai-viet.html" class="news-row-item">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/03/gia-xang-dau-2026-xu-huong-chuyen-dich-sang-xe-dien-2.jpg" alt="Bài viết">
                                <div class="news-row-info">
                                    <h5>Xu hướng thị trường xe máy điện thông minh Việt Nam 2026</h5>
                                    <span>18 Tháng 6, 2026</span>
                                </div>
                            </a>
                            <a href="bai-viet.html" class="news-row-item">
                                <img src="https://dailyxedien.vn/wp-content/uploads/2026/01/chi-tiet-lo-trinh-han-che-xe-xang-dau-vao-trung-tam-tphcm.jpg" alt="Bài viết">
                                <div class="news-row-info">
                                    <h5>Lộ trình cấm xe xăng tại các thành phố lớn và tác động đến thị trường</h5>
                                    <span>10 Tháng 5, 2026</span>
                                </div>
                            </a>
                            <a href="tin-tuc.html" class="news-see-more-link">Xem tất cả Thị Trường <i class="fa-solid fa-chevron-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        `;
        document.body.insertAdjacentHTML('beforeend', newsPanelHTML);
    }
}

window.openNewsPanel = function() {
    const o = document.getElementById('news-panel-overlay');
    const p = document.getElementById('news-panel');
    if (o && p) {
        o.style.display = 'block';
        p.style.display = 'block';
        lockScroll();
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                o.classList.add('open');
                p.classList.add('open');
            });
        });
    }
};

window.closeNewsPanel = function() {
    const o = document.getElementById('news-panel-overlay');
    const p = document.getElementById('news-panel');
    if (o && p) {
        o.classList.remove('open');
        p.classList.remove('open');
        unlockScroll();
        setTimeout(() => {
            o.style.display = 'none';
            p.style.display = 'none';
        }, 300);
    }
};

window.switchNewsTab = function(e, tabId) {
    e.preventDefault();
    
    // Deactivate all tab items
    const tabItems = document.querySelectorAll('.news-tab-item');
    tabItems.forEach(item => item.classList.remove('active'));
    
    // Activate clicked tab item
    e.currentTarget.classList.add('active');
    
    // Hide all panels
    const panels = document.querySelectorAll('.news-tab-panel');
    panels.forEach(panel => panel.classList.remove('active'));
    
    // Show corresponding panel
    const targetPanel = document.getElementById('news-tab-panel-' + tabId);
    if (targetPanel) {
        targetPanel.classList.add('active');
        // Scroll right side back to top
        const rightContainer = document.querySelector('.news-articles-right');
        if (rightContainer) rightContainer.scrollTop = 0;
    }
};

window.switchCategoryTab = function(e, tabId) {
    e.preventDefault();
    
    // Deactivate all tab items
    const tabItems = document.querySelectorAll('.cat-tab-item');
    tabItems.forEach(item => item.classList.remove('active'));
    
    // Activate clicked tab item
    e.currentTarget.classList.add('active');
    
    // Hide all panels
    const panels = document.querySelectorAll('.cat-tab-panel');
    panels.forEach(panel => panel.classList.remove('active'));
    
    // Show corresponding panel
    const targetPanel = document.getElementById('cat-tab-panel-' + tabId);
    if (targetPanel) {
        targetPanel.classList.add('active');
        // Scroll right side back to top
        const rightContainer = document.querySelector('.cat-products-right');
        if (rightContainer) rightContainer.scrollTop = 0;
    }
};

/* ==========================================================================
   KHỞI TẠO
   ========================================================================== */
window.addEventListener('DOMContentLoaded', () => {
    renderStores();
    updateHeroSlider();
    startHeroAutoplay();
    startTestimonialAutoplay();
    initAiTabs();
    initBackToTop();
    initKeyboard();
    updateCartCount();
    initMobileBottomNav();
});
