# Demo tuyển dụng

## Phạm vi và dữ liệu

DEC-048: được chủ dự án cho phép deploy/reset bộ riêng. Giáo viên teacher@demo.attendance.test, học sinh student@demo.attendance.test; mật khẩu công khai **AttendanceDemo!2026**. Không phải secret production. Không công khai tài khoản quản trị. Mọi tên, mã, ngày sinh, nội dung, điểm đều là giả lập.

Baseline: 1 giáo viên, 12 học sinh; 3 lớp, mỗi lớp 10 thành viên active và 2 pending; 21 buổi, 120 kết quả attendance chia present/late/absent, có đã duyệt và chờ duyệt; 12 bài (9published/3draft), bài nộp ghi chú, điểm nháp/công bố; 9bản tin cùng bình luận/kiểm duyệt và thông báo phát sinh từ action thật. Không giả tệp đã qua quét virus hay giả lượt camera QR. Lịch dựa vào thời điểm reset; dữ liệu cũ không bị biến thành upcoming sai.

## Reset và giới hạn

PORTFOLIO_DEMO_ENABLED=true bật lệnh php artisan demo:portfolio ở startup và kiểm tra hourly. Lệnh chỉ chạy trên isolated MySQL test hoặc đúng service Render/Aiven đã duyệt, MAIL_MAILER=array. Marker khóa transaction, tạo lần đầu idempotent; sau24h hoặc vượt ngưỡng khôi phục baseline, giữ ID tài khoản, thay ID lớp/bài. Mật khẩu demo ổn định. Render Free ngủ thì scheduler dừng: lần khởi động hoặc đăng nhập demo hợp lệ tiếp theo chạy bù, không cam kết một giờ reset cố định.

Ngưỡng phát hiện: hơn6lớp,20môn,10học kỳ,100buổi,60bài tập,60bản tin,200bình luận,500lần nộp hoặc1000thông báo; giới hạn1000audit/event cho mỗi nhóm điểm danh, enrollment, điểm và kiểm duyệt bình luận. Kiểm tra trước mutation kế tiếp, vì một thao tác có thể tạo nhiều dòng; đây là ngưỡng bảo trì, không phải quota byte tuyệt đối. Vượt ngưỡng trả DEMO_LIMIT tiếng Việt; đăng xuất/đăng nhập lại hoặc lượt scheduler sau sẽ reset. Giữ rate limit hiện có. Demo khóa sửa hồ sơ/mật khẩu và upload vật lý (nộp ghi chú vẫn được); banner được giới hạn bởi validation hiện có. Tài khoản demo không tham gia lớp ngoài demo, người ngoài không tham gia lớp demo.

Reset xóa theo owner giáo viên demo và phụ thuộc, trong một transaction, giữ FK; không xóa user ngoài namespace, không xóa lớp của teacher khác, không chuyển/import dữ liệu cũ. Nếu email trùng danh tính có sẵn, owner đổi hoặc có physical file bất thường thì dừng và rollback để kiểm tra. Không có endpoint reset công khai. Các thay đổi trong demo được dùng chung bởi mọi người thử và có thể mất sau reset; không nhập dữ liệu thật.

## Kiểm chứng

PortfolioDemoTest kiểm tra seed idempotent, reset cả quan hệ grades/attendance/comments không lỗi FK, giữ lớp ngoài demo, chặn upload/đổi mật khẩu, email collision rollback, quota và phục hồi. Frontend lint/47unit/build. Ảnh và kiểm tra production sẽ được ghi khi bản triển khai hoàn tất.
