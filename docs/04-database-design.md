# 04 — Thiết kế dữ liệu

> 2026-10-26 | LOGICAL TARGET + CURRENT MAPPING | DB/migrations core đã tạo, danh mục toàn sản phẩm phía dưới còn TARGET. Naming snake_case DB, camelCase JSON; ULID cho entity nghiệp vụ, không dùng mã HS/mã lớp làm identity.

**Cập nhật CURRENT đợt API đầu:** đã có migrations users/auth/session/cache/jobs, semesters, courses, classes, class_invites, class_memberships, membership_events, inbox_notifications và outbox_events. MySQL8.4 local đã chạy, dữ liệu synthetic dev/test riêng. DEC-009 chọn một role nên dùng `users.role`, chưa tạo user_roles; profile cơ bản (display_name/phone/locale/timezone/version) nằm cùng users, chưa có bảng profiles. Course_types và toàn bộ bảng lịch/attendance/bài tập/điểm/quiz/chat/file vẫn TARGET chưa tạo. SQL cũ chưa import. Migrations là nguồn CURRENT, bảng dưới là logical target cần cập nhật trước từng module.

**Phase2 ngày2026-10-27:** thêm session_series, class_sessions, attendance_windows, attendance_rosters, attendance_records, attendance_audits và bảng mutex kỹ thuật schedule_owner_locks. Hai migration mới được thêm sau migration core, không sửa lịch sử migrations đã chạy. Không tạo qr_challenges: QR dùng HMAC/epoch30s/expiry60s và token_version cửa sổ. Audit actor nullable cho auto_absent; manual correction có actor/reason/before/after; không dùng bảng audit_events tổng quát chưa tạo. Chi tiết [SCHEDULE-ATTENDANCE](SCHEDULE-ATTENDANCE.md). Các bảng bài tập/điểm/quiz/chat/file vẫn chưa có.

## Dữ liệu hiện có và chưa có

Hiện có: snapshot 16 bảng trong SQL cũ và asset gốc, inventory/hash ở evidence. Chưa có bản DB live được xác nhận, thống kê số dòng tin cậy, mapping tài khoản, dữ liệu Assignment/Quiz/Chat/Notifications của bản mới. Không dùng dữ liệu thật trong SQL cũ để seed mới; tài khoản demo chỉ synthetic khi được triển khai. Mọi bảng sau là TARGET, không phải dữ liệu đã tồn tại.

## Quy ước

Mỗi bảng nghiệp vụ có `id`, `created_at`, `updated_at` UTC; record mutable có `version` số nguyên tăng khi update. FK indexed; archive/deleted_at dùng theo retention cụ thể, không soft-delete mọi bảng tùy tiện. Tiền/điểm dùng DECIMAL, không float. `status` enum string ở API, DB check/enum theo engine. `owner_id` immutable qua payload. PII nullable theo mục đích, chưa ép birthday/gender như bản cũ. Timezone gắn lớp; không suy ra timezone từ máy host.

## Entity và trường chính

