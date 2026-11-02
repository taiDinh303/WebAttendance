# 11 — Trạng thái cloud AttendanceWeb

## Kiểm chứng ngày2026-11-02

| Thành phần | Trạng thái quan sát được |
|---|---|
| Frontend | https://attendanceweb-psi.vercel.app; guest API /api/v1/me qua proxy trả401 JSON |
| Render | attendanceweb-api, srv-dbh23o6c6qcc73csmo0g; My Workspace/dinhvantai079@gmail.com do chủ dự án xác nhận; Free/Singapore; branchmain, rootattendance_api, manual deploy |
| Backend source | 11ba4cd37d1296a219063f21048f49f127e16d1d |
| Aiven | attendanceweb-mysql-free, free-1-1gb, do-sgp, RUNNING qua connector trước deploy |
| Migration | Render log DONE các migration admin/demo/profile/announcement/audit/student_code tới2026_11_01_120000 |
| Seed deploy | dep-dbk5qcmc6qcc73dk5n20 Live; summary DB created=true,3teacher/9class/180student, mỗi owner10/20/30,720attendance records |
| Tắt seed | VISUAL_DEMO_ENABLED=false đã lưu; dep-dbk5s5m7bikc73987dbg Live cùng source |
| HTTP | HTTPS /health/ready200; guest /api/v1/me qua Vercel401; không coi là test login authenticated mới |

## Dữ liệu minh họa

[DEMO-VISUAL](DEMO-VISUAL.md): giữ tài khoản gv001–003@attendance.test và3class10HS cũ, thêm150student hs031–180@attendance.test cùng6class20/30HS. Password tài khoản cũ không reset; mật khẩu khởi tạo tài khoản mới dùng server secret đã có, không ghi ở docs/source. Synthetic accounts verified chỉ trong seed riêng, không thay quy tắc đăng ký thường.

Thêm lịch/điểm danh/audit, bài tập/điểm, bản tin/bình luận và inbox/outbox. Bài nộp demo chỉ có ghi chú, không tạo file giả trên disk tạm hoặc bỏ qua scanner. Data khác không xóa/import. Dataset marker chống reseed/reset; MAIL_MAILER=array, không gửi mail thật.

## Triển khai hiện hành

Dockerfile/apache/.dockerignore/entrypoint được đưa lại về main từ cấu hình đã chạy ở107fee1, giữ PHPzip/CA mode0400 và PDO certificate verification. Startup config cache → migrate tăng dần khi RUN_MIGRATIONS=true → seed nếu explicit flagtrue → routecache → Apache PORT10000. Sau công việc này flagseedfalse. Service vẫn manual/off; không bật auto deploy hoặc đổi plan.

Chủ dự án đã cấp quyền tự deploy trong công việc ngày2026-11-02. Mọi thay đổi về plan trả phí, SMTP thật, storage hoặc import dữ liệu cũ vẫn cần phạm vi riêng. Kiểm đối tượng/workspace/service và source trước release, không in secrets từ Dashboard/API.

## Chưa nghiệm thu

Authenticated browser flow trên cloud chưa chạy lại trong đợt seed này. Storageprivate/upload/scanner/mail thật, dispatcher/realtime, cloudbackup/restore/RPO-RTO và capacity vẫn chưa VERIFIED. Readiness200 không thay các bước đó. Checklist release tại [18](18-release-checklist.md), lệnh/kết quả thực tế tại [worklog](worklog.md).
