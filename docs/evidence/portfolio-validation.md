# Kiểm chứng portfolio — 2026-11-03

## Phạm vi

DEC-048, dữ liệu hoàn toàn giả lập, release107b769 trên Render attendanceweb-api và frontend attendanceweb-psi.vercel.app. Không dùng credentials visual cũ. Render deployment dep-dbkssd4830cc73f6527g live; startup log Portfolio ready:3classes, baseline2026-11-03T12:08:04Z. Bật PORTFOLIO_DEMO_ENABLED bằng merge env, không thay secrets/gói dịch vụ.

## VERIFIED local

- PHPUnit toàn bộ131tests/1186assertions PASS trước bổ sung note-only demo. Sau sửa note-only: PortfolioDemoTest3tests/21assertions PASS, bao gồm HTTP tạo note/retry cùng Idempotency-Key/ghi chú rỗng422. Normal nộp bài giữ requiredfile; Regression suite trước đó kiểm tra contract bình thường.
- Vitest48tests PASS (thêm thử note-only/retry, vẫn từ chối khôngfile ở flow thường), ESLint và production build PASS. Pint PASS.
- Reset: idempotent khi chưa đến hạn; reset sau24h; quota chặn và reset phục hồi; giữ lớp của ownerkhác; email collision failclosed; chặn profile/password/upload demo; dependencies attendance/grades/comments xóa transaction không lỗi FK.

## VERIFIED production

| Luồng | Bằng chứng |
| --- | --- |
| Đăng nhập teacher/student | Browser thật, cookie/CSRF, dashboard có3lớp |
| Đọc chức năng |12màn giáo viên và các màn học sinh dùng API thật; không pageerror trong capture |
| Tạo buổi/mở QR | POSTsessions201, POSTwindow201, QRký thật trong modal |
| Học sinh active điểm danh | UI xác nhận, HTTP201, ảnh student-check-in |
| Chặn điểm danh lại | HTTP409 ALREADY_CHECKED_IN |
| Đóng QR và duyệt | Close200, approve200 approvedAt có giá trị |
| Khóa sửa sau duyệt | PUTcorrect409 ATTENDANCE_APPROVED |
| Duyệt vào lớp | Pending request có dữ liệu, owner approve200 |
| Responsive/ảnh |21ảnh từ production; desktop1440x1000/mobile390x844; xem ảnh và chụp lại QR sau khi tải xong |

Nộp bài qua HTTP phát hiện request cũ bắt buộcfile; đã sửa riêng cho demo, chưa ghi VERIFIEDlive phần note-only cho đến deployment tiếp theo. Ảnh ở [FEATURE-TOUR](../FEATURE-TOUR.md). Không phát video/trailer dựng giả. Không thử camera vật lý, GoogleOAuth/email thật chưa áp dụng. Resetproduction không ép chạy để tránh mất phiên người thử: logicreset đã kiểm tra trên MySQLtest biệt lập, startup thực tế tạo baseline thành công. Không sửa dữ liệu ngoài ownerdemo.

Vercel MCP trả403 với scope team nên trạng thái UI được xác minh qua URLpublic/asset thực tế, không tuyên bố kiểm tra buildlog Vercel.
