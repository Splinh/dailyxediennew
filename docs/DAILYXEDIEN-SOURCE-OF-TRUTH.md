# DailyXeDien Source Of Truth

Created: 2026-06-08
Primary source: https://dailyxedien.vn/

## Purpose

Web mới là bản rebuild từ `dailyxedien.vn`, nên thông tin brand/content/contact phải lấy từ site DailyXeDien hiện tại.

Không dùng dữ liệu demo Lac Huy/Thaphaco cho nội dung thật. Các dữ liệu demo chỉ được phép dùng làm fallback kỹ thuật tạm thời nếu chưa có ACF hoặc chưa import.

## Source Priority

| Priority | Source | Use |
|---|---|---|
| 1 | `dailyxedien.vn` live site | Company info, contact, menu/category structure, product categories, news categories, footer policy, service text. |
| 2 | Google Sheet `DailyXeDien_Rebuild_Plan` | Timeline, task tracking, plugin mapping, KPI tracking. |
| 3 | `htmlmau/*.html` | Layout/design reference only. Do not treat as final business data if it conflicts with live site. |
| 4 | Existing `spl` theme | Technical implementation patterns: ACF JSON, template parts, helpers, WooCommerce rendering. |
| 5 | Lac Huy/Thaphaco demo data | Placeholder only; should be removed/replaced before production. |

## Verified Live Site Data

Collected from `dailyxedien.vn` on 2026-06-08.

### Brand and SEO

| Field | Value |
|---|---|
| Site title | Đại lý Xe Điện - Xe Đạp Điện - Xe Máy Điện - Xe 3 Bánh |
| Main domain | https://dailyxedien.vn/ |
| Brand/company display | Dailyxedien.vn / Công ty TNHH Xe Điện BLUERA Việt Nhật |
| Main business | Electric bicycles, electric motorbikes, electric three-wheelers, 50cc bikes, bicycles, electric vehicle parts and services. |

### Company Info

| Field | Value |
|---|---|
| Company name | Công ty TNHH Xe Điện BLUERA Việt Nhật |
| Tax code | 0312473259 |
| Tax issue note | Do Sở KH & ĐT TP. HCM cấp ngày 23-09-2013 |
| Head office | 466 Nguyễn Duy Trinh, P. Bình Trưng, TP. Hồ Chí Minh |
| Common product-page address variant | 466 Nguyễn Duy Trinh, P. Bình Trưng Đông, TP. Thủ Đức, TP. HCM |
| Working hours | Thứ 2 - Chủ nhật (8:00AM - 08:00PM) |

### Contact

| Field | Value |
|---|---|
| Hotline | 0933 505 222 |
| Landline | 028 2253 0524 |
| Email | Dailyxedien.vn@gmail.com |
| Website | https://dailyxedien.vn/ |
| Fanpage label | Đại Lý Xe Điện Bluera & Bluera Việt Nhật |

### Top Bar Links

| Label | Source meaning |
|---|---|
| SỨ MỆNH | About/mission page |
| CƠ HỘI HỢP TÁC | Dealer/cooperation page |
| HỆ THỐNG CỬA HÀNG | Store system page |
| Đăng nhập / Đăng ký | WooCommerce account |

### Main Product Categories

Use these as the initial product menu/category source:

| Category | Notes |
|---|---|
| Xe Đạp Trợ Lực | Includes Bluesuda, ADO. |
| Xe Đạp Điện | Includes Bluera, AI Ebike, ADO, Asama, FMT, Honda, Nijia, Osakar, Pega, Yadea, Yamaha, Concise, Nhập Khẩu. |
| Ô Tô Điện | Buggy, Chở Khách, Sân Golf. |
| Xe Điện Cũ | Xe Đạp Điện Cũ, Xe Điện Thanh Lý. |
| Xe Máy Điện | Pusan, Anbico, Lixi, Nijia, TH CEO, Yadea, Xe Giao Hàng. |
| Xe Trẻ Em | Xe Đạp Trẻ Em, Xe Mô Tô Trẻ Em, Xe Ô Tô Điện Trẻ Em. |
| Xe Pin | Scooter, Lihaze, NIJIA SMART, FMT Aitefu. |
| Xe Ba Gác Điện | Bluera, Nhập Khẩu. |
| Xe Máy 50CC | Xe Cub 50CC, Xe Tay Ga 50CC, Mô Tô 110CC, Xe Máy Khác. |
| Xe 3 Bánh | Xe Điện 3 Bánh Chế, Xe Điện 3 Bánh Nhập, Xe Máy 3 Bánh Chế, Xe Lăn Điện. |
| Xe Đạp | Xe Đạp Nhật, Xe Đạp Mini, Xe Đạp Thể Thao, Xe Đạp Nhập Khẩu, Xe Đạp Thông Dụng, Xe Đạp Cũ. |
| Phụ Tùng Xe Điện | Ắc Quy - Pin, Board - Điều Khiển - IC, Chân Chống, Dè - Chắn Bùn, Đèn/Còi/Xi Nhan, Sạc, Săm - Lốp, Tay Ga - Tay Thắng - Công Tắc, etc. |

### News Categories

| Group | Categories |
|---|---|
| Tin Dailyxedien | Khuyến Mãi, Sự Kiện, Kinh Nghiệm, Video, Tuyển Dụng |
| Tin Cộng Đồng | Công Nghệ, Du Lịch, Thể Thao, Ảnh Đẹp, Thị Trường Xe Điện |

### Services

| Service |
|---|
| Sửa Chữa |
| Nâng Cấp |
| Bảo Dưỡng |
| Bảo Hành |
| Cứu Hộ |

### Homepage Trust/USP Items

The live homepage includes these trust points:

| Label | Description |
|---|---|
| Chính Hãng | Cam kết chính hãng |
| Giao Hàng | Giao hàng miễn phí |
| Trả Góp | 0% lãi suất, thủ tục nhanh |
| Bảo Hành | Bảo hành 12 tháng |
| Dịch Vụ | Sửa chữa và thay thế |
| Hệ Thống | Có mặt trên toàn quốc |

## Implementation Rules

1. ACF Options populate script must use the company/contact data above.
2. Homepage flexible demo data must use DailyXeDien labels, categories, service text, and CTA text from live site.
3. Product sections should query imported WooCommerce categories matching the live site category names.
4. Footer company block must not use Lac Huy/Thaphaco values.
5. If `htmlmau/index.html` conflicts with live site business data, keep the layout from `htmlmau` but use content from `dailyxedien.vn`.
6. Any future content scraping/import should record the source URL and date in `docs/PLAN-TRACKING.md`.
