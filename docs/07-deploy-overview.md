# 07 — Triển khai tổng quan

> 2026-10-26 | PROPOSED / NOT PROVISIONED | Chưa có provider, domain, plan, region hay tài khoản cloud của AttendanceWeb. Không sử dụng instance của M-ChamCong/NoteTatCa.

## Thành phần cần có

| Thành phần | Nhu cầu | Trạng thái |
| --- | --- | --- |
| Frontend static | Build React/Vite, SPA fallback, HTTPS, API base URL | Chưa có |
| Laravel HTTP | PHP/extensions đúng version, document root public/, config cache | Chưa có |
| Relational DB | MySQL đã chốt DEC-011, local8.4.13; production cần backup/private credentials/UTC | Cloud chưa provision Q-026 |
| Queue worker | Process sống và restart/retry/failed-job monitor | Chưa có |
| Scheduler | Một scheduler leader hoặc locking phân tán | Chưa có |
| WebSocket | Reverb sau TLS proxy, origin allowlist, private auth | Chưa có |
| Private storage/scan | Bài nộp/học liệu/export, quota và TTL | Chưa chọn |
| Email | Verification/reset trước; notification mail Q-016 | Chưa chọn |

## Phương án

Đề xuất ban đầu một host/container platform có PHP HTTP + worker + scheduler + Reverb, DB/storage managed khi ngân sách cho phép. Có thể host React cùng origin để đơn giản cookie/CSRF. Tách static hosting vẫn được nếu same-site domain và cookie/CORS đã kiểm thử. Shared hosting không chạy worker/WebSocket ổn định cần polling hoặc dịch vụ realtime khác; không cam kết chat tức thời khi chỉ có PHP HTTP.

Chưa chốt Docker bắt buộc. Nếu dùng container, một image có thể có nhiều process role riêng, cùng source version; không chạy tất cả trong một HTTP request. Region gần người dùng và DB, budget/tải chốt Q-026; MySQL đã chốt, không hỏi lại DB engine. Không copy kiến trúc Render/Vercel/Supabase từ hai project tham khảo như mặc định.

## Cổng staging→production

Domain/TLS/auth browser thật; isolation REST/file/job/channel; queue retry/reconnect; test scan/upload quota; DB backup restore trên fixture; load theo scope chốt; runbook rollback app/schema; retention/admin policy đã chốt. Chi tiết [08](08-deploy-detail.md), [18](18-release-checklist.md). Deployment chưa được giao trong tác vụ docs.
