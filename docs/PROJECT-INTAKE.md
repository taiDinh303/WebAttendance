# Hồ sơ đầu vào và phỏng vấn

> 2026-10-27 | CONFIRMED + OPEN | Người quyết định: chủ dự án; các đáp án chưa có là OPEN, không coi là mặc định đã duyệt.

## Đầu vào

| Mục | Giá trị / nguồn |
| --- | --- |
| Sản phẩm | AttendanceWeb, nâng cấp WebAttendance, giống classroom; yêu cầu trực tiếp |
| Đích | `D:\My Projects\AttendanceWeb`; folder tồn tại và rỗng lúc bắt đầu |
| Source | `D:\My Projects\WebAttendance`, Git main, HEAD `02917e3d65f8659bc09560f40591c1f75be21e57` |
| Tham khảo | M-ChamCong và NoteTatCa: docs, code tổ chức dịch vụ, phân quyền, UI React |
| Tác vụ hiện tại | Chủ dự án giao bắt đầu Laravel API và tạo folder rỗng atd_react; không làm UI ở đợt này |
| Ảnh đính kèm | Mô tả CV: web PHP/MySQL/JS điểm danh QR, vai trò team leader nhóm 3, Oct–Nov 2025. Là thông tin người dùng cung cấp; không chứng minh runtime/timeline phát triển của bản mới |
| Database thật | Chưa cung cấp; dump SQL chỉ là snapshot source, không coi là bản dữ liệu đang chạy |
| Hosting / ngân sách / hạn | Chưa có |

## Các câu đã trả lời — không hỏi lại

| ID | Trả lời chủ dự án ngày 2026-10-26 | Quyết định |
| --- | --- | --- |
| Q-001 | Nhiều giáo viên tự tạo lớp độc lập | DEC-001 |
| Q-002 | React + Laravel API; giáo viên/học sinh tự đăng ký, vào lớp bằng mã; đồng ý React TypeScript/Vite sau giải thích PHP | DEC-002 |
| Q-003 | Tạm thời only QR | DEC-003; GPS/IP và điểm danh thủ công không nằm trong bản đầu |
| Q-004 | Cần duyệt khi vào lớp, giáo viên xem được | DEC-004 |
| Q-005 | Bài tập nộp file, chấm điểm và trắc nghiệm | DEC-005 |
| Q-006 | Có chat trao đổi học sinh–giáo viên; thông báo giáo viên khi học sinh vào lớp hoặc hoạt động liên quan | DEC-006 |
| Q-007 | Đầy đủ phân trang; dữ liệu lớp và điểm của giáo viên/học sinh độc lập | DEC-007 |
| Q-008 | Soạn docs trước, tham khảo hai repo, theo dõi có gì/đến đâu/dữ liệu nào đã có | DEC-008 |
| Q-009 | Một vai trò/tài khoản; giáo viên dùng ngay sau xác minh email | DEC-009 |
| Q-010 | Giáo viên tạo nhiều lớp riêng, tự tạo học kỳ/môn riêng; lớp không bắt buộc môn/học kỳ | DEC-010 |
| Q-018 (DB) | Chọn MySQL; hosting/tải/ngân sách chưa chốt, chuyển Q-026 | DEC-011 |
| Q-020 (access) | Rời/bị loại thì ngừng truy cập lớp ngay, giữ dữ liệu server cho giáo viên; retention chuyển Q-025 | DEC-013 |
| Q-022 | Đã rời/bị từ chối được xin duyệt lại; bị loại phải được giáo viên cho phép lại | DEC-012 |
| Q-023 (2026-10-27) | Giờ thực + lặp tuần + sửa/hủy từng buổi | DEC-015 |
| Q-011 (2026-10-27) | Giáo viên chọn giờ mở/đóng/mốc muộn; QR đổi30giây, hiệu lực60giây; chốt danh sách khi mở; hết cửa sổ mới tính vắng | DEC-016 |
| Q-024 (2026-10-27) | Giáo viên có thể sửa tay cho linh hoạt nếu muốn | DEC-017, thay giới hạn manual của DEC-003 |
| Q-012 (2026-10-27) | Dùng thang10 cho tất cả bài và có điểm trung bình | DEC-018 |
| Q-027 (2026-10-27) | Trung bình điểm đã công bố, trọng số bằng nhau; bài chưa chấm/chưa nộp chưa tự tính0 | DEC-019 |
| Q-013 (2026-10-27) | Theo đề xuất:5file×20MB mỗi lần, PDF/Word/Excel/PowerPoint/ảnh/TXT; teacher bật/tắt nộp muộn/nộp lại; giữ revision history | DEC-020 |

