# 17 — Chuẩn cấu hình môi trường

> 2026-10-26 | PROPOSED | Chưa có `.env.example` runtime. Tên Laravel/Reverb dưới đây cần đối chiếu version thực lúc scaffold; custom key là thiết kế mục tiêu.

| Nhóm | Key dự kiến | Phân loại / kiểm tra |
| --- | --- | --- |
| App | APP_ENV, APP_URL, APP_KEY, APP_DEBUG | KEY secret; prod DEBUG=false; URL HTTPS |
| DB | DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD | Credentials secret; engine/version chốt Q-018; test DB riêng |
| Session/CSRF | SESSION_DRIVER, SESSION_DOMAIN, SESSION_SECURE_COOKIE, SANCTUM_STATEFUL_DOMAINS | Same-site cookie, secure prod, stateful host:port dev; TTL theo policy |
| CORS | Allowed origins/credentials trong config/cors.php và env mapping riêng | Không wildcard origin cùng credentials |
| Mail | MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS | Secrets riêng; local/testing log/fake, không SMTP cũ |
| Queue/cache | QUEUE_CONNECTION, CACHE_STORE, REDIS_HOST/PASSWORD nếu dùng | Chốt driver, worker cùng env, retry/timeout tương thích |
| Broadcasting | BROADCAST_CONNECTION, REVERB_APP_ID/KEY/SECRET, REVERB_HOST/PORT/SCHEME | SECRET private; KEY app identifier có thể public theo provider, vẫn không nhầm với secret |
| Files | FILESYSTEM_DISK + adapter bucket/region/access keys | Private, scan/quota; local path ngoài public webroot |
| Frontend | VITE_API_BASE_URL, VITE_WS_HOST/PORT/SCHEME, VITE_REVERB_APP_KEY nếu dùng | VITE_* luôn public trong bundle, không đặt DB/password/app secret |
| Domain custom | ATTENDANCE_QR_TTL_SECONDS, FILE_MAX_BYTES, FILE_MAX_COUNT, QUIZ_MAX_DURATION_SECONDS | Tên đề xuất, chưa tạo; bounds validate startup sau Q tương ứng |
| Observability | LOG_LEVEL, safe request ID config, alert endpoint secret nếu có | Không log raw request nhạy cảm; debug chỉ local |

Mọi `.env.example` chỉ placeholder `example.test`/empty secret, `.env` ignore Git. Validation startup fail rõ khi thiếu config bắt buộc; không fallback sang connection cũ hay public storage. Config cache cần rebuild/restart worker sau thay env; không rotate APP_KEY tùy tiện vì dữ liệu/session mã hóa có thể mất khả năng đọc.

Config dev/staging/prod/test tách DB, disk/bucket, queue namespace, session cookie tên/domain và Reverb app để không lẫn người dùng. Docs ghi URL/driver/region không secret ở11, ghi thay đổi thực/evidence ở worklog. Local dev/test/HTTP fixture đã có kiểm chứng theo API-CURRENT; staging/production chưa có.

## File scanner CURRENT — phase3

FILE_SCANNER=local_validation (mặc định config/filesafety.php) chỉ cho local/testing/http-smoke; production trả503. FILE_SCANNER=clamav dùng CLAMAV_HOST (mặc định127.0.0.1), CLAMAV_PORT (mặc định3310), INSTREAM fail closed khi không có xác nhận. Đây là config adapter đã có; ClamAV daemon chưa cài/test. Giới hạn upload thực hiện cố định20MiB/file và5file/revision, không phải các biến FILE_MAX_* đề xuất phía trên. PHP local ini20M/24M; reverse proxy/production chưa cấu hình.

## Cấu hình hiện hành TASK-024

.env.example đã có DEMO_SEED_ENABLED=false và DEMO_PASSWORD rỗng. Demo opt-in riêng attendance_api/.env.demo (ignored), APP_ENV=demo, APP_DEBUG=false, DB_CONNECTION=mysql, DB_HOST loopback, DB_DATABASE=attendance_demo, DB user chỉ quyền schema demo, MAIL_MAILER=array, FILE_SCANNER=local_validation, key/password mới. Không dùng credential/dump/SMTP cũ; không ghi đè .env hoặc env test. DEMO_PASSWORD tối thiểu12 ký tự gồm chữ thường/hoa/số, bắt buộc trước seed; thay env không reset password tài khoản đã seed.

Seed allowlist: demo/local→attendance_demo; testing→attendance_test/attendance_ci_test; http-smoke→attendance_http_test. Những DB khác/production/mail thật/không opt-in đều từ chối trước khi ghi. DatabaseSeeder mặc định vẫn trống.

app:diagnose --json kiểm PHP/extensions/key/debug/MySQL/schema và đường dẫn writable; production kiểm thêm demo-disabled/HTTPS/secure encrypted cookie/scanner/mail config. Exit1 nếu có checkfalse, không in secret. Checktrue của production-only trong local nghĩa điều kiện không áp dụng, không chứng minh TLS/SMTP/ClamAV đang chạy. Không sửa config cache đang tồn tại để chạy demo: launcher từ chối và yêu cầu xử lý cache đúng môi trường.
