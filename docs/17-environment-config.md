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

## Brevo HTTPS email — 2026-11-05

IMPLEMENTED local: Laravel custom transport `brevo` gửi qua HTTPS API, không cần SMTP/package mới. Biến cấu hình trên service AttendanceWeb (khác tên .NET của NoteTatCa):

```dotenv
MAIL_MAILER=brevo
BREVO_API_KEY=
MAIL_FROM_ADDRESS=verified-sender@example.test
MAIL_FROM_NAME=AttendanceWeb
```

`BREVO_API_KEY` là secret nhập trực tiếp trên Render; chủ dự án chọn dùng chung giá trị `EMAIL__BREVO__APIKEY` của NoteTatCa. Không đưa key vào docs/Git hoặc frontend. Sender phải được Brevo xác minh. Local/test giữ `MAIL_MAILER=array`. Transport timeout30s/connect10s, không redirect/retry HTTP tự động, yêu cầu201+messageId; lỗi không chứa key/body provider. Chưa hỗ trợ attachments; từ chối rõ để không bỏ mất file.

Render `attendanceweb-api`: sender/name đã Save only; chủ dự án đã dán và lưu key, UI xác nhận biến có mặt và được che. Chưa chuyển mailer hoặc deploy code từ tác vụ cấu hình. Chỉ bật `MAIL_MAILER=brevo` khi triển khai code hỗ trợ; config cache/worker phải nhận cùng env. VerifyAccountEmail và ResetAccountPassword dùng queue: nếu `QUEUE_CONNECTION=database` cần queue worker; web entrypoint hiện chỉ chạy scheduler, không chạy queue worker. Không coi Save only là gửi mail thành công. Chưa thử email thật/kiểm hộp thư hoặc xác minh key production.

Tham khảo: [Brevo send API](https://developers.brevo.com/reference/send-transac-email), [Laravel custom transports](https://laravel.com/docs/13.x/mail#custom-transports), [Render environment](https://render.com/docs/configure-environment-variables).

2026-11-05 rollout: chủ dự án đã giao publication/deploy/gửi mẫu. Render chọn MAIL_MAILER=brevo và QUEUE_CONNECTION=sync để2notification account xử lý trực tiếp sau commit thay vì đợi worker chưa có; chưa cấu hình retry worker hoặc promise queue durability. PortfolioDemo maintenance cần chạy trong scope mail array và khôi phục default bằng finally để giữ dữ liệu demo giả mà không chặn real mail. Những ghi chú chưa deploy phía trên là lịch sử tại thời điểm kiểm tra; outcome cuối xem worklog.

VERIFIED rollout21:12 ngày2026-11-05: Render LIVE commit2c3b2c4, mailer brevo/queue sync, health200; demo isolation regressionPASS. Email mẫu gửi bằng Laravel transport local với shared key được Brevo ghi Delivered; registration/recovery trên Render chưa thử với người dùng thật. Không coi trạng thái Delivered là bằng chứng vị trí Inbox. Chi tiết/CI audit limits ở worklog.

## Google sign-in — 2026-11-05

Frontend Vercel Production `VITE_GOOGLE_CLIENT_ID` và Render `GOOGLE_CLIENT_ID` dùng cùng OAuth Web Client ID do chủ dự án cung cấp. Đây là public identifier; không dùng Client Secret, không đặt secret/token vào VITE_* hoặc browser storage. Backend thiếu ID fail503; frontend thiếuID không khởi chạy GIS. Local ignored.env/.env.local được thêm riêng key này, giữ Brevo/DB/config cũ.

Google Cloud của AttendanceWeb: Authorized JavaScript origins gồm https://attendanceweb-psi.vercel.app; local thêm đúng origin máy đang chạy (mặc định http://localhost:5173, nếu dùng127.0.0.1 phải thêm origin127.0.0.1 tương ứng). Popup/ID-token flow không cần callback redirect URI. Nếu Audience=Testing, thêm tài khoản thử vào Test users theo yêu cầu Google. Cần xác nhận đăng nhập Google thật bằng tài khoản do chủ dự án điều khiển; fixture dùng synthetic signed tokens/GIS, không đồng nghĩa real consent/OAuth đã chạy.

Migration mới2026_11_05_144206 thêm google_subject nullable unique và cho password nullable cho Google-only users; account password cũ giữ nguyên. Deploy backend/migration trước khi dựa vào Google fields. Không chạy migrate down khi có Google-only users vì xóa binding có thể khóa đường đăng nhập. GIS script tải từ accounts.google.com; API không nhận raw Google subject/email từ client và không lưu ID token. Requestchallenge CSRF/cookie → nonce5phút → POSTcredential → signature+claims → subject/account/role → cookie session. Existing email cần explicit link trong phiên đã đăng nhập. One nonce active mỗi session, tab cũ có thể phải retry.
