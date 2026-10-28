# API hiện có — bắt đầu đọc ở đây khi chạy backend

> 2026-10-27 | CURRENT — API phase3, verified local | Source ở `attendance_api`, frontend `atd_react` đã có React theo DEC-022/023; xem FRONTEND-IMPLEMENTATION. Đây không phải tuyên bố hoàn tất toàn sản phẩm.

Phase2 đã có lịch giờ thực/lặp tuần, QR30s/TTL60s với snapshot roster, teacher sửa tay có reason/version/audit, chốt vắng và summary. Xem [contract lịch/điểm danh](SCHEDULE-ATTENDANCE.md) và [kiểm chứng phase2](evidence/schedule-attendance-validation.md). Q-011/023/024 đã trả lời; DEC-017 cho phép sửa tay thay giới hạn cũ.

## Công nghệ và môi trường đã có

PHP8.4.27 (Windows package8.4.27-1), Composer2.10.3, Laravel13.38.0, Sanctum4.3.3, MySQL8.4.13. Packages pin bằng `attendance_api/composer.lock`; PHP/Composer/MySQL portable ở `.tools` đã ignore, không thay PATH/cài Windows service. MySQL chỉ bind127.0.0.1:33306; user app giới hạn schema, root password ngẫu nhiên chỉ trong file local ignored.

DB `attendance_dev` cho app local, `attendance_test` cho PHPUnit, `attendance_http_test` cho HTTP smoke; users/schema tách riêng. `.env`, `.env.testing`, `.env.http-smoke` local có secret và đã ignore. Chưa có cloud, production, dữ liệu thật hoặc dữ liệu cũ import. Không chia sẻ các file này trong ZIP/Git.

## Chức năng đã code

| Module | Đã có | Chưa có / giới hạn |
| --- | --- | --- |
| Auth | Register teacher/student một role, normalize email, hash password, login/logout cookie, signed email verification, resend, forgot/reset, change password, rate limits | Chưa OAuth/admin provisioning/UI; mail array local không gửi mail thật |
| Session | CSRF cả login và mutation; HttpOnly cookie, encrypted DB session; password reset/change tăng authVersion và rotate remember token; account locked bị chặn request đang đăng nhập | Chưa UI danh sách session/revoke từng thiết bị |
| Self profile | GET me, PATCH displayName/phone/locale/timezone có expectedVersion; không cho client đổi role/email/owner | Chưa upload avatar/đổi email |
| Catalog | CRUD môn/học kỳ riêng từng teacher, pagination/search/sort, unique owner-code, version; delete đang dùng409 | Chưa course_types/tín chỉ/tiết; student không được đọc danh mục riêng |
| Lớp | Teacher tạo nhiều lớp; name/description/timezone, optional course/semester; read/update/archive scoped; mã mời random16hex, hash DB, rotate/disable/expiry | Không hard-delete, co-teacher/transfer owner; create lớp chưa Idempotency-Key tổng quát |
| Enrollment | Nhập mã→pending, owner list/approve/reject, self cancel/leave, owner remove/allow-rejoin; left/rejected được request lại, removed bị chặn đến khi owner cho phép | HS không có roster API; chưa Excel/import/sức chứa/quota |
| Notification | Inbox phân trang, unread count, mark-read self; membership/assignment/submission/grade events + inbox + outbox cùng transaction; duplicate pending/approve không tạo notice lặp | Chưa realtime dispatcher/Reverb, read-all, email/push/reminders |
| Calendar | Giờ thực UTC, calendar/self classes có phân trang, lặp tuần theo timezone lớp, preview/conflict acknowledgment, sửa/hủy từng buổi | Chưa sửa cả series/học bù tự động, recurrence qua đêm hoặc calendar import |
| Attendance | QR đổi30s/TTL60s, fixed roster, late/expiry, idempotent check-in, owner manual correction/audit, scheduler/fallback finalize, teacher summary + student self history | React đã render/đọc QR; chưa reopen/Excel/realtime; không GPS/IP/face |
| Bài tập/điểm | Draft/publish/close, private file, revision nộp bài, chấm nháp/publication bất biến, gradebook và average | VERIFIED LOCAL; chưa quiz/antivirus daemon/quota/purge |
| Nền tảng | JSON envelope/camelCase/traceId, errors an toàn, CORS allowlist, pagination1–100/default20, health, migrations, HTTP Feature tests, CI workflow | Chưa chạy GitHub CI thật, load/concurrency suite, backup restore production |

