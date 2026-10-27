# Bằng chứng kiểm tra API core — 2026-10-26

## Môi trường và phạm vi

PowerShell trên Windows; root `D:\My Projects\AttendanceWeb`, cwd lệnh PHP/backend là `attendance_api`. PHP8.4.27, Composer2.10.3, Laravel13.38.0/Sanctum4.3.3, MySQL8.4.13 loopback33306. Chỉ DB synthetic `attendance_dev`, `attendance_test`, `attendance_http_test`, credentials tách riêng. Chưa dùng DB/SMTP/dữ liệu cũ. Frontend `atd_react` kiểm0 item.

## Lệnh và bằng chứng

| Kiểm tra | Lệnh/nguồn | Kết quả |
| --- | --- | --- |
| Runtime | `.tools/php/php.exe -v`, Composer --version, mysqld --version | Đã chạy, versions như trên |
| Dependency install/lock | Composer create-project/require/update --lock | Laravel/Sanctum + dev Boost/Scramble/Pint/PHPUnit có lock; không JS deps |
| Composer metadata | composer validate --strict | PASS sau đồng bộ lock khi đổi metadata/scripts |
| Dependency advisories | composer audit --locked --format=json | Không advisories tại lần kiểm; không thay security review nghiệp vụ |
| PHP style | vendor/bin/pint --format=agent | PASS sau format; --dirty không áp dụng vì chưa Git init |
| Migrations | artisan migrate; migrate:status |4 files Ran trên dev; fresh schema cho tests và HTTP fixture |
| Feature/DB tests | vendor/bin/phpunit --log-junit ../work/phpunit-release.xml | Kết quả cuối ở [api-test-results.json](api-test-results.json) và [phpunit-core.xml](phpunit-core.xml) |
| HTTP thật | node work/http-smoke.mjs, server8001 envhttp-smoke |22 checksPASS; xem [http-smoke.json](http-smoke.json) |
| API routes | artisan route:list --path=api/v1 --except-vendor --json |40 operations ở [api-routes-current.json](api-routes-current.json) |
| OpenAPI | artisan scramble:export --path=../docs/evidence/openapi-current.json --no-interaction | Export default không warning, cookie/CSRF/body/page/error được chuẩn hóa |
| Docs | UTF-8 strict decode, relative-link/fence checks | Kết quả cuối ở [api-docs-validation.json](api-docs-validation.json) |
| Legacy unchanged | git status --short và HEAD trên WebAttendance | Clean, vẫn02917e3d65f8659bc09560f40591c1f75be21e57 |

Feature tests gồm auth/verification/session/profile/credentials, catalog/classes/version/FK/owner isolation, membership pending/approval/rejection/leave/remove/rejoin, notifications self/dedup/read, health alive/DB/schema failure. HTTP smoke kiểm server không ở APP_ENV=testing nên CSRF thực sự có hiệu lực; cookiejar riêng teacherA/B/student, foreign teacher404/count0, pending denied→approve allowed→leave denied, inbox và logout401. Không có trình duyệt/UI trong smoke này.

## Lỗi từng gặp và cách xử lý

- Download PHP dùng hash theo official releases JSON; Composer signature endpoint trảbytes ở PowerShell, lần xử lý .Trim trực tiếp fail, đã đọc file text và verify SHA384 trước chạy installer. Không bỏ qua checksum.
- Lượt26 Feature tests đầu24PASS/2FAIL: logout còn cached Sanctum user và user version cũ sau đổi password trong nhiều request cùng app. Sửa forget guard khi logout/revoke và refresh current account/version; lượt lại26PASS212assertions.
- Khi thêm health regression, tách StartSession nhưng middleware CSRF vẫn thêm cookie nên lỗi Session store not set. Sửa health stateless bằng withoutMiddleware('web'); ba health testsPASS11assertions. Schema mock thứ tự ban đầu cũng được sửa, không nới assertion.
- OpenAPI lần đầu4 warnings từ user context null và resource model inference; thêm nullsafe rule scope + mixin, export default không warning. Sau đó phát hiện page items bị suy luậnstring và errors không đúng envelope; đã chuẩn hóa document transform và operation-specific create/PATCH bodies, cookie+CSRF scheme.
- Strict OpenAPI `--fail-on-unknown` còn88 dynamic inference findings ở source trước document transform. Chưa coi strict generationPASS; bản export dùng được để tham khảo, chưa là hợp đồng typed hoàn toàn đủ sinh client production tự động.

## Giới hạn còn lại

Chưa chạy GitHub CI thật, installproduction --no-dev, static analyzer, browser/device UI, SMTP thật, WS/Reverb, crash/retry outbox dispatcher, true concurrent race/load, backup restore, cloud deploy hoặc migration dữ liệu cũ. Chưa code calendar/QR/announcements/learning/quiz/chat/files/exports/admin. Không đánh mọi TC001–022 trong kế hoạch là hoàn thành. .tools/work/.env* không được đưa vào artifact/Git; final evidence chỉ JUnitPASS/summary/inventory không credentials.
