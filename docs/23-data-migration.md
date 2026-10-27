# 23 — Chuyển dữ liệu cũ sang mới

> 2026-10-26 | OPTIONAL / NOT AUTHORIZED TO EXECUTE | Có yêu cầu giữ chức năng cũ; chưa có yêu cầu chuyển dữ liệu thật. Q-017 phải chốt snapshot và phạm vi.

## Nguồn và hạn chế

`Database/db_atd.sql` là snapshot trong Git, không khẳng định đang production. TXT schema/procedure có khác biệt dump; dùng schema thật được chủ dự án xác nhận trước mapping. Inventory có SHA256 file nguồn; chưa thống kê/validate số dòng dữ liệu, chưa import DB. Có dữ liệu account/profile/seed trong SQL, không copy vào docs/fixtures.

## Mapping dự kiến

| Legacy | Target | Cần đối soát |
| --- | --- | --- |
| users, roles, user_roles | users/user_roles/profiles + legacy_id_map | ID số chuyển sang identity mới; role trùng/không hợp lệ, email normalize unique |
| students, teachers, admins | profiles + role mapping | Nhiều record cùng user, email thiếu/trùng; không bắt buộc birthday/gender |
| semesters, courses, course_types | catalog owner scoped | Danh mục global cũ chia owner hoặc tham chiếu shared theo Q-010, không đoán |
| classes | classes | Teacher owner hợp lệ, class_id cũ giữ trong mapping, không tái dùng làm invite |
| class_students | memberships + events | Status active/inactive cũ không đồng nghĩa pending/rejected; không tạo lịch sử duyệt giả |
| schedules | class_sessions | date/start/end là ngày/giờ/tiết; mapping tiết sang giờ cần Q-023; timezone xác nhận |
| attendances | attendance_records | Map status -1/0/1/2 theo nghĩa thực, quan hệ HS–buổi, duplicate/orphan, không đoán theo comment |
| attendance_reports | Recompute aggregate | Không import tổng hợp làm nguồn chuẩn; đối soát chênh lệch sau tính lại |
| announcements/comments | announcements/announcement_comments | Author thiếu trong announcement phải quy tắc minh bạch; sanitize giữ nội dung hợp lệ |
| Avatar assets | private file records khi phù hợp | File tồn tại/type/hash/consent, tránh URL path cũ không an toàn |
| Không có source cũ | assignment/submission/grade/quiz/chat/notification | Khởi tạo rỗng, không bịa dữ liệu lịch sử |

Password: kiểm hash format từng nhóm, không chạy `hash_all_password.php` hoặc hash lần hai. Hash tương thích có thể rehash-on-login sau xác nhận; plaintext/không rõ dùng forced reset theo quy trình đã duyệt, không gửi password qua email. Invite/reset token/session cũ không chuyển sang mới.

## Quy trình dry run trước chuyển thật

1. Xác nhận file chuẩn/timezone/scope/retention, backup, checksum, quyền truy cập và dữ liệu nhạy cảm. Lưu baseline count/report an toàn, không log raw account.
2. Parse/import vào DB staging cô lập, không kết nối mail/realtime thật; tạo mapping legacy_table/id→new_id unique.
3. Validation orphan/duplicate/constraint/range, status mapping, giờ/tiết, owner; xuất báo cáo lỗi được redaction để chủ dự án quyết định.
4. Chạy transform idempotent; repeat không nhân đôi. Đối soát counts theo class/user/status, sample approved synthetic/authorized records, attendance sums và file hashes.
5. Test login/reset/ownership/report bằng account thử được phép, kiểm không rò cross-teacher. Không mở pending/removed sai vì mapping.
6. Chủ dự án duyệt cutover window/read-only source/delta/rollback; chỉ sau đó thực thi nhiệm vụ chuyển dữ liệu riêng. Không tự push/deploy/import từ đợt docs.

Rollback: giữ source cũ read-only và backups, không xóa file/dump; dữ liệu ghi mới sau cutover cần đối soát nếu quay lại, không hứa rollback không mất gì. Record dry run counts/command/commit/failures vào evidence/worklog. Tất cả các bước hiện NOT RUN.