Assignment/private file/submission/gradebook đã có source và HTTP smoke; full suite51tests/574assertionsPASS. Chưa code announcement/comment/quiz/chat/Excel/admin. Attendance summary và average đã có; export vẫn TARGET. Không gọi các endpoint tương lai như đã chạy được.

## API hiện hành và tài liệu máy đọc

- [Route inventory thực](evidence/routes-current.json):79 operations dưới `/api/v1` sau phase3; không tính health/CSRF/docs vendor.
- [OpenAPI hiện hành](evidence/openapi-current.json): từ Scramble0.13.50, đã bổ sung envelope lỗi, array pagination, cookie/CSRF security và body create/update theo operation.
- UI docs local: `http://127.0.0.1:8000/docs/api`; JSON: `/docs/api.json` nếu dev dependency được cài. Middleware vendor chỉ cho local/được cấp quyền; không mở public production.
- Export lại từ backend: `php artisan scramble:export --path=../docs/evidence/openapi-current.json --no-interaction`. Export route: `php artisan route:list --path=api/v1 --except-vendor --json`.
- Export mặc định không warning sau chỉnh model/rules. Lần strict `--fail-on-unknown` phase1 có88 inference findings ở dynamic request/model/trace trước document-transform; chưa rerun strict cho phase2. Không dùng bản suy luận này tự động sinh client production mà không review schema. Đây không phải lỗi các HTTP flow đã test.

Nhóm route đã có: auth/register/login/logout/forgot/reset/verify/resend; me/profile/change-password; semesters/courses; classes/archive/invite-code; join-requests/self/cancel; owner requests/approve/reject/allow-rejoin/members/remove; class leave; notifications/unread/read; calendar/sessions/session-series; attendance-windows/qr/roster/close; check-ins/manual/audit/summary/self-history. Health ngoài prefix `/health/live`, `/health/ready`; CSRF `/sanctum/csrf-cookie`.

## Hợp đồng khác biệt cần nhớ

1. Create class trả201 `{data:{class:{...},inviteCode:"..."}}`, Location `/api/v1/classes/{id}`. Mã chỉ trả lúc create/rotate; list/detail không có hash/code. HS không dùng classId làm mã mời.
2. PATCH class partial + expectedVersion; PATCH course cần name, optional code + expectedVersion; PATCH semester cần name/startDate/endDate + expectedVersion, optional isActive. DELETE catalog cần expectedVersion JSON. Học kỳ ngày `yyyy-MM-dd`, JSON timestamp UTC ISO8601.
3. Join duplicate pending/active trả200, không notification lặp. Request mới hoặc request lại left/rejected trả201 cùng membershipId và version tăng. Approve cùng trạng thái active là idempotent200; approve/reject đối nghịch hoặc version cũ409.
4. Owner allow-rejoin: POST `/classes/{classroom}/join-requests/{membershipId}/allow-rejoin` + expectedVersion; chuyển removed→left, chưa active. HS vẫn gửi mã rồi owner duyệt.
5. `GET /classes` teacher chỉ own, student chỉ active memberships, pending/left/removed không có trong items/total. Danh sách HS (`members`) chỉ owner; quyền roster HS chưa được chốt nên không mở.
6. Create class/catalog chưa có general Idempotency-Key; không tự retry POST create khi timeout. Inbox/outbox đã durable trong DB nhưng chưa có realtime dispatch; client có thể GET inbox.

## Chạy local — PowerShell tại root AttendanceWeb

```powershell
.\scripts\mysql.ps1 -Action status
.\scripts\mysql.ps1 -Action start
.\scripts\artisan.ps1 migrate --no-interaction
.\scripts\artisan.ps1 serve --host=127.0.0.1 --port=8000 --no-reload
```

Các lệnh migration chỉ trỏ DB local mới đã cấu hình; không dùng config/dump cũ. PHP portable không nằm global PATH; dùng script từ root hoặc đường dẫn `.tools/php/php.exe`.

Terminal riêng nếu cần xử lý mail jobs: `.\scripts\artisan.ps1 queue:work`. Hiện `MAIL_MAILER=array` không gửi/lưu email có thể nhận; cần cấu hình mailbox dev/SMTP được chủ dự án cho phép để thử verify/reset qua email thật. Tests dùng fake notifications và token synthetic, không gửi email thật. Không seed account mặc định hoặc admin password. React đã có trang reset-password; email local array vẫn không gửi mail thật.

