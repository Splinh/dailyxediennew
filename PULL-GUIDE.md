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
cd /www/wwwroot/dailynew.bluerabike.com
```
*(Thư mục dự án chính thức trên VPS aaPanel).*

---

## 3. Pull code mới nhất từ GitHub
Đảm bảo bạn đã đẩy code từ máy local lên GitHub trước (`git push`). Sau đó chạy lệnh sau trên VPS:
```bash
git pull origin main
```
*Lưu ý: Nếu có xung đột file (conflict) do dữ liệu tạm trên VPS, bạn có thể reset tạm thời bằng lệnh `git stash` hoặc `git reset --hard` trước khi pull.*

---

## 4. Reload PHP-FPM để xóa OPcache
Do file CSS/JS đã được biên dịch ở local và push lên Git, sau khi `git pull` bạn chỉ cần restart dịch vụ PHP-FPM trên VPS để xóa OPcache:

```bash
systemctl restart php-fpm-84
```
*(Hoặc lệnh `/etc/init.d/php-fpm-84 reload` tùy thuộc vào hệ thống aaPanel).*

---
Chúc bạn buổi demo thành công tốt đẹp! 🎉
Nếu gặp khó khăn ở bước nào, hãy nhắn tôi hỗ trợ ngay nhé.
