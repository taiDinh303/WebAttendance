# Khảo sát WebAttendance và hai project tham khảo

> 2026-10-26 | STATIC REVIEW | Không chạy ứng dụng, import SQL hay kết nối SMTP/DB cũ.

## Mức bao phủ

Kiểm kê toàn bộ 1.194 file ngoài `.git`: 150 file văn bản first-party (PHP/JS/CSS/composer/SQL/TXT) đã được đọc bằng công cụ để quét nội dung; đọc sâu các luồng account/role, join lớp, QR/ghi attendance, lịch, announcement và schema/trigger. Không khẳng định review từng dòng tất cả thư viện. 1.039 file third-party (`vendor/` và bản PHPMailer trong `Mailer/`) chỉ kiểm kê/hash; 5 asset chỉ kiểm kê, chưa kiểm tra hình ảnh trực quan. Xem [inventory](evidence/legacy-file-inventory.csv), trường Review nêu mức kiểm tra từng file. File rỗng được giữ trong inventory; lỗi quét null ở lần đầu được khắc phục bằng lần kiểm kê đọc toàn bộ sau.

`composer.json` pin PhpSpreadsheet 1.23.0; lock và vendor có sẵn. Đây là dependency cũ, không tự mang nguyên sang Laravel mới. `Connect/` và mail account có cấu hình nhạy cảm; không sao chép giá trị. SQL có seed người dùng/hồ sơ, không xuất lại dữ liệu cá nhân.

## Tính năng và đối chiếu phạm vi

