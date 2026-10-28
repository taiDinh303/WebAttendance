# 09 — Đặc tả giao diện và trạng thái

> 2026-10-28 | CURRENT2.0 + TARGET | DEC-025 chốt tiếng Việt, nền sáng/xanh và ưu tiên dashboard/workspace. Shell2.0 đã triển khai, trạng thái và evidence ở FRONTEND-IMPLEMENTATION/06-testing. Brand chính thức và dark mode còn OPEN. Các màn hình tương lai bên dưới vẫn là TARGET, không phải inventory tính năng đã chạy.

## Hướng thiết kế

Web lớp học rõ ràng, nền sáng, sidebar nhỏ và nội dung rộng; dashboard ưu tiên việc cần xử lý: yêu cầu vào lớp, bài cần chấm, buổi sắp học. HS ưu tiên lịch hôm nay, bài sắp hạn, QR và điểm mới. Tham khảo token/layout tập trung Marixa và trạng thái form/query rõ của NoteTatCa; không sao chép tên thương hiệu hoặc màu đã chốt của hai sản phẩm sang đây như quyết định người dùng.

Đề xuất token: primary #1647C8, accent #008A9B, background #F5F6F8, surface #FFFFFF, text #172033, muted #526174, border #DCE3EC; success/warning/error luôn có chữ. Font hỗ trợ tiếng Việt; body16, label14, title24; spacing4/8/12/16/24/32; input radius8/card12. Kiểm contrast từng cặp trước nghiệm thu, không tự tuyên bố đạt WCAG chỉ từ hex. Tokens tập trung `src/shared/styles/tokens.css` khi code.

## Navigation và màn hình

| Khu/route dự kiến | Nội dung, action và trạng thái đặc biệt |
| --- | --- |
| `/`, `/login`, `/register`, `/forgot-password`, `/reset-password`, `/verify-email` | Giới thiệu, role khi đăng ký, form/validation, email sent/expired/resend; không demo login bằng tài khoản thật |
| `/app` | Teacher: yêu cầu/bài cần chấm/lịch; HS: lịch/bài hạn/điểm mới; card không lộ dữ liệu lớp khác |
| `/app/classes` | Search/filter semester/status; paginated cards hoặc table; teacher Create, HS Join bằng mã |
| `/app/join-requests` | HS xem pending/rejected, cancel; không gọi lớp pending là đã tham gia |
| `/app/classes/:id` | Header tên/môn/học kỳ; tabs Bản tin, Bài tập, Trắc nghiệm, Lịch, Thành viên, Điểm danh, Điểm; tab theo role |
| `…/requests` | Teacher pending queue phân trang, approve/reject từng người, confirmation; stale state409 refresh |
| `…/members` | Teacher roster/search/import/export/remove; HS roster tên tối thiểu theo policy, không email/phone/điểm lớp |
| `/app/calendar` và `…/schedule` | Lịch tuần/tháng/list, filter lớp, timezone rõ; teacher tạo/sửa/hủy, preview recurrence |
| `…/sessions/:sessionId/qr` | Teacher QR lớn, thời hạn server, rotate/count/check-in list; nút đóng rõ, reconnect/refetch; rời trang không ngầm close |
| `/check-in` | Token QR → login nếu cần → xác nhận tên lớp/buổi → submit; expired/not-member/closed/duplicate thành công rõ |
| `…/attendance` | Teacher grid học sinh×buổi có filter, sửa tay theo DEC-017: chọn status, reason, version và xem audit; HS lịch sử self, không được sửa |
| `…/announcements/:announcementId` | Bản tin/comments paginated, draft teacher, moderation; form lỗi giữ text |
| `…/assignments/:assignmentId` | Đề/deadline/maxScore/files; HS upload progress + server scan, submit receipt/revision; teacher submissions queue |
| `…/submissions/:submissionId` | Teacher revision selector/download/score/feedback/save draft/publish; HS receipt/feedback published self |
| `…/grades` | Teacher gradebook paginated theo HS và bài; HS điểm self; trung bình điểm đã công bố theo DEC-018/019 |
| `…/quizzes/:quizId` | Teacher authoring/preview/publish; HS instructions/attempt policy/start; result theo thời điểm cho phép |
| `/app/attempts/:attemptId` | Đồng hồ theo expiresAt server, question navigation/answered state, save ACK, submit confirm; warning offline, resume |
| `/app/messages` | Danh sách thread phân trang/cursor, class context, history load older, pending/failed/sent, unread; không tạo participant tùy ý |
| `/app/notifications` | Inbox paginated read/unread, mark read, deep link; tài nguyên đã gỡ/mất quyền xử lý rõ |
| `/app/settings` | Profile/avatar/security/locale theo self; destructive account action chờ Q-020 |
| `/admin/users`, `/admin/audit` | Account status/search/pagination/audit; không tự thêm nút đọc chat/bài HS |

## Hành vi chung

- URL lưu page/filter/sort; thay filter reset page1; size20 đề xuất, chọn10/20/50/100. Desktop bảng; mobile row/card, không giấu dữ liệu quan trọng chỉ vì nhỏ.
- Query key có actor/class/filter, xóa cache khi logout/đổi actor; không hiển thị điểm account trước trong loading account mới.
- Loading dùng skeleton đúng vùng; empty phân biệt chưa có dữ liệu và filter không có kết quả; error có retry đọc; save lỗi giữ draft/file trạng thái. Không tự retry submit/approve/publish nếu chưa có key.
- 401 đăng nhập lại, 403/404 thông báo an toàn, 409 giữ draft và cho tải phiên bản mới, 422 lỗi từng trường, 429 đếm theo Retry-After, 503 giữ nội dung chưa gửi.
- Dialog focus trap/return focus, label thật, keyboard được, touch target44px đề xuất, reduced motion. Biểu đồ có bảng số liệu thay thế; status không chỉ màu.
- Chat: ACK nhận từ server mới Sent; WS không nhận vẫn refetch được. Upload100% không đồng nghĩa đã submit; quiz đang gõ không đồng nghĩa đã saved.
- Chỉ QR dùng camera cho đọc mã; không thu ảnh mặt/GPS. Nếu browser không hỗ trợ, hiển thị hướng dẫn mở URL QR hợp lệ, không mở manual attendance ngầm.