Tắt MySQL local an toàn: `.\scripts\mysql.ps1 -Action stop`. Dừng API bằng Ctrl+C ở terminal chạy; không kill MySQL bằng PID khi có thể shutdown bằng mysqladmin. Lệnh start không tự initialize/xóa data directory.

## Kết nối từ React hiện có

Origin dev allowlist: `http://127.0.0.1:5173`, `http://localhost:5173`; chọn cùng hostname cho web/API để cookie hoạt động. GET `/sanctum/csrf-cookie` với credentials trước login, đọc cookie XSRF-TOKEN, gửi giá trị đã URL-decode trong X-XSRF-TOKEN, mọi request có credentials. Không lưu bearer token/localStorage vì API này không cấp token.

Đăng ký body `{displayName,email,password,passwordConfirmation,role}`; password kỹ thuật hiện≥12 ký tự, có chữ hoa/thường và số, tối đa128; role teacher/student. Sau login, chưa verify vẫn đọc me/logout/resend nhưng không workspace classes/catalog/enrollment. Verify signed URL phải đúng authenticated account/expiry/signature.

## Kiểm thử và bằng chứng

Từ root: `.\scripts\php.ps1 attendance_api/vendor/bin/phpunit -c attendance_api/phpunit.xml` không khuyến nghị vì cwd khác ảnh hưởng môi trường; dùng backend cwd hoặc [scripts/verify-api.ps1](../scripts/verify-api.ps1). Tests guard bắt buộc MySQL database `attendance_test` hoặc `attendance_ci_test`, từ chối DB khác trước RefreshDatabase. Không SQLite fallback.

Xem [báo cáo API](evidence/api-validation.md) và [worklog](worklog.md) cho command/result cuối. Chưa load test, browser UI, SMTP thật, Reverb, production hoặc migrate dữ liệu cũ. GitHub Actions workflow mới được viết, chưa push/chạy remote.

## Learning API verified local — 2026-10-27

TASK-010/011 có20operation mới về assignment/file/submission/grade, VERIFIED LOCAL theo [evidence](evidence/learning-validation.md). Xem [ASSIGNMENTS-GRADES](ASSIGNMENTS-GRADES.md) cho payload/flow và giới hạn file/scanner. Evidence core/lịch QR phía trên không chứng minh các endpoint mới. Quiz vẫn chưa có runtime; [QUIZZES](QUIZZES.md) ghi DEC-021 và câu hỏi còn mở.

### Nghiệm thu learning local cuối — 2026-10-27

Các kết quả trung gian phía trên đã được thay thế: full suite cuối51tests/574assertionsPASS, HTTP36checksPASS. Xem [learning-validation](evidence/learning-validation.md); runtime79operations. Chưa quiz/chat/production; React core đã triển khai theo evidence/react-validation.md.

## AttendanceWeb2.0 — 2026-10-28

Thêm GET /api/v1/dashboard, query tổng hợp theo owner/active membership; counts độc lập preview limit, phân biệt teacher/student, điểm nháp không xuất hiện ở student. Contract API-010 tại [15-api-contract](15-api-contract.md). Class detail student có membershipVersion để leave an toàn; attendance list trả tên học sinh/tiêu đề/giờ buổi thay mã opaque ở UI. Không có migration schema hoặc thay auth/QR30/60.

Target workload đã CONFIRMED3teacher/90student/30check-in đồng thời. Script scripts/verify-attendance-concurrency.php chỉ cho HTTP test DB+array mail, tạo fixture và dùng30PHPprocess có barrier để gọi action thật, kiểm record/replay với MySQL. Chưa kiểm production HTTP capacity hoặc đặt SLA; không dùng dev server PHP1worker để cam kết concurrency HTTP30. Kết quả tại [kiểm thử](06-testing.md).

## Admin/demo — TASK-024 (2026-10-28)

Runtime thêm5operation admin/summary, admin/users, users/{id}/lock, users/{id}/unlock, admin/audit; tổng85operation theo OpenAPI export không warning. Contract API-011 ở15, Policy admin không có quyền nội dung lớp/file/điểm. Hai migration bổ sung admin audit và demo manifest, seed riêng opt-in. PHP101tests/838assertionsPASS, HTTP30+30replayPASS và browser adminPASS; các snapshot NOT RUN trước đây chỉ đúng tại ngày ghi, xem06-testing cho evidence hiện hành. Chưa production/SMTP/ClamAV/realtime dispatcher.