| Q-014 (2026-10-27) | Theo đề xuất: một/nhiều đáp án; giáo viên chọn thời gian và số lượt; câu nhiều đáp án phải đúng đủ; chỉ công bố đáp án sau khi đóng bài | DEC-021 |

| 2026-10-27 React | Chủ dự án giao triển khai React tại AttendanceWeb | DEC-022, thay giới hạn folder rỗng |
| 2026-10-27 Phân tầng | Làm/phân tầng như NTC, Marixa, Badminton trong D:\My Projects | DEC-023 |

## Câu hỏi tiếp theo theo cổng triển khai

Đề xuất dưới đây dùng để viết thiết kế cụ thể, chưa được chủ dự án phê duyệt. Có thể code phần không phụ thuộc trong giai đoạn sau; không phát hành hành vi chưa chốt.

| Q | Cần quyết định | Đề xuất ban đầu | Chặn bước |
| --- | --- | --- | --- |
| Q-028 | Quiz nhiều lượt lấy điểm nào? | Điểm cao nhất / mới nhất / giáo viên chọn — đã hỏi, chưa trả lời | Quiz publication |
| Q-029 | Trọng số câu quiz? | Bằng nhau hoặc tự đặt — đã hỏi, chưa trả lời | Quiz scoring |
| Q-030 | Thời lượng lượt và thời điểm hiện điểm? | Thời lượng riêng không vượt giờ đóng; điểm/đáp án sau đóng — đã hỏi, chưa trả lời | Quiz timing |
| Q-015 | Chat riêng 1–1 hay cả nhóm lớp, gửi file, sửa/xóa? | V1 chat riêng GV–HS trong phạm vi lớp active; chỉ text; chưa group/file/edit | Chat schema/UI |
| Q-016 | Thông báo chỉ trong app hay cả email/push? Nhắc hạn khi nào? | Trong app trước; email tài khoản riêng; nhắc hạn tùy chọn, chưa push | Notifications/jobs |
| Q-017 | Có chuyển dữ liệu cũ không, bản dump nào là chuẩn? | Chưa chuyển; chỉ dry run sau khi được giao, đổi mật khẩu nếu hash không phù hợp | Migration |
| Q-019 | UI tiếng Việt/Anh, thương hiệu, dark mode? | Việt mặc định; i18n-ready; nền sáng, xanh lam/cyan; dark mode sau | UI nghiệm thu |
| Q-021 | Admin được xem nội dung lớp/chat/bài nộp đến mức nào? | Quản lý tài khoản/abuse; không tự động có quyền đọc nội dung | Admin/privacy |
| Q-025 | Thời hạn lưu chat/bài nộp/điểm, xóa tài khoản, khôi phục? | Chưa purge tự động, giữ dữ liệu lịch sử đến khi chốt | Retention/account removal |
| Q-026 | Hosting, domain, ngân sách, tải dự kiến và thời hạn? | Chỉ local trước; chưa tạo cloud, không cam kết capacity | Cloud/release |

## Quyết định triển khai kỹ thuật đợt API đầu

Ngày 2026-10-26 chủ dự án giao code API và folder `atd_react` rỗng (DEC-014). Chọn Laravel/Sanctum cookie session + CSRF, ULID, FormRequest/Policy/Resource/Action, MySQL portable riêng dev/test; không chuyển dữ liệu cũ. Patch versions/actual tests xem API-CURRENT/worklog. Scope đợt đầu: auth/profile, catalog môn/học kỳ, lớp/mã mời, duyệt/rejoin/leave/remove và inbox thông báo membership. QR/lịch/bài tập/quiz/chat chưa triển khai; cần chốt Q tương ứng. Điều này không tự duyệt các đề xuất TTL/thang điểm/upload.

## Cách ghi câu trả lời mới

Ghi nguyên ý, ngày và nguồn; chuyển Q sang ANSWERED, thêm DEC mới nếu đổi hành vi; cập nhật RULE/API/TC/TASK liên quan. Không biến đề xuất kỹ thuật thành câu trả lời giả. Chưa có deadline/budget thì không cam kết ngày bàn giao hoặc plan hosting.