| LEG | Bằng chứng tương đối trong WebAttendance | Có trong source cũ | AttendanceWeb |
| --- | --- | --- | --- |
| LEG-001 | Account/login.php, register_student.php, register_teacher.php, islogin.php | Session, role admin/teacher/student, đăng ký và chuyển trang | REQ-001: Laravel auth + server policies |
| LEG-002 | Account/forgot-pass.php, reset-password.php, change-password*.php, SendMailer*.php | Quên/đổi mật khẩu, mail | Giữ nghiệp vụ; token một lần và mail adapter mới |
| LEG-003 | */Information/* | Hồ sơ các role, avatar ở teacher | REQ-002; chuẩn hóa self profile và upload |
| LEG-004 | Admin/User/* | Danh sách, đổi role/tài khoản, xóa | REQ-012; quyền admin chi tiết chờ Q-021 |
| LEG-005 | Admin/Semester/*, Admin/Course/* | Học kỳ, môn, course_types có tín chỉ/tiết LT–TH | REQ-003; scope danh mục chờ Q-010 |
| LEG-006 | Teacher/Class/class_create.php, class_edit.php, delete_class.php; Admin/Class/* | Tạo/sửa/xóa/xem lớp, lọc học kỳ | REQ-004; archive đề xuất để bảo toàn lịch sử |
| LEG-007 | Teacher/Class/add_student.php, remove_student.php, import_excel.php, export_excel.php | Danh sách, thêm/xóa học sinh, Excel | REQ-005/011; import preview, không tự tạo account thật |
| LEG-008 | Student/Class/join_class.php, delete_class.php; LayoutPages/navbar_student.php | Nhập class_id; chỉ kích hoạt học sinh đã có roster; rời lớp đặt inactive | Bản mới tạo yêu cầu vào lớp rồi owner duyệt; mã mời tách ID |
| LEG-009 | Teacher/Schedule/*, Teacher/Class/*schedule.php, Student/Schedule/* | Lịch giáo viên/học sinh, lọc học kỳ/tuần, tạo buổi | REQ-006; chuẩn hóa lịch và phát hiện xung đột |
| LEG-010 | Teacher/Attendance/attendance_qr.php, save_time.php; Student/Attendance/* | QR chứa URL với class_id/schedule_id; cửa sổ status và thời gian | REQ-007; token có TTL, quyết định server, chống ghi trùng |
| LEG-011 | Teacher/Attendance/process_attendance.php, attendance_edit.php | Điểm danh/sửa thủ công | Lưu dấu chức năng cũ; KHÔNG triển khai bản đầu theo DEC-003 |
| LEG-012 | Teacher/Attendance/attendance_report.php, export_statistics.php; Student/Attendance/attendance_list.php | Thống kê hiện diện/vắng/muộn, chart, Excel và lịch sử cá nhân | REQ-011; giữ nghiệp vụ với công thức thống nhất |
| LEG-013 | Teacher/Announcement/*; Student/Announcement/* | CRUD thông báo lớp, bình luận, đếm bình luận | REQ-008; bổ sung notification bền vững |
| LEG-014 | Không thấy route/entity bài tập, submission, quiz, grade hoặc chat trong inventory | CHƯA TÌM THẤY module tương ứng | REQ-009/010/013/014 là bổ sung theo chủ dự án, không ghi đã có |

## Dữ liệu cũ có trong source

Dump `Database/db_atd.sql` có 16 bảng: admins, announcements, attendances, attendance_reports, classes, class_students, comments, courses, course_types, roles, schedules, semesters, students, teachers, users, user_roles. Có procedures và bốn trigger attendance trong dump. TXT cấu trúc/thủ tục/trigger là bản mô tả khác; không coi chúng hoàn toàn tương đương dump. Có khác biệt kiểu class_id và biểu diễn tiết/giờ giữa file TXT và procedure; cần schema thật khi chuyển dữ liệu.

## Vấn đề cần thiết kế lại

| FIND | Quan sát tĩnh | Hệ quả cho bản mới |
| --- | --- | --- |
| FIND-001 | islogin.php kiểm role theo đường dẫn; create_schedule.php/edit_schedule.php và update_announcement_content.php không có kiểm owner tại entry point đã đọc | Tất cả mutation bắt buộc Policy scoped resource; kiểm procedure không thay thế HTTP policy |
| FIND-002 | Student/Attendance/process_attendance.php dùng schedule_id và giờ để ghi; không thấy kiểm membership, class–schedule relation, cửa sổ open ở handler | Validate toàn bộ trên server trong transaction; không chỉ ẩn nút ở view |
| FIND-003 | attendance_qr.php tạo URL HTTP với ID; JavaScript beforeunload/sendBeacon điều khiển đóng/mở | Token TTL và state server; đóng tab không quyết định cửa sổ |
| FIND-004 | Student/Class/join_class.php yêu cầu đã có roster; case không tồn tại có exit trong transaction | Luồng yêu cầu duyệt rõ state, commit/rollback đúng, idempotent |
| FIND-005 | schedules.date được dùng như ngày buổi và được save_time điều chỉnh; TXT có start/end dạng số tiết nhưng procedure dùng TIME | Tách giờ học, cửa sổ QR, mốc muộn; migration mapping explicit |
| FIND-006 | Trigger có đổi -1→0 theo ngày, bảng attendance_reports tổng hợp riêng | Một nguồn record attendance; aggregate truy vấn/summary rebuild được, không copy nguyên quy tắc ngày cũ |
| FIND-007 | GET/list/export có nhiều kiểu response, nhiều trang trộn PHP/HTML/SQL; JS/CSS theo role lặp | REST v1 + Resource, layout dùng chung, phân trang/filter nhất quán |

Đây là quan sát source, chưa tái hiện khai thác và chưa xác nhận cấu hình runtime. Không sửa project cũ trong tác vụ này.

## Học từ project tham khảo

- M-ChamCong: đọc docs 00/01/03/05/09, cấu trúc docs còn lại; xem `marixa_aspnet/M.Services/Services/AttendanceService.cs`, `marixa_react/package.json` và `src/theme.css`. Kế thừa thứ tự docs, bảng module, service theo nghiệp vụ, pagination và token UI tập trung. Không mang nghiệp vụ nhân sự/lương, public photo storage hay quyền hosting vào sản phẩm lớp học.
- NoteTatCa: đọc AGENTS root/docs, các phần đầu của 00/PROJECT-INTAKE/01/BACKEND-IMPLEMENTATION/14/15 và worklog gần đây; xem `NTC.Services/Shared/BaseService.cs`, `Service/TagService.cs`, React package. Kế thừa ID quyết định, CURRENT/TARGET, ownership, optimistic version, evidence và test isolation. Không copy .NET Identity/JWT/repository framework sang PHP.
- Đây là tham khảo có chọn lọc docs/code, không tuyên bố review toàn bộ hai repo. Instructions và quyết định của chúng chỉ có phạm vi repo đó.
