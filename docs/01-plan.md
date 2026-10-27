# 01 — Kế hoạch và backlog

> 2026-10-26 | API phase1 | Codex đã bắt đầu backend theo DEC-014. Trạng thái dưới đây phân biệt scope core đã code với phần mở rộng còn thiếu; frontend chỉ folder rỗng. Kết quả kiểm tra cụ thể tại worklog/API-CURRENT.

## Giai đoạn và cổng

| Giai đoạn | Kết quả cần có | Phụ thuộc / điều kiện qua cổng |
| --- | --- | --- |
| P0 — Đặc tả | Inventory, REQ/RULE/FLOW/API/TC, Q và tiến độ | Docs kiểm link; các Q chặn phần sắp code có câu trả lời |
| P1 — Nền tảng | Laravel/React, auth, profile, CI, policy, API/error/page | Q-009/018; DB test riêng; TC-001/002/003 |
| P2 — Lớp và lịch | Catalog, lớp, mã mời, duyệt, roster, lịch | Q-010/022/023; TC-004/005/006/014 |
| P3 — QR và bản tin | QR TTL, attendance/manual audit, report/Excel, announcement/comment | DEC-016/017; TC-007/008/009/016/023; feed/Excel chưa làm |
| P4 — Bài tập và điểm | Upload private, revision, chấm/công bố, gradebook | Q-012/013; TC-010/011/017 |
| P5 — Trắc nghiệm | Authoring, snapshot, attempt/autosave/submit, kết quả | Q-014; TC-012/013 |
| P6 — Chat và thông báo | Chat phạm vi lớp, unread, outbox, channels riêng | Q-015/016/020; TC-015/018/019 |
| P7 — Hoàn thiện và release | E2E, tải/bảo mật, docs vận hành, backup restore | Q-018/020/021; toàn bộ release checklist |

P3 và P4 có thể đổi thứ tự theo ưu tiên chủ dự án; không triển khai chat trước isolation/membership. Không ước lượng lịch ngày khi chưa chốt tải, đội ngũ và scope chi tiết.

## Backlog truy vết

| TASK | Nội dung / REQ | Phụ thuộc | Nghiệm thu | Trạng thái |
| --- | --- | --- | --- | --- |
| TASK-001 | Khảo sát và docs ban đầu | Source/ref/chat | Kiểm inventory/link | DOCS WRITTEN |
| TASK-002 | Phỏng vấn và chốt baseline theo module | Chủ dự án | DEC có nguồn; Q không bị bỏ ngỏ | Q-009/010/018-DB/020-access/022 ANSWERED; phần còn lại OPEN |
| TASK-003 | Scaffold, config, health, CI — nền tảng | DEC-011 | PHP/MySQL/test + config | IMPLEMENTED LOCAL; CI chưa chạy remote |
| TASK-004 | Account/auth/profile — REQ-001/002 | DEC-009; TASK-003 | TC-001/002/003 theo scope | IMPLEMENTED CORE; avatar/mail thật chưa có |
| TASK-005 | Catalog/lớp — REQ-003/004 | DEC-010; TASK-004 | TC-004 theo scope | IMPLEMENTED CORE; course_types chưa có |
| TASK-006 | Mã mời/duyệt/roster — REQ-005 | DEC-012/013; TASK-005 | TC-005/006 theo scope | IMPLEMENTED API; Excel/import chưa có |
| TASK-007 | Lịch/buổi học — REQ-006 | DEC-015; TASK-005 | TC-014 | IMPLEMENTED API: giờ thực/tuần/preview/conflict/sửa/hủy |
| TASK-008 | QR/attendance — REQ-007 | DEC-016/017; TASK-006/007 | TC-007/008/009/023 | IMPLEMENTED API: QR/snapshot/manual audit/finalizer; xem evidence phase2 |
| TASK-009 | Bản tin/bình luận — REQ-008 | TASK-006 | TC-016 | NOT STARTED |
| TASK-010 | File/bài tập/nộp bài — REQ-009 | DEC-020; TASK-006 | TC-010/017 | IMPLEMENTED / VERIFIED LOCAL: evidence/learning-validation.md |
| TASK-011 | Chấm/công bố/gradebook — REQ-010 | DEC-018/019; TASK-010 | TC-011 | IMPLEMENTED / VERIFIED LOCAL: snapshot công bố và average |
| TASK-012 | Quiz/attempt/scoring — REQ-013 | DEC-021, Q-028/029/030; TASK-006/011 | TC-012/013 | NOT STARTED; timing/scoring multi-attempt còn chờ trả lời |
| TASK-013 | Chat — REQ-014 | Q-015/025; TASK-006 | TC-015/019 | NOT STARTED |
| TASK-014 | Notification/jobs — REQ-015 | Q-016; TASK-006, outbox nền tảng | TC-018 | PARTIAL: inbox membership + durable outbox; chưa dispatcher/realtime |
| TASK-015 | Báo cáo/Excel — REQ-011 | TASK-008/011/012 | TC-009/020 | PARTIAL: attendance summary đã có; Excel/grade report chưa có |
| TASK-016 | Admin/tài khoản — REQ-012 | Q-021/020; TASK-004 | TC-003/019 | NOT STARTED |
| TASK-017 | Pagination/filter, responsive, accessibility — REQ-016 | Xuyên suốt từng module | TC-020/021 | PARTIAL: API collections + React core, xem FRONTEND-IMPLEMENTATION |
| TASK-018 | Migration tùy chọn | Q-017, target schema ổn định | Đối soát dry run, rollback | NOT STARTED |
| TASK-019 | Release/backup/monitor/runbook | P1–P6, Q-018 | TC-022 + checklist 18 | NOT STARTED |

## Frontend theo yêu cầu mới 2026-10-27

TASK-020 — React core theo DEC-022/023: IMPLEMENTED; layered app/auth/class/calendar/learning/grades/attendance/inbox/settings/catalog. TASK-021 — verification UI/API: build/lint/Vitest và browser fixture theo evidence/react-validation.md. Các module chưa có backend không được gọi là hoàn thành.

## Definition of done cho module

REQ và RULE đã chốt phần áp dụng; migration/index/policy có test; API contract khớp runtime; UI xử lý loading/empty/error/forbidden/conflict; pagination và ownership kiểm qua HTTP; kiểm file/realtime/job tương ứng; lưu evidence lệnh/kết quả và giới hạn vào worklog. Không gọi DONE vì đã tạo file, build pass hoặc chỉ có mock UI.

## Rủi ro và bước kế tiếp

- QR có thể bị chụp/chia sẻ trong TTL; QR không chứng minh vị trí. Không lén thêm GPS/IP. TTL/snapshot/manual correction đã chốt DEC-016/017; giữ audit và diễn giải đúng giới hạn.
- Quiz mất mạng/sát hạn và upload nặng cần test concurrency, deadline theo clock server, server ACK.
- Reverb/worker cần process sống; hosting PHP chỉ phục vụ HTTP chưa đủ.
- Scope đầy đủ có nhiều phase; mỗi release cần vertical slice dùng được, không tuyên bố hoàn thiện từ scaffold.
- Bước tiếp theo: Q-028/029/030 cho quiz, Q-015/016 cho chat/notification, Q-025/026 cho retention/cloud. Q-023/011/024 đã chốt ngày2026-10-27 và triển khai phase2, không hỏi lại. API hiện có xem API-CURRENT, không dùng bảng TARGET làm inventory runtime.
