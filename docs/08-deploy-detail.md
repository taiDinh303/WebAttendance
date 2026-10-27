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
