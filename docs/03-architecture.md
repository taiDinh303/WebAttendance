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

## Rà soát OOP/SOLID/Clean Code — TASK-022, 2026-10-28

Phạm vi: source Laravel/React hiện hành, không bao gồm code PHP legacy trong lịch sử Git. Đã có OOP (Action/Policy/Resource/Model), phân trách nhiệm validation–authorization–serialization và các pattern Action, Policy, Adapter (API gateway), Transactional Outbox. SOLID là tiêu chí thiết kế; không cần thêm inheritance, interface hoặc generic repository cho mọi class.

Điểm cần cải thiện qua khảo sát: `ManageSchedule` gộp recurrence/timezone với transaction; `SubmissionFlow` import concrete HTTP gateway dù đã có interface; hai lần `send` đồng thời có thể upload và tạo key riêng. Controller báo cáo vẫn chứa query dài; nhiều action còn dùng array và tuple thay DTO. Outbox hiện chỉ ghi durable event, chưa có dispatcher/realtime được kiểm chứng.

Mục tiêu đánh giá nội bộ khoảng 8/10 cho phần đã triển khai: trách nhiệm rõ, dependency thay thế được ở boundary cần test, giữ policy/transaction và có bằng chứng kiểm thử. Đây là nhận định kỹ thuật có phạm vi, không phải chứng nhận toàn sản phẩm hoặc đo coverage 80%. Kết quả thực thi và giới hạn xem [06-testing](06-testing.md) và [worklog](worklog.md).

IMPLEMENTED/VERIFIED LOCAL: `WeeklyOccurrences` không phụ thuộc Eloquent/Gate/container, nhận timezone và definition đã validate; `ManageSchedule` inject class này và giữ quyền/transaction/locking. `SubmissionFlow` chỉ nhận `SubmissionGateway`, UI cấp `learningApi`; ESLint chặn import infrastructure/React vào flow này. Các lệnh send đồng thời dùng chung Promise, chụp riêng mảng file đầu vào, không đổi payload/key khi ACK chưa chắc chắn. QR dùng constants30/60 và một snapshot server time trong mỗi lần issue.

| Tiêu chí | Đánh giá nội bộ sau thay đổi | Căn cứ / khoảng cách còn lại |
| --- | --- | --- |
| OOP | 8/10 | Class đóng gói state gửi bài; Action/Policy/Resource và recurrence có trách nhiệm rõ; còn nhiều array/tuple |
| SOLID | 7.5/10 | SRP và dependency injection được cải thiện; gateway interface nhỏ, có fake test; một số Action/controller vẫn nhiều trách nhiệm |
| Design Patterns | 8/10 | Action, Policy, Adapter, Transactional Outbox dùng đúng nhu cầu; chưa có outbox dispatcher kiểm chứng |
| Clean Code | 8/10 | Constants/typed contract/tên tham số rõ, recurrence không lặp phép normalize; còn query báo cáo dài và notification insert lặp |
| Khả năng kiểm thử | 8/10 | Pure unit + HTTP/MySQL regression + frontend gateway tests; chưa đo coverage hoặc stress concurrency nhiều process |

Tổng quan xấp xỉ8/10 theo các tiêu chí trên; phạm vi khảo sát source hiện hành và đọc sâu các flow lịch/QR/bài nộp, không tuyên bố audit từng dòng toàn repository. Không thêm abstraction hoặc pattern chỉ để tăng số lượng class.
