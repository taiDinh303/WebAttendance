# 08 — Runbook release, giám sát và khôi phục

> 2026-10-26 | DRAFT / NOT RUN | Không có deployment hoặc DB target; runbook chỉ dùng sau khi chốt hosting và được giao triển khai.

## Release

1. Ghi release commit/tag, scope, migration plan và rollback compatibility. Xác nhận không secrets trong artifact và không sử dụng config cũ.
2. CI clean install từ lock, tests/lint/type/build/audit; staging DB synthetic, fake mail hoặc mailbox thử được cho phép.
3. Cấu hình env theo17; `APP_DEBUG=false`, document root `public/`, TLS/proxy trust, không public `.env`/storage private. Health/metrics có access policy.
4. Backup DB trước migration, kiểm backup đọc được, có restore rehearsal. Duyệt migration destructive riêng, không tự chạy từ tài liệu.
5. Deploy code compatible trước/sau migration theo expand-contract; chạy migrations đúng environment khi được giao. Không seed demo/admin password mặc định production.
6. Build/cache config/routes phù hợp version; restart PHP workers/queue/Reverb để cùng code và env; scheduler chỉ một active leader có lock.
7. Smoke login→teacher class→student request/approve→QR→submission/grade→quiz→chat/notification. Dùng account/dataset thử, không nội dung thật.
8. Kiểm error rate/queue lag/WS auth/latency, lưu evidence. Nếu fail gate thì rollback app compatible; không tuyên bố deployed chỉ vì build success.

## Rollback và incident

Rollback frontend/API về image/release trước khi schema backward-compatible. Migration mất dữ liệu không đảo bằng lệnh down tự động; restore DB là quyết định có nguy cơ mất ghi mới, cần backup, thời điểm, owner và kế hoạch đối soát riêng. Disable feature/write cần thiết trong incident, giữ read an toàn nếu được. Không xóa failed jobs/outbox để che backlog.

| Sự cố | Kiểm tra/ứng phó |
| --- | --- |
| Login lỗi sau deploy | TLS/domain/CSRF/stateful/CORS/session store và clock; không tắt CSRF để chữa |
| Chat không cập nhật | REST message ACK/DB, outbox pending, worker, Reverb TLS/private auth; reconnect REST để không mất tin |
| Upload treo | Size proxy vs app, disk/quota/scan worker, staged state; không đặt bucket public |
| QR báo hết hạn | Clock UTC, window/token_version/expiry, độ trễ; không tin thời gian máy học sinh |
| Queue backlog | Worker sống, lease/retry/failed_jobs; retry idempotent, không gửi notification/mail lặp |
| Rò quyền | Thu hồi session/channel, chặn action, bảo toàn audit, điều tra scope bằng traceId; không dump nội dung chat vào log |

## Monitor và backup

HTTP error rate/p95, DB connections/slow queries, queue pending age/failed count, scheduler last success, WebSocket reconnect/auth errors, storage capacity/scan failures, outbox lag, attendance/quiz finalizer delay. Log cấu trúc traceId/actor pseudonymous/action/status/duration; giảm PII theo16.

Backup DB và object manifest/file versions; mã hóa, quyền tối thiểu, test restore định kỳ. RPO/RTO/schedule/retention chưa chốt Q-018/020; chưa có cam kết khôi phục hoặc backup đã chạy. Restore rehearsal phải so số dòng, file hashes, FK và luồng đăng nhập/đọc bài trên môi trường riêng.

## TASK-024 — Công cụ và rehearsal local đã chạy

Launcher scripts/demo.ps1 phục vụ API8000 + Vite5173 foreground, có kiểm env/config cache/diagnostics; không là production process manager. mysql.ps1 start nay chờ TCP tối đa30s thay vì báo sẵn sàng ngay sau Start-Process; không tự initialize data directory đã có. app:diagnose --env=demo --json và health/ready kiểm schema admin cùng users; không dùng health để công khai cấu hình bí mật.

Đã thực thi scripts/verify-demo-restore.php: mysqldump single-transaction vào work ignored; CREATE schema attendance_restore_<timestamp>_<random> không IF NOT EXISTS để từ chối trùng, không DROP/overwrite; restore SQL vào schema mới, so counts35bảng và52FK,0orphan. Sao chép45private files và so SHA256 với metadata restore; kiểm94hash mật khẩu synthetic. Kết quả evidence/demo-restore-result.json. Mỗi lần chạy để lại schema/dump/copy local phục vụ kiểm tra; không tự dọn/xóa hoặc restore lên nguồn. Raw backup chứa hash/session và phải được bảo vệ, không commit. Đây là rehearsal SQL/file local, chưa HTTP smoke trên schema restore, backup mã hóa/retention/RPO-RTO hoặc phục hồi production.

Release gate tiếp tục: chọn hosting/budget/domain, TLS/proxy/cookies browser staging, ClamAV daemon, mail được phép, storage private/quota, worker/scheduler/outbox/monitor/restart; backup mã hóa + retention + restore HTTP smoke; production workload benchmark. Workflow API/React hiện có chạy từ lock/tests/audit; đợt thay đổi này chưa được push hoặc kiểm CI remote. Không deploy hoặc phát mail thật từ nhiệm vụ này. Production build local không tương đương deployed.

## Composer audit CI — timeout Packagist (2026-10-29)

Workflow API gọi scripts/composer-audit.sh từ backend: giữ composer audit --locked --no-interaction; retry riêng transport exit100 tối đa3lần, nghỉ5s/10s. Exit khác hoặc hết lượt vẫn fail với nguyên exitcode. Audit step giới hạn5phút, dùng COMPOSER_IPRESOLVE=4 trên runner ext-curl. Workflow cũng chạy9regressioncases, thay đổi cả2script kích hoạt workflow. Không dùng offline/ignore-unreachable/continue-on-error để đánh dấu audit thành công khi chưa lấy được advisory. Mất kết nối Packagist kéo dài vẫn chặn CI, cần kiểm mạng/runner/Packagist rồi chạy lại.

Căn cứ: [Composer CLI: audit/COMPOSER_IPRESOLVE](https://getcomposer.org/doc/03-cli.md), [Composer changelog: transport exit100](https://github.com/composer/composer/blob/main/CHANGELOG.md). COMPOSER_PROCESS_TIMEOUT áp dụng process ngoài, không chỉnh connect timeout HTTP10s trong log. Không sửa lockfile hoặc bỏ qua security findings.
