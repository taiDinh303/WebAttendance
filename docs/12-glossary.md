# 12 — Thuật ngữ và quy ước

> 2026-10-26 | DRAFT

| Thuật ngữ | Nghĩa trong AttendanceWeb |
| --- | --- |
| Teacher / owner | Giáo viên sở hữu lớp, không phải mọi tài khoản role teacher |
| Student | Tài khoản học sinh, có thể active nhiều lớp sau duyệt |
| Class | Lớp học thuộc một teacher; không đồng nghĩa lớp hành chính trong hồ sơ HS |
| Membership | Quan hệ HS–lớp có status pending/active/rejected/left/removed |
| Invite code | Bí mật mời lớp, khác classId, có thể rotate/tắt |
| Semester / course | Học kỳ / môn học; scope danh mục Q-010 |
| Session (class) | Một buổi học; phân biệt login session là phiên đăng nhập |
| Attendance window | Khoảng server cho check-in QR, không phải toàn bộ giờ học |
| QR challenge | Token ngắn hạn cho cửa sổ, không phải tài khoản hoặc bằng chứng vị trí |
| Announcement | Bản tin lớp có nội dung/bình luận |
| Notification | Inbox cá nhân báo sự kiện, deep link kiểm quyền lại |
| Assignment | Bài tập có đề/deadline/maxScore/chính sách nộp |
| Submission revision | Một lần nộp bất biến; upload file riêng chưa là nộp bài |
| Grade / publish | Điểm và hành động công bố cho HS, draft score không hiển thị |
| Quiz / attempt | Bài trắc nghiệm / lượt làm theo snapshot và server deadline |
| Conversation | Thread chat theo participant và scope lớp |
| Outbox | Sự kiện DB cùng transaction, worker dispatch có retry/dedup |
| Cursor | Mốc phân trang opaque cho chat/history, khác page number |
| Idempotency | Gửi lại cùng thao tác không sinh hiệu ứng nghiệp vụ lặp |
| Optimistic version | Version chống ghi đè cập nhật đồng thời; stale version409 |
| CURRENT / TARGET | Hiện trạng có evidence / trạng thái sản phẩm muốn đạt |
| CONFIRMED / PROPOSED | Người dùng chốt / thiết kế đề xuất chưa chốt |
| IMPLEMENTED / VERIFIED | Có code / đã kiểm chứng phạm vi được nêu |
| NOT STARTED / NOT RUN | Chưa làm / chưa chạy kiểm tra; không được ghi PASS |

REQ yêu cầu; RULE quy tắc; FLOW luồng; API nhóm contract; TC nghiệm thu; TASK việc triển khai; Q phỏng vấn; DEC quyết định xác nhận; ADR-P phương án kiến trúc đề xuất; LEG mục kế thừa; FIND quan sát source. ID giữ ổn định khi sửa docs, không đánh số lại khiến mất trace.
