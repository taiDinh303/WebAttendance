# 06 — Kế hoạch kiểm thử và nghiệm thu

> 2026-10-26 | TEST PLAN + CORE EVIDENCE | Đã có Feature tests và HTTP smoke cho core; bảng TC toàn sản phẩm vẫn là kế hoạch. Chỉ phạm vi nêu trong evidence/api-validation được tính đã chạy.

## Phạm vi kiểm chứng hiện tại

TC-001/002: register/login/logout/verification/reset/change/session và CSRF/CORS đã test local, không SMTP/browser thật. TC-003/004/005/006: self/teacher isolation, catalog/lớp/duyệt/rejoin/leave/remove/version đã test qua HTTP+MySQL; chưa concurrency stress/Excel/course_types/admin. TC-018 chỉ inbox membership/dedup/state/recipient; chưa crash-dispatch/Reverb. TC-020 chỉ pagination/filter/sort hiện có, chưa exports/large dataset. Health/migrations local đã kiểm; TC-022 release/backup/restore production chưa chạy. TC-007–017 liên quan QR/learning/chat vàTC-021 frontend chưa thực hiện. Kết quả cuối/giới hạn xem [api-validation](evidence/api-validation.md).

Phase2 bổ sung kiểm chứng TC-007/008/009/014/023 cho lịch+QR+manual: timezone/DST, parent/owner/self, epoch30s/TTL60s và ngưỡng muộn/đóng, snapshot/removed/newly approved, version/audit, precision giây khi replay, cancelled exclusion và backlog>100 windows. Xem [evidence phase2](evidence/schedule-attendance-validation.md) cho kết quả mới; câu NOT RUN ở snapshot phase1 trên không áp dụng các flow phase2 đã kiểm. Chưa camera/browser thật hoặc stress concurrency.

## Fixture và môi trường

DB test riêng cùng engine dự kiến production; không dùng connection string cũ. Teacher A/B; lớp A/B; HS S1 active A, S2 active B, S3 pending A, S4 removed A; admin và account locked. Seed synthetic không có PII thật; fake mail, private test disk, fake clock và worker test. Mỗi suite namespace/dataset riêng tránh test song song phá nhau.

## Test cases bắt buộc

| TC | REQ/RULE | Thao tác và kết quả mong đợi |
| --- | --- | --- |
| TC-001 | REQ-001 | Đăng ký/login/logout/reset; không tự cấp admin; verification/reset hết hạn/đã dùng bị từ chối; logout phiên cũ không gọi được API |
| TC-002 | REQ-001 | CSRF thiếu/sai, origin lạ, cookie và CORS; session fixation, lock/reset revocation, nhiều tab/reload; response không lộ credential |
| TC-003 | RULE-001 | Teacher B sửa lớp A, HS sửa profile người khác, mass assign owner/role; đều bị chặn và không thay DB; admin scope theo Q-021 |
| TC-004 | REQ-003/004 | CRUD catalog/lớp own, lọc học kỳ, archive; FK catalog khác owner bị chặn; xóa catalog đang dùng conflict |
| TC-005 | REQ-005 | Mã đúng→pending, owner nhận một notification; pending không xem roster/bản tin/file/điểm; approve→active và HS nhận notice |
| TC-006 | RULE-002/003 | Mã rotated/disabled/expired; double request; concurrent approve/reject; unique membership, status đúng và audit/outbox không lặp |
| TC-007 | REQ-007 | QR hợp lệ đúng lớp/window + active→một record; direct POST thiếu token, token sai/expired/rotated, pending/removed, schedule khác lớp đều bị chặn |
| TC-008 | RULE-004/005 | Clock boundary opens/late/close, close/check-in race, hai request song song, đổi giờ máy, worker chậm, teacher đóng tab; trạng thái server/unique đúng |
| TC-009 | REQ-011 | Finalize absent idempotent theo roster đã chốt; cancelled/unmarked không sai denominator; report/Excel khớp records và self filter |
| TC-010 | REQ-009 | Upload sạch→submit ACK→revision; late/resubmit đúng config; retry cùng key không revision lặp; file revision cũ giữ nguyên |
| TC-011 | REQ-010 | Score ngoài range, grade version cũ; draft score không xuất hiện trong mọi HS API/export; publish chỉ HS đó đọc được; đổi điểm có audit |
| TC-012 | REQ-013 | Quiz JSON không lộ key; option khác question bị chặn; start lặp trả attempt cũ; chấm đúng snapshot và chính sách multiple-choice |
| TC-013 | RULE-007 | Autosave race/out-of-order, submit vs timeout, refresh/reconnect, hết lượt, hết hạn dù worker chậm; chốt một kết quả và deadline không đổi |
| TC-014 | REQ-006 | Lịch self/owner, range bounded, timezone/qua ngày, recurrence preview/dedup, conflict, cancel/đổi buổi đã có attendance không phá lịch sử |
| TC-015 | REQ-014 | Thread đúng participant; hai lớp cùng HS không lẫn; cursor không missing/duplicate; resend clientMessageId chỉ một message; read marker monotonic |
| TC-016 | REQ-008 | Announcement/comment scoped, XSS payload không chạy, comment self/moderation đúng; người ngoài không đọc/count; deleted link an toàn |
| TC-017 | RULE-010 | MIME giả/double extension/path traversal/quá size/quota/scan pending/fileId người khác bị chặn; private URL/export không vượt quyền; staged cleanup an toàn |
| TC-018 | REQ-015 | Crash sau commit/trước broadcast, retry job, offline→reconnect; notification không mất/nhân đôi; mark-read self; không rò PII qua payload |
| TC-019 | RULE-001/008 | Subscription channel người khác bị403; lock/remove trong khi socket đang mở ngừng dữ liệu mới; job/download/presigned URL policy không bỏ qua thu hồi |
| TC-020 | REQ-016 | Mọi list pageSize1/100/101, page0, q/sort/filter invalid, stable tie-breaker, total đúng scope, last/empty page; export đúng filter và formula escape |
| TC-021 | REQ-016 | UI 360/768/1440px, keyboard/focus/dialog, contrast, không tràn, loading/empty/401/403/409/422/429/503; giữ draft khi lỗi, không báo submit thành công giả |
| TC-022 | Release | Clean install, migrations fresh/upgrade trên DB synthetic, worker/Reverb restart, backup restore, readiness, rollback app/schema tương thích; không test trực tiếp DB thật |
| TC-023 | DEC-017/REQ-007 | Chỉ teacher owner sửa status của roster, reason/version bắt buộc; create manual không bịa giờ quét; giữ mốc QR thật; audit before/after actor/time; replay/finalizer không ghi đè manual; stale/cross-owner/student bị chặn |

