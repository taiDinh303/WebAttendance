# 03 — Kiến trúc và chuẩn tổ chức code

> 2026-10-26 | CURRENT CORE + TARGET DESIGN | Laravel/MySQL core đã chạy local; React TS/Vite CONFIRMED nhưng frontend chỉ folder rỗng. Các thành phần tương lai phải xem trạng thái riêng.

**Cập nhật CURRENT sau DEC-014:** backend đã scaffold và triển khai scope đợt đầu; MySQL đã CONFIRMED theo DEC-011. Xem [API-CURRENT](API-CURRENT.md) cho packages/routes/tests thật; bảng phía dưới vẫn mô tả kiến trúc toàn sản phẩm, Reverb/storage/scanner chưa cài. `attendance_api` đã có, `atd_react` có folder rỗng; không có React app.

## Thành phần

Browser React → HTTPS REST `/api/v1` → Laravel → relational DB. Laravel ghi outbox cùng transaction, worker xử lý notification/mail/export. WebSocket Reverb/Echo truyền event qua private channel; client lấy dữ liệu chuẩn từ REST khi reconnect. Private object storage chứa file; Laravel kiểm quyền trước download. Không cần microservice ở bản đầu.

Chọn React riêng gọi JSON API theo yêu cầu; không dùng Inertia làm cầu nối server page ở kiến trúc này. Vite chỉ build frontend, không thay PHP server. PHP Laravel vẫn xử lý toàn bộ nghiệp vụ và quyền.

## Layout dự kiến

```text
AttendanceWeb/
  docs/                         # đã có
  attendance_api/               # chưa có
    app/Http/Controllers/Api/V1/
    app/Http/Requests/
    app/Http/Resources/
    app/Policies/
    app/Models/
    app/Actions/{Enrollment,Attendance,Assignments,Quizzes,Chat}/
    app/Services/                # hạ tầng/chia sẻ có nghiệp vụ rõ
    app/Jobs/ app/Events/ app/Notifications/
    database/{migrations,factories,seeders}/
    routes/{api,channels,web}.php
    tests/{Unit,Feature}/
  atd_react/               # chưa có
    src/{app,features,shared}/
    src/shared/{api,ui,auth,config,i18n}/
    tests/e2e/
```

Controller: nhận request đã validate, gọi Policy/action, trả Resource/status. FormRequest: shape/range; Policy: quyền actor với resource; Action: state transition và transaction; Model/query scope: persistence; Resource: allowlist dữ liệu, đặc biệt điểm/đáp án/private profile. Clock abstraction cho deadline test được. Dùng constructor injection cho storage/mail/clock/scoring khi cần thay implementation.

Không copy hệ thống generic repository/UoW từ .NET. Eloquent + `DB::transaction` đủ trước; chỉ thêm repository cho boundary có nhu cầu thật. Không viết base CRUD controller chứa quyền chung mơ hồ. Dùng enum/domain errors, service rõ tên, query có eager loading và pagination.

## Phiên đăng nhập đề xuất

Laravel Sanctum SPA cookie, app/API cùng site như `app.example.test` / `api.example.test` hoặc reverse proxy cùng origin. Cookie phiên HttpOnly/Secure; CSRF cookie và `X-XSRF-TOKEN`; stateful domains, credentialed CORS allowlist cụ thể. Đăng ký/login/logout có CSRF phù hợp; session regenerate sau login; revoke sessions khi khóa/reset password theo policy.

Không chọn localStorage bearer mặc định cho SPA. Nếu hosting app/API khác site phải thử browser cookie thực tế; ưu tiên same-site domain trước khi đổi auth. API token cho mobile/tích hợp bên thứ ba là scope tương lai. Đối chiếu [Sanctum chính thức](https://laravel.com/framework/docs/12.x/sanctum); kiểm lại đúng version đã pin khi scaffold.

## Stack đề xuất và tiêu chí chốt

| Thành phần | Đề xuất | Cần kiểm trước pin |
| --- | --- | --- |
| Backend | Laravel 13 / PHP tương thích (nhánh framework 13.x yêu cầu PHP ^8.3 tại lúc khảo sát) | Bản release cụ thể, extensions, hosting, package compatibility |
| DB | MySQL, cùng engine dev/test/prod | Q-018, Unicode/collation, backup và migrations |
| Frontend | React/TypeScript/Vite, Router, TanStack Query | Version/lock, license, routing và bundle |
| UI | Tailwind CSS + shadcn/ui | Accessibility và component ownership |
| Realtime | Reverb + Echo | WebSocket host/process/TLS, private channel auth |
| Queue/cache | DB queue ban đầu hoặc Redis nếu tải cần | Lease/retry/monitor/chi phí; không phụ thuộc Redis khi chưa chọn |
| Storage | Local private dev; object storage private production | Signed download hoặc proxy, scan và quota |
| API docs | OpenAPI từ contract đã triển khai | Contract drift CI và auth examples |

Nguồn kỹ thuật tra ngày 2026-10-26: [framework composer 13.x](https://github.com/laravel/framework/blob/13.x/composer.json), [frontend](https://laravel.com/framework/docs/13.x/frontend), [database](https://laravel.com/framework/docs/database/config), [Reverb](https://reverb.laravel.com/). Chưa pin patch version; không coi tài liệu online là bằng chứng package đã cài.

## Transaction/concurrency

Join approval, QR check-in, submit quiz, grade publish và gửi chat phải atomic với outbox. Unique DB constraints chống double click; version/If-Match chống mất cập nhật; lock cửa sổ/bản attempt khi xử lý deadline/đóng phiên. Chỉ retry transaction an toàn; không tự retry mutation client nếu chưa có idempotency. Event chỉ broadcast sau commit; subscription không thay quyền REST.
