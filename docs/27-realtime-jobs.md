# 27 — Realtime, queue và tác vụ nền

> 2026-10-26 | PROPOSED | Chưa có worker/WebSocket chạy. Chat scope và kênh email cần Q-015/016.

Phase2/2026-10-27 đã implement `attendance:finalize` + scheduler everyMinute/withoutOverlapping và fallback finalize khi đọc window/roster/history/summary. Job này ghi absent/audit idempotent theo snapshot, không cần WebSocket. Outbox dispatcher, attendance realtime events và chat vẫn chưa triển khai; đăng ký schedule không đồng nghĩa scheduler process đang chạy.

## Event contract

Event envelope `{eventId,eventType,aggregateId,version,occurredAt,traceId,data}`. Không có password/token/answer key/full grade trong event chung. Data tối thiểu ID/state để REST refetch; chat body chỉ truyền qua participant-specific delivery đã kiểm quyền. DB lưu message/notification trước broadcast.

| Event | Người nhận / private channel đề xuất | Hành động client |
| --- | --- | --- |
| enrollment.requested | `users.{ownerId}` | Invalidate requests/unread |
| enrollment.approved/rejected/removed | `users.{studentId}` | Refetch membership, invalidate class, revoke access khi remove |
| assignment.published / quiz.published | User inbox của active members | Refetch class feed/list |
| submission.created | `users.{ownerId}` | Refetch submissions cần chấm |
| grade.published / quiz.result-published | `users.{studentId}` | Refetch self grade/result, không broadcast điểm cho cả lớp |
| message.created | `users.{participantId}` hoặc `conversations.{id}` với revoke bảo đảm | Dedup messageId/sequence rồi sync REST |
| notification.created/read | `users.{recipientId}` | Refetch inbox/count; đồng bộ nhiều tab |
| attendance.recorded/window.closed | `users.{ownerId}` | Refetch bảng/QR window, không public roster |

User channel chỉ user đó auth; thread channel cần membership active và đúng participant. Nếu hạ tầng chưa đảm bảo revoke subscription đang mở, dùng user channels với server fan-out kiểm quyền từng recipient trước publish; không broadcast dữ liệu nhạy cảm vào group channel cũ rồi hy vọng client tự bỏ qua. Lock user revoke session/socket; HS remove khỏi lớp vẫn có user channel nhưng không nhận event lớp nữa. Link sâu vẫn REST auth.

## Outbox và delivery

Transaction nghiệp vụ ghi entity+outbox(eventKey unique). Worker claim row theo lease/lock, gửi notification unique recipient+eventKey, broadcast sau commit, retry exponential/backoff có giới hạn và failed-job monitoring. Crash trước commit không có event; sau commit chưa dispatch sẽ retry; crash sau dispatch/trước ACK có thể gửi lại nên consumer dedup. Không tuyên bố exactly-once qua WebSocket. Broadcast mất không mất DB data; reconnect lấy từ cursor/high-watermark.

Không đưa nội dung chat/bài nộp đầy đủ vào outbox nếu chỉ cần ID; worker re-read quyền/record. Notification cho người vừa rời lớp theo quyền hiện tại, payload không lộ nội dung lớp; không gửi tiếp nếu account locked. Unread state DB, read marker monotonic; client tab khác cập nhật qua event/refetch.

## Job catalog

| Job | Trigger / idempotency | Kiểm thất bại |
| --- | --- | --- |
| DispatchOutbox | Scheduler/continuous worker; eventKey + lease | Queue lag/oldest pending/failed count |
| DeliverInAppNotification | Outbox; recipient/event unique | Retry không notification trùng |
| SendAccountMail | Verification/reset challenge; expiry check | Captured mail test; retry/resend rate, không log proof |
| FinalizeAttendanceWindow | closesAt; unique session/student | API chặn quá hạn dù job chậm, absent roster đã chốt |
| ExpireQuizAttempt | expiresAt; lock attempt/state | Submit/timeout race không double score |
| ScanUploadedFile | Staged file; hash/scan state | Reject quarantine, không submit pending |
| CleanupStagedFiles | TTL chưa chốt; lock/recheck attached | Không xóa revision file; retry safe |
| GenerateExport | ExportId + filter snapshot | Recheck quyền; private file và expiry |
| ApplyRosterImport | Batch confirmed + row key | Kết quả từng dòng, retry không thêm membership lặp |

Reminder deadline/email, online presence và typing indicators chưa xác nhận; không cần làm trước message delivery đúng. Không offline check-in/quiz submit; network recovery không được tự lùi server deadline.

## Client reconnect

Backoff/jitter; auth lại khi reconnect, lấy unread+changed membership, messages afterSequence và query invalidate. Duplicate/out-of-order events không rollback version mới; gap sequence thì fetch. Không phụ thuộc socket để hoàn tất POST; UI lấy ACK REST làm trạng thái đã gửi/đã nộp. Lưu đúng cursor cho từng actor/thread, clear khi logout.

## Learning CURRENT — phase3

Publish assignment, submit revision và publish grade tạo inbox/outbox trong cùng transaction. Chưa có dispatcher/realtime/email cho các sự kiện này. Scan upload hiện đồng bộ trước khi ghi private file: local validation hoặc ClamAV INSTREAM; bảng jobs phía trên mô tả TARGET, chưa có ScanUploadedFile job/quarantine worker. Cleanup staged/retention chờ chốt, không tự purge.
