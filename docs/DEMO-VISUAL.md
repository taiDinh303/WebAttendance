# Dữ liệu minh họa 9 lớp

2026-11-02 — Chủ dự án giao tạo trên Aiven và cho phép tự deploy cho công việc này; workspace Render My Workspace/dinhvantai079@gmail.com đã xác nhận. Chỉ service attendanceweb-api/srv-dbh23o6c6qcc73csmo0g và Aiven attendanceweb-mysql-free, không đổi gói Free.

## Phạm vi

Giữ3teacher gv001–003@attendance.test và3lớp demo cũ10HS. Thêm mỗi teacher lớp20/30HS, thêm150student hs031–180@attendance.test. Tổng phạm vi seed9lớp/180HSactive, thêm6yêu cầu chờ duyệt của học sinh demo hiện có, không thêm người ngoài phạm vi. Dữ liệu/tài khoản khác không xóa. Điền thiếu mãSV/giới tính/ngày sinh/avatar trên30student demo cũ, không ghi đè trường đã có hoặc mật khẩu.

Mỗi lớp:4buổi quá khứ đã chốt có mặt/muộn/vắng và audit ghi rõ seed;3buổi hôm nay/sắp tới;3bài published và1draft; bài nộp giả chỉ có ghi chú (không giả file đã scan), điểm draft/published và một phần chưa nộp;2bản tin published/1draft với bình luận live/hidden/deleted/audit. Có inbox/outbox từ action nghiệp vụ nhưng không gửi mail thật.

Dự kiến63sessions,720attendance records/audits,36assignments,432submissions,216grade publications,27announcements,72comments gồm deleted. Các con số chỉ bộ dữ liệu mới/reused9lớp, không khẳng định toàn DB không còn dữ liệu khác.

## Chạy an toàn

`php artisan demo:visual`: config VISUAL_DEMO_ENABLED=true, MAIL_MAILER=array, password từ DEMO_SETUP_PASSWORD server secret (không log/commit). Production chỉ đúng Render service ID và hostname Aiven đã duyệt. Test chỉ MySQL isolated attendance_test/attendance_ci_test local. Từ chối nếu không đúng3teacher synthetic + mỗi người1class active10student synthetic, hoặc namespace email xung đột. Toàn bộ trong transaction, marker demo_datasets khóa chống chạy đồng thời, rerun giữ sửa tay. Không dùng --fresh/reset/truncate.

Khôi phục Dockerfile/apache/.dockerignore/entrypoint từng triển khai ở107fee1 vào main (trước đó main thiếu), giữ CA read protection và explicit TLS verify. Startup migrate trước seed, rồi route cache/Apache. Seed tạo note-only revisions không lưu file trên disk tạm. Không gọi upload/scanner/cloudbackup đã verified.

Sau seedLive: tắt VISUAL_DEMO_ENABLED, redeploy cùng source để runtime flagfalse. RUN_MIGRATIONS là migrate tăng dần, không rollback dữ liệu. Không reset tài khoản/demo namespace trên redeploy; marker trả created=false.

## Bằng chứng

VisualDemoTest kiểm shape/counts/idempotency/password/edit-preservation và guard opt-in trên MySQL test. Test không chứng minh cloud đã chạy; kết quả deploy và log seed actual ghi worklog/11-setup-cloud.
