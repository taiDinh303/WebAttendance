# 11 — Checklist cấu hình cloud

> 2026-10-26 | NOT SELECTED | Chưa tạo tài nguyên, không có URL/ref/plan/credentials AttendanceWeb.

| Hạng mục | Thông tin phải điền khi chốt | Bằng chứng cần lưu, không secret |
| --- | --- | --- |
| Provider/region/budget | Q-026, chủ tài khoản, giới hạn CPU/RAM/sleeping | Plan/region và chi phí dự kiến được duyệt |
| Domains | App/API/WS same-site; DNS/TLS/redirect URLs | URL công khai + TLS/auth browser smoke |
| PHP HTTP | Runtime/extensions, build/start/document root public | Release/image ID, readiness |
| DB | Engine/version/collation, private network/pool/TLS | Migration version + restore rehearsal |
| Worker/scheduler | Command, concurrency, retry, shutdown/restart | Queue test và scheduler last-success |
| WebSocket | Reverb hoặc provider thay thế, TLS/origin/private auth | Connect/deny/revoke/reconnect tests |
| File storage | Bucket private/region/scan/quota/lifecycle | Denied anonymous download, upload/scan/download test |
| Mail | Domain sender/DNS, sandbox trước | Captured/authorized mailbox proof, không token |
| Monitoring/backups | Alerts, retention, owner, RPO/RTO | Restore report/alert smoke |

Secrets vào secret manager/env từng environment, phân quyền DB/app/CI tối thiểu; không lưu trong docs hay `.env.example`. Tách staging/production cả DB/storage/session/queue để không broadcast hoặc gửi mail nhầm. Cloud dashboard của repo tham khảo không phải tài nguyên được cấp cho AttendanceWeb.

Sau provisioning, cập nhật bảng instance ở file này làm nguồn duy nhất;07/08 chỉ link lại. Hiện mọi ô live NOT PROVISIONED, không tự điền URL ví dụ thành live.
