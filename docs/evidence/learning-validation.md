# Kiểm chứng learning API — 2026-10-27

PHP8.4.27, Laravel13.38.0, Sanctum4.3.3, MySQL8.4.13 portable loopback33306. PHPUnit dùng attendance_test; HTTP dùng attendance_http_test, tách dev. Không dữ liệu cũ/mail thật/cloud.

| Kiểm tra | Kết quả | Evidence |
| --- | --- | --- |
| Full PHPUnit | PASS51tests,574assertions,0fail/0error | [JUnit](phpunit-learning-full.xml) |
| HTTP cookie/CSRF/multipart | PASS36checks | [HTTP](http-learning.json) |
| OpenAPI mặc định | Export0.3 thành công,79operations | [OpenAPI](openapi-current.json), [routes](routes-current.json) |
| Composer | validate --strict PASS; audit --locked không advisory/abandoned | Summary trong worklog; raw audit local work/composer-audit-phase3.json |
| Pint | PASS toàn backend | vendor/bin/pint --format agent |
| Docs | UTF-8, links tương đối và code fences PASS | [Docs validation](learning-docs-validation.json) |

Lệnh từ attendance_api: `.tools/php/php.exe` ở parent project, `artisan test --compact --log-junit=../work/phpunit-learning-full.xml`. HTTP script local `work/http-learning-smoke.mjs` chạy node, dùng synthetic credentials trong work ignored, server8001 envhttp-smoke. Không chứa credentials trong evidence.

Feature learning bao phủ: draft/material visibility, membership revocation, content/MIME/Office traversal/VBA/file-size, scanner gate production, staged delete/attached immutable, own file và tối đa5file, idempotency cùng/khác payload, revision history/deadline/resubmit toggle, draft privacy, publication retry/amendment reason/snapshot, equal-weight average published-only (có điểm0), gradebook pagination và tenant isolation. Core/lịch/QR regression cùng full suite.

Lượt full đầu50/51PASS do test đổi APP_ENV production khiến CSRF419 chặn trước scanner. Đã sửa test gọi boundary scanner trực tiếp ở production; không tắt CSRF ứng dụng. Full cuối51/51PASS. HTTP CSRF thiếu token419 vẫn được kiểm riêng.

Giới hạn: chưa ClamAV daemon/malware corpus, load/concurrency/stress, browser UI, proxy production upload limits, purge/quota/storage-failure retry, SMTP thật, GitHub CI remote. Local validation không phải antivirus. Strict Scramble inference chưa PASS; export mặc định không bảo đảm toàn bộ dynamic schemas đủ để sinh client không review. Chưa quiz/chat/frontend/admin/export.