| Bảng | Trường nghiệp vụ chính ngoài quy ước | Quan hệ/ràng buộc |
| --- | --- | --- |
| users | email, email_verified_at, password_hash, display_name, account_status, last_login_at | Unique normalized_email; email collation chốt trước migration |
| user_roles | user_id, role | Unique user–role; tự đăng ký không cấp admin; đa role Q-009 |
| profiles | user_id, student_code?, phone?, avatar_file_id?, locale, timezone | Unique user; mã HS là metadata, không PK |
| semesters | owner_id, name, start_date, end_date, is_active | owner teacher; start≤end; scope Q-010 |
| course_types | owner_id, name, credits?, theory_periods?, practice_periods? | Tín chỉ/tiết không âm; dùng khi cần giữ catalog cũ |
| courses | owner_id, course_type_id?, code?, name | Unique owner–code khi có code; FK type cùng owner |
| classes | owner_id, course_id?, semester_id?, name, description?, timezone, status, archived_at? | owner teacher; catalog cùng scope; status active/archived |
| class_invites | class_id, code_hash, enabled, expires_at?, rotated_at | Unique hash; code CSPRNG, không lưu plaintext; giới hạn active code theo service |
| class_memberships | class_id, student_id, status, requested_at, reviewed_at?, reviewed_by?, review_note?, joined_at?, ended_at? | Unique class–student; status pending/active/rejected/left/removed |
| membership_events | membership_id, actor_id, from_status?, to_status, reason?, occurred_at | Append-only audit, không lấy trạng thái từ log thay row chính |
| schedule_series | class_id, timezone, recurrence_rule, starts_on, ends_on | Lặp tuần bounded; chỉ tạo sau Q-023 |
| class_sessions | class_id, series_id?, title?, starts_at, ends_at, room?, status | starts<ends; scheduled/cancelled/completed; không gộp giờ QR |
| attendance_windows | session_id, opened_by, opens_at, late_after?, closes_at, state, token_version | closed/open/expired; unique phiên open/buổi bằng transaction; mọi timestamp hợp lệ |
| qr_challenges | window_id, nonce_hash, valid_from, expires_at, token_version | Challenge dùng chung nhiều HS trong TTL, mỗi HS chỉ ghi một attendance |
| attendance_records | session_id, student_id, window_id?, status, checked_in_at?, recorded_source | Unique session–student; status present/late/absent; source qr/close_finalize |
| announcements | class_id, author_id, title, content, state, published_at? | draft/published/archived; sanitize content |
| announcement_comments | announcement_id, author_id, content, deleted_at? | Scope quan hệ announcement→class; version khi sửa |
| assignments | class_id, author_id, title, instructions, due_at?, max_score, allow_late, allow_resubmit, state | draft/published/closed/archived; max_score>0 |
| submissions | assignment_id, student_id, latest_revision_id?, status | Unique assignment–student; row tổng hợp không thay file revision |
| submission_revisions | submission_id, revision_no, submitted_at, is_late, note? | Unique submission–revision_no; immutable sau submit |
| files | uploaded_by, class_id?, storage_key, original_name, mime_type, size_bytes, sha256, scan_state, state | Storage key random unique; staged/attached/deleted; scan pending/clean/rejected |
| assignment_files | assignment_id, file_id | Unique link; owner/scope hợp lệ |
| submission_files | revision_id, file_id | Unique link; file chỉ attach cho revision actor hợp lệ |
| grades | submission_revision_id, graded_by, score, feedback?, state, published_at? | Unique revision; draft/published; score≤assignment max_score |
| grade_events | grade_id, actor_id, old_score?, new_score?, action, reason?, occurred_at | Append-only; thay điểm đã công bố có audit/version |
| quizzes | class_id, title, instructions?, opens_at?, closes_at?, duration_seconds?, max_attempts, state, published_version_id? | draft/published/closed; cấu hình Q-014 |
| quiz_versions | quiz_id, version_no, total_score, content_snapshot | Immutable snapshot sau publish; unique quiz–version |
| quiz_questions | quiz_version_id, question_key, kind, prompt, points, position | single_choice/multiple_choice đề xuất; answer key server-only |
| quiz_options | question_id, option_key, content, is_correct, position | Không trả is_correct trên student attempt Resource |
| quiz_attempts | quiz_id, version_id, student_id, attempt_no, started_at, expires_at, submitted_at?, state, score? | Unique quiz–student–attempt_no; in_progress/submitted/expired/graded; active attempt bảo vệ lock |
| quiz_answers | attempt_id, question_id, selected_option_keys, saved_at | Unique attempt–question; chỉ option thuộc question/version |
| conversations | class_id, teacher_id, student_id, state, last_message_at? | V1 private class–teacher–student unique, chờ Q-015 |
| messages | conversation_id, sender_id, client_message_id, body, sent_at, sequence_no | Unique sender–client_message_id; index conversation–sequence; text only đề xuất |
| conversation_reads | conversation_id, user_id, last_read_sequence | Unique conversation–user; read marker chỉ tăng |
| notifications | recipient_id, event_key, type, resource_type, resource_id, minimal_payload, read_at? | Unique recipient–event_key; không chứa đề/đáp án/file token |
| outbox_events | event_key, type, aggregate_id, payload, occurred_at, dispatched_at?, attempts, next_retry_at? | Unique event_key; retry bền vững, payload tránh PII dư thừa |
| exports | requested_by, class_id, kind, filter_snapshot, state, file_id?, expires_at? | pending/running/ready/failed/expired; quyền recheck download |
| import_batches | requested_by, class_id, state, file_id, totals, error_report_file_id? | preview→confirmed→applied; dedup từng dòng; không tự reset password |
| audit_events | actor_id?, action, target_type, target_id, trace_id, occurred_at, safe_diff | Append-only; không log secrets/chat/file body |
| idempotency_records | actor_id, operation, key_hash, request_hash, resource_id?, response_status, expires_at | Unique actor–operation–key_hash; kiểm quyền trước replay |

