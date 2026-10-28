# 16 — Bảo mật, quyền riêng tư và isolation

> 2026-10-26 | TARGET CONTROLS | Chưa kiểm thử runtime. Quyền admin/retention Q-020/021 còn mở.

Phase2 ngày2026-10-27: đã kiểm thử QR/late/expiry/snapshot/removed và manual correction theo DEC-017. Chỉ owner sửa, reason/version bắt buộc, audit before/after với actor/time; student chỉ self QR. Snapshot không thay thế kiểm active membership hiện tại. HMAC token/private response, không đưa token vào audit/outbox. Chưa có stress race/concurrency hoặc chống chia sẻ QR bằng vị trí. Quyền rời/bị loại đã chốt DEC-013; retention hiện mở ở Q-025, admin ở Q-021.

## Boundary bắt buộc

1. Actor từ phiên server; role từ nguồn server có hiệu lực khi account/role thay đổi. Teacher owner query lớp, student active membership query tài nguyên rồi mới count/page. Không mass assign owner/role/status/grade từ client.
2. Child resource phải thuộc parent: session–class, assignment–class, revision–submission–student, file–revision, attempt–quiz, message–conversation–participant. Không tin hai ID hợp lệ riêng rẽ là cùng scope.
3. Grades/draft quiz answer key/roster PII được redaction trong Resource server, không chỉ ẩn UI. HS không thấy điểm HS khác kể cả export, aggregate, logs hay notifications.
4. Teacher khác là một đối tượng không được đọc, kể cả cùng role teacher. Admin không mặc nhiên quyền nội dung cho đến khi Q-021 chốt; mọi truy cập hỗ trợ sau này cần mục đích và audit.
5. Background jobs, cache và WS có actor/scope và kiểm lại quyền; cache key có user/class, logout clear cache. Không cache private content ở CDN công khai.

## Threat → control → evidence

| Nguy cơ | Control dự kiến | TC |
| --- | --- | --- |
| IDOR/cross-teacher/student | Policies + scoped query + FK relation checks ở HTTP/files/job/channel | 003/007/011/019 |
| CSRF/session theft/fixation | HttpOnly/Secure session, CSRF, allowlist CORS, regenerate login, revoke lock/reset, TLS | 001/002 |
| QR replay/chia sẻ | Challenge TTL/version, membership/window server, unique check-in, rate; không chứng minh vị trí | 007/008 |
| Brute-force mã/login/reset | CSPRNG invite, hash token, rate limit, generic failure, token expiry/single use | 001/006 |
| XSS/SQL injection/mass assignment | FormRequest allowlist, Eloquent binding, content sanitize, no unsafe render, CSP phù hợp | 003/016 |
| File executable/malware/leak | MIME+signature/size/quota, private disk, scan, no executable serving, download authorization | 017 |
| Lộ đáp án/điểm nháp | Separate author/student Resources; không serialize key/score trước publish | 011/012 |
| Quiz/submission race | Server clock/lock/version/unique/idempotency, snapshot immutable | 008/010/013 |
| Socket còn quyền sau remove | Recheck participant trước delivery, disconnect/revoke channel, tránh payload full content tới class-wide channel | 019 |
| Formula injection qua Excel | Escape untrusted cell values, fixed types, không evaluate formula từ upload | 020 |

## Dữ liệu và retention

PII tối thiểu: email/displayName, mã HS tùy chọn, phone/avatar nếu cần; birthday/gender không bắt buộc chỉ vì schema cũ có. Dữ liệu nhạy cảm: bài nộp/điểm/chat và login tokens. Chưa quyết định đối tượng tuổi/trường học nên không tự tuyên bố tuân thủ chuẩn pháp lý cụ thể; retention/consent cần chủ dự án chốt trước release.

Đề xuất archive class và giữ học liệu/bài nộp/điểm/audit; không tự purge user/class. Quyền sau leave/remove, account delete, lịch lưu chat/file, export self và việc hỗ trợ admin là Q-020/021. Staged files có cleanup TTL kỹ thuật, phải không xóa file đã attach hoặc đang submit; TTL chính xác chốt cùng quota. Không lưu ảnh mặt/tọa độ/IP để điểm danh; access logs hạ tầng nếu có phải giảm thiểu và có retention.

## Secrets/logs/abuse

Secrets trong môi trường riêng, không Git/docs/screenshots. Log traceId/action/resource/status/duration, không password/cookie/QR token/invite code/reset query/chat body/bài file. Sanitized audit trước/sau cho approval/grade/account state. Rate theo actor+endpoint và IP hạ tầng ở mức chống abuse, không dùng IP để quyết attendance. Chưa có UI báo cáo/block chat; chốt moderation trước mở public nếu cần.

Không gửi SMTP thật hoặc import dump thật khi test. Dependency audit/license và secret scan trước release; phát hiện secret cũ không sao chép vào báo cáo. Giá trị secret đã tồn tại trong repo cũ cần xử lý ở tác vụ riêng, không tái sử dụng cho project mới.

## File bài tập hiện có — 2026-10-27

File nằm private disk, download qua API kiểm membership/owner mỗi request; không expose storage key/sha256. Nội dung/MIME và cấu trúc Office được kiểm, không đồng nghĩa không có malware. FILE_SCANNER=local_validation chỉ dev/test, scanState validated_local; production fail closed và phải cấu hình ClamAV. Chưa cài scanner daemon, quarantine dashboard/quota/purge hoặc retention tự động. Chi tiết [ASSIGNMENTS-GRADES](ASSIGNMENTS-GRADES.md).

## Admin — DEC-027 / TASK-024

CONFIRMED: admin chỉ thống kê tổng hợp, tìm/lọc tài khoản, khóa/mở khóa có lý do và xem nhật ký quản trị. Không impersonation/đổi role/xóa user; không mặc định xem lớp, điểm, bài nộp, file hoặc chat. Không có public admin registration. Admin policy kiểm verified/active/role; action khóa lại actor từ DB trong transaction để chặn actor stale đã bị khóa. Khóa các admin theo id ổn định trước subject; expectedVersion ngăn ghi đè và invariant ít nhất1activeadmin/tự khóa được kiểm tại action.

Dùng RevokeAccountSessions sẵn có: xóa database sessions của subject, đổi remember token và tăng auth_version/version khi lock và unlock; mở khóa không hồi sinh cookie cũ. Browser đã kiểm học sinh đang đăng nhập nhận401 sau khóa và vẫn401 sau mở khóa cho đến đăng nhập lại. Resource chỉ trả trường cần quản trị. Audit không chứa bài nộp/điểm/chat/password; lý do do admin nhập vẫn phải tránh dữ liệu nhạy cảm không cần thiết.

Demo chỉ dữ liệu synthetic trên attendance_demo; password mạnh ngẫu nhiên ở ignored env/work, không trong docs/seed/report. local_validation cho APP_ENV=demo được chấp nhận như local/testing/http-smoke, **không phải antivirus scan**; production vẫn yêu cầu scanner thật và từ chối file chưa được duyệt. Raw browser traces/cookie jars/SQL dump/backup chỉ ở thư mục ignored, không chia sẻ công khai.