## Lớp kiểm tra

Unit: state transitions, scoring, timezone/deadline, formula, rate/quota rules. Feature HTTP + DB: policy, transaction, unique/idempotency, resource redaction, pagination. Integration: queue/outbox/private storage/WebSocket auth/reconnect. E2E: browser teacher tạo lớp→HS request→duyệt→QR→bài nộp→chấm/công bố→quiz→chat/notification. Mock frontend chỉ kiểm UI, không thay bằng chứng luồng thật.

## Hiệu năng và evidence

Chốt tải ở Q-018 trước SLA. Đề xuất dataset thử 2 teacher/2 lớp cho isolation, và dataset lớn phân trang 1.000+ học sinh/lớp, 10.000 messages/thread; concurrency QR/quiz đo với tải mục tiêu được đồng ý, không tuyên bố các số này đã là capacity. Kiểm query count/N+1, memory export, queue lag, p95 latency và reconnect storm. Không dùng số đo máy dev để cam kết production.

Mỗi lần chạy ghi ngày, commit/diff, shell/cwd, command, môi trường/fixture, số ca pass/fail, log/screenshot path, giới hạn. FAIL phải có mô tả nguyên nhân biết/chưa biết và task tiếp; không sửa assertion để hợp thức hóa lỗi. Build/lint chỉ là một gate; không gọi ownership/realtime PASS khi chưa chạy test tương ứng.

## Learning verification đang thu thập — 2026-10-27

[Contract learning](ASSIGNMENTS-GRADES.md), Feature AssignmentsGradesTest kiểm file private/draft publish/membership revocation, upload content/scanner gate, immutable revision/deadline/retry, grade snapshots/average và teacher/student isolation. HTTP smoke đã PASS36checks với cookie/CSRF/multipart trên attendance_http_test; [kết quả](evidence/http-learning.json). Full suite và test Office/size vừa bổ sung đang chạy; chưa đánh toàn module VERIFIED. Không coi local_validation là antivirus.

### Learning kết quả cuối

Full suite51tests/574assertions và HTTP36checksPASS, [evidence](evidence/learning-validation.md). Đây là kết quả cuối thay phần đang chạy phía trên; các kiểm thử load/production/ClamAV thật chưa thực hiện.
