# Kế hoạch thực hiện backend

> 2026-10-26 | IMPLEMENTED CORE / IN PROGRESS | Source PHP Laravel API trong attendance_api; xem API-CURRENT và evidence/api-validation cho phạm vi chạy/test thực.

## Thứ tự làm một vertical slice

Chốt Q của module → thêm migration/constraints/factory → model/query scoped → Policy → FormRequest/Action transaction → Resource → routes/version/errors → Feature tests → OpenAPI → React flow → E2E → worklog evidence. Không dựng toàn bộ generic CRUD trước rồi mới thêm quyền.

| Module dự kiến | Action/service chính | Guard và điều kiện data | TASK |
| --- | --- | --- | --- |
| Accounts | Register/Login/Verify/Reset/ChangePassword, CurrentActor | self/admin provisioning, rate/CSRF/revoke | 004 |
| Catalog/Classes | CreateClass/ArchiveClass/RotateInvite | teacher own, cùng scope catalog | 005 |
| Enrollment | RequestJoin/ApproveJoin/RejectJoin/Leave/Remove | owner hoặc requester, lock/version/unique | 006 |
| Schedule | CreateSession/PreviewSeries/ConfirmSeries/CancelSession | owner, timezone/range/conflict | 007 |
| Attendance | OpenWindow/RotateChallenge/CheckIn/CloseWindow/Finalize | token/membership/clock, lock window | 008 |
| Feed | PublishAnnouncement/WriteComment/ModerateComment | active membership, owner/self, sanitize | 009 |
| Assignments | PublishAssignment/StageFile/SubmitRevision | owner hoặc student self, scan/deadline/revision | 010 |
| Grades | SaveDraftGrade/PublishGrade/AmendGrade | class owner, maxScore/version/audit | 011 |
| Quizzes | PublishQuiz/StartAttempt/SaveAnswer/SubmitAttempt/Score | snapshot, actor, clock, key redaction | 012 |
| Messaging | ResolveThread/SendMessage/MarkRead | participant+active class, dedup/sequence | 013 |
| Notification | DispatchOutbox/CreateInbox/ReadInbox | recipient self, dedup | 014 |
| Reports | BuildAttendanceReport/RequestExport/ApplyRosterImport | owner/self, scoped filters, formula safety | 015 |
| Admin | LockAccount/UnlockAccount/QuerySafeAudit | Q-021; không implicit content access | 016 |

## Checklist chất lượng mỗi module

- Route/model binding không load resource ngoài scope trước khi kiểm; trả404/403 nhất quán.
- Index và constraints chống race có test DB engine thật; query pagination count đúng, không N+1.
- Business exception map mã ổn định; không bắt mọi exception rồi trả200/error text.
- Transaction không gọi mail/WebSocket external trước commit; outbox và job retry kiểm tra quyền.
- Request field allowlist/length/type; files/challenges/answer keys/PII không serialize mặc định.
- Unit clock test + HTTP Feature policy/idempotency/concurrency + API schema; ghi NOT RUN nếu chưa chạy phần tương ứng.

## Bảng hiện trạng

Laravel13.38.0/Sanctum4.3.3 đã cài và pin lock. Phase2 có11 controllers API,12 models,6 migration files và59 operations REST v1. Hai controller mới Schedule/Attendance,6 model mới ClassSession/SessionSeries/AttendanceWindow/AttendanceRoster/AttendanceRecord/AttendanceAudit. Thêm6 bảng lịch/điểm danh và bảng schedule_owner_locks để serialize writes không lock users trước class. MySQL8.4.13 local dev/test/HTTP-fixture schemas tách biệt; migrations local đã chạy, source cũ chưa import.

Auth/profile core, catalog môn/học kỳ, lớp/invite, membership transitions, inbox events, lịch tuần/QR/snapshot/manual audit/finalize/report đã có tests HTTP+DB. Bản đầu dùngusers.role một role/profile basic cùngusers theo DEC-009; không dựng generic repository. Source tổ chức FormRequest→Controller→Action/transaction→Policy/Resource. Notifications membership/audit/outbox cùng transaction; chưa có dispatcher worker/realtime. Avatar/course_types/feed/assignments/grades/quizzes/chat/Excel/admin vẫn chưa triển khai.

Mail verify/reset dùng notification queue, fake trong tests; local MAIL_MAILER=array, không gửi mail thật. Generic Idempotency-Key create/catalog chưa có, không auto-retry create. CI workflow đã viết nhưng chưa push/chạy GitHub. OpenAPI export có schema normalization; strict unknown inference chưa pass, xem API-CURRENT. Không gọi mọi TC trong kế hoạch làPASS vì chỉ scope core được chạy.

## TASK-010/011 — verified local 2026-10-27

Xem [ASSIGNMENTS-GRADES](ASSIGNMENTS-GRADES.md). Source đã có migration9bảng, private upload, revision nộp bài, chấm nháp và publication bất biến, gradebook/average phân trang. Learning full suite51tests/574assertions và HTTP36checksPASS; evidence riêng, không dựa vào phase2. Quiz theo DEC-021 vẫn NOT IMPLEMENTED.

### Kết quả cuối learning — 2026-10-27

TASK-010/011 VERIFIED LOCAL:51tests/574assertions full suite và HTTP36checksPASS; [evidence](evidence/learning-validation.md). Phần “đang kiểm thử” ở mốc ghi nhận trước được thay thế bởi kết quả này. Các giới hạn production/ClamAV/quota/purge và module chưa code vẫn giữ nguyên.