Session/password_reset_tokens/jobs/failed_jobs là hạ tầng Laravel; migrations theo version và driver đã chọn. Schema auth cụ thể chốt sau Q-009. Không tự tạo bảng school/tenant vì mô hình xác nhận là teacher-owned classes.

## Index và integrity trọng yếu

- classes(owner_id,status,updated_at,id); semesters/courses(owner_id,name,id).
- memberships(student_id,status,class_id), (class_id,status,requested_at,id); mọi page total đã filter actor.
- sessions(class_id,starts_at,id), attendance(session_id,status,student_id); unique session/student là chống race cuối cùng.
- assignments(class_id,state,due_at,id), submissions(assignment_id,student_id), grades(state,published_at,id).
- attempts(student_id,quiz_id,state); messages(conversation_id,sequence_no), notifications(recipient_id,read_at,created_at,id), outbox(dispatched_at,next_retry_at,id).
- Quan hệ cùng scope cần service/Policy và nếu engine phù hợp composite FK; FK đơn lẻ không tự chứng minh assignment/class/student cùng lớp.
- Không dùng cascade xóa user/class để mất bài/điểm/chat. Archive trước; purge theo Q-020 và runbook. Delete draft trống có thể riêng sau quyết định.

## Aggregate attendance và điểm

Record buổi là nguồn chuẩn. Đề xuất report: P=present, L=late, A=absent của các buổi đã chốt không cancelled; denominator=P+L+A; tỉ lệ tham dự=(P+L)/denominator, denominator0 trả null. Chưa có excused; không coi unmarked là absent trước finalize. Chốt Q-011 trước code.

Điểm bài nộp lấy revision được chấm và công bố; quiz lấy result đã cho phép xem. DEC-018/019: thang10 cố định (max_score nếu còn cột phải cố định10), average=sum các published scores/count với trọng số bằng nhau; chưa chấm/chưa nộp không tự0, không có điểm thì null. Đây là TARGET module learning chưa migrate/code; không dùng raw quiz points làm điểm vượt10. Gradebook không trả điểm người khác cho HS và không lộ draft score qua aggregate. Mọi summary materialized phải rebuild được, không tạo nguồn chuẩn thứ hai.

## Dữ liệu khởi tạo dự kiến

Role teacher/student/admin qua seed idempotent; admin chỉ qua quy trình provisioning an toàn. Demo có teacher A/B, student S1/S2, pending/removed membership và hai lớp để test isolation. Không seed user thật từ dump. Danh mục học kỳ/môn không tự tạo global đến khi Q-010 chốt. Tất cả seed/migration hiện NOT RUN.

## Learning schema hiện có trong source — 2026-10-27

Migration 2026_10_26_175034 thêm9bảng: assignments (lớp/author/title/instructions/due/policies/state/version), stored_files (owner/class/assignment/purpose/private key/MIME/size/hash/scan state), assignment_files (liên kết tài liệu), submissions (unique assignment/student, latest revision và current publication), submission_revisions (revisionNo/key/hash/note/submittedAt/late), submission_files, grades (nháp theo revision, score decimal4,2/version), grade_publications (snapshot bất biến, unique grade/version), grade_events (actor, before/after publication, reason). MaxScore10 là invariant API, không nhận từ client. Chi tiết [ASSIGNMENTS-GRADES](ASSIGNMENTS-GRADES.md). Chưa có bảng quiz/attempt.
