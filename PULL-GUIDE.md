# Hướng dẫn deploy cập nhật từ Git lên VPS Demo

Tài liệu này hướng dẫn các bước SSH vào VPS và pull code mới nhất để cập nhật trang Demo.

---

## 1. Truy cập vào VPS qua SSH
Mở terminal trên máy tính của bạn (PowerShell, Command Prompt hoặc Git Bash) và chạy lệnh:
```bash
ssh root@<IP_CỦA_VPS>
```
*(Nhập mật khẩu SSH của VPS nếu được yêu cầu).*

---

## 2. Di chuyển vào thư mục code dự án trên VPS
Di chuyển đến thư mục chứa mã nguồn của website:
```bash
cd /var/www/dailynew
```
*(Thay thế `/var/www/dailynew` bằng đường dẫn chính xác thư mục dự án trên VPS của bạn, ví dụ: `/www/wwwroot/dailynew.vn` trên aaPanel).*

---

## 3. Pull code mới nhất từ GitHub
Đảm bảo bạn đã đẩy code từ máy local lên GitHub trước (`git push`). Sau đó chạy lệnh sau trên VPS:
```bash
git pull origin main
```
*Lưu ý: Nếu có xung đột file (conflict) do dữ liệu tạm trên VPS, bạn có thể reset tạm thời bằng lệnh `git stash` hoặc `git reset --hard` trước khi pull.*

---

## 4. Biên dịch lại giao diện (Compile Assets)
Do dự án sử dụng Vite và Tailwind 4, sau khi pull code mới về, bạn cần chạy build để tạo các file CSS/JS tối ưu cho môi trường Production:
```bash
pnpm build
```
*(Nếu trên VPS chưa cài pnpm, bạn có thể chạy `npm run build` hoặc cài pnpm toàn cục bằng lệnh: `npm install -g pnpm`)*.

---

## 5. Xóa Cache hệ thống & OPcache
Để đảm bảo code PHP mới được load ngay lập tức mà không bị OPcache hay cache WordPress giữ lại:

* **Xóa cache WordPress (WP-CLI):**
  ```bash
  wp cache flush
  ```
  *(Hoặc chạy qua PHP nếu không có lệnh global: `php vendor/wp-cli/wp-cli/php/boot-fs.php cache flush`)*.

* **Reload PHP-FPM (Xóa cache OPcache):**
  * Đối với aaPanel / CyberPanel: Bạn có thể vào bảng quản trị Web Server chọn **Restart/Reload PHP** (phiên bản PHP đang dùng, ví dụ PHP 8.3).
  * Hoặc chạy lệnh trực tiếp trong Terminal VPS:
    ```bash
    sudo systemctl reload php-fpm
    ```
    *(Tùy thuộc hệ điều hành VPS: `sudo systemctl restart php8.3-fpm` hoặc tương đương).*

---
Chúc bạn buổi demo thành công tốt đẹp! 🎉
Nếu gặp khó khăn ở bước nào, hãy nhắn tôi hỗ trợ ngay nhé.
