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
| Q-028/029/030 (2026-10-31) | Quiz: điểm áp dụng khi nhiều lượt do giáo viên chọn; giáo viên tự đặt trọng số câu (mặc định bằng nhau); giáo viên chọn thời lượng mỗi lượt (không vượt giờ đóng) và thời điểm hiện điểm; đáp án vẫn chỉ sau quiz đóng | DEC-031 |
| Q-015 (2026-10-31) | Group chat cả lớp trong lớp active; tạm thời chưa có chat riêng 1–1; gửi file/sửa-xóa tin chưa chốt | DEC-032 |
| Q-016 (2026-10-31) | Cần email thật (cấu hình SMTP do chủ dự án); có nhắc hạn nộp bài, thời điểm do giáo viên tự chọn; chưa có push mobile | DEC-033 |
| Q-026 phần hosting (2026-10-31) | MySQL cloud: Aiven, free plan, khu vực Singapore, instance đã mở; domain/ngân sách/tải dự kiến/thời hạn/SLA vẫn OPEN | DEC-034 |
| Q-ann1 (2026-10-31) | Học sinh được bình luận dưới bản tin đã công bố; bình luận sanitize | DEC-035 |
| Q-ann2 (2026-10-31) | Giáo viên không cần xóa bình luận; có quyền ẩn/masking nội dung nhạy cảm, linh hoạt | DEC-035, thay moderation xóa bằng ẩn (không xóa vật lý) |
| Q-ann3 (2026-10-31) | Bản tin cho cả hai lựa chọn: có hạn hiệu lực hoặc không hết hạn; có gắn đầu | DEC-035 |
| Q-exp1 (2026-10-31) | Xuất Excel cho cả attendance và grades | DEC-036 |
| Q-exp2 (2026-10-31) | Cần template mẫu cho export | DEC-036 |
| Q-chat1 (2026-10-31) | Tạm thời chỉ group chat/bản tin cả lớp, chưa chat riêng 1–1 | DEC-032 |
| Q-chat2 (2026-10-31) | Được gửi file trong chat | DEC-037 |
| Q-chat3 (2026-10-31) | Được sửa/xóa tin nhắn | DEC-037 |

## Câu hỏi tiếp theo theo cổng triển khai

Đề xuất dưới đây dùng để viết thiết kế cụ thể, chưa được chủ dự án phê duyệt. Có thể code phần không phụ thuộc trong giai đoạn sau; không phát hành hành vi chưa chốt.

| Q | Cần quyết định | Đề xuất ban đầu | Chặn bước |
| --- | --- | --- | --- |
| Q-028 | Quiz nhiều lượt lấy điểm nào? | ANSWERED 2026-10-31: giáo viên chọn điểm áp dụng — DEC-031 | Quiz publication (chờ engine, không còn bị Q chặn) |
| Q-029 | Trọng số câu quiz? | ANSWERED 2026-10-31: giáo viên tự đặt, mặc định bằng nhau — DEC-031 | Quiz scoring (chờ engine) |
| Q-030 | Thời lượng lượt và thời điểm hiện điểm? | ANSWERED 2026-10-31: giáo viên chọn thời lượng (không vượt giờ đóng) và thời điểm hiện điểm; đáp án vẫn sau quiz đóng — DEC-031 | Quiz timing (chờ engine) |
| Q-015 | Chat riêng 1–1 hay cả nhóm lớp, gửi file, sửa/xóa? | ANSWERED 2026-10-31: group chat cả lớp, chưa chat riêng 1–1; file/sửa-xóa chưa chốt — DEC-032 | Chat schema/UI (chờ design chi tiết) |
| Q-016 | Thông báo chỉ trong app hay cả email/push? Nhắc hạn khi nào? | ANSWERED 2026-10-31: in-app + email thật; nhắc hạn nộp, thời điểm do giáo viên tự chọn; chưa push — DEC-033 | Notifications/jobs (chờ SMTP cấu hình) |
| Q-017 | Có chuyển dữ liệu cũ không, bản dump nào là chuẩn? | Chưa chuyển; chỉ dry run sau khi được giao, đổi mật khẩu nếu hash không phù hợp | Migration |
| Q-019 | UI tiếng Việt/Anh, thương hiệu, dark mode? | Việt mặc định; i18n-ready; nền sáng, xanh lam/cyan; dark mode sau | UI nghiệm thu |
| Q-021 | Admin được xem nội dung lớp/chat/bài nộp đến mức nào? | Quản lý tài khoản/abuse; không tự động có quyền đọc nội dung | Admin/privacy |
| Q-025 | Thời hạn lưu chat/bài nộp/điểm, xóa tài khoản, khôi phục? | Chưa purge tự động, giữ dữ liệu lịch sử đến khi chốt | Retention/account removal |
| Q-026 | Hosting, domain, ngân sách, tải dự kiến và thời hạn? | Chỉ local trước; chưa tạo cloud, không cam kết capacity | Cloud/release |

## Quyết định triển khai kỹ thuật đợt API đầu

Ngày 2026-10-26 chủ dự án giao code API và folder `atd_react` rỗng (DEC-014). Chọn Laravel/Sanctum cookie session + CSRF, ULID, FormRequest/Policy/Resource/Action, MySQL portable riêng dev/test; không chuyển dữ liệu cũ. Patch versions/actual tests xem API-CURRENT/worklog. Scope đợt đầu: auth/profile, catalog môn/học kỳ, lớp/mã mời, duyệt/rejoin/leave/remove và inbox thông báo membership. QR/lịch/bài tập/quiz/chat chưa triển khai; cần chốt Q tương ứng. Điều này không tự duyệt các đề xuất TTL/thang điểm/upload.

## Cách ghi câu trả lời mới

Ghi nguyên ý, ngày và nguồn; chuyển Q sang ANSWERED, thêm DEC mới nếu đổi hành vi; cập nhật RULE/API/TC/TASK liên quan. Không biến đề xuất kỹ thuật thành câu trả lời giả. Chưa có deadline/budget thì không cam kết ngày bàn giao hoặc plan hosting.

## Phỏng vấn phiên bản 2.0 — 2026-10-28

Chủ dự án giao làm theo đề xuất và xác nhận khoảng3giáo viên,90học sinh,30người điểm danh đồng thời (DEC-025/026). Đợt2.0 ưu tiên dashboard theo vai trò, giao diện mới Việt/sáng/xanh và hoàn thiện workspace/thao tác đang có API. Q-019 đã chốt phần ngôn ngữ/phong cách; brand/dark mode chưa chốt. Q-026 đã chốt workload, hosting/domain/ngân sách/thời hạn còn OPEN. Các Q-028/029/030 và Q-015/016 không có đáp án mới; hỏi trước module2.1/2.2 tương ứng.

## Admin/demo — 2026-10-28

Yêu cầu1admin/3teacher/90student,30check-in đồng thời và chuẩn chất lượng8nhóm khoảng8/10. Q-021 ANSWERED: admin tài khoản/thống kê/audit, giữ riêng nội dung lớp (DEC-027); không mặc định có quyền đọc điểm/bài/file/chat. DEC-028 cho seed local demo synthetic bền vững. Không cấp quyền deploy/mail thật/import cũ.

### 2026-10-30 — Hồ sơ và sidebar theo NoteTatCa (CONFIRMED)

Chủ dự án yêu cầu Settings cuối sidebar, thu/mở sidebar, hồ sơ read-first/edit-on-demand, bỏ input timezone và dùng timezone thiết bị khi lưu; thêm gender/birthday/avatar. Chốt Google bên cạnh email/password; chọn chuẩn bị cả avatar có sẵn và tải ảnh, Google/upload chưa khả dụng khi chưa có cấu hình. Không tự bật SMTP, kho ảnh hoặc OAuth credentials. IMPLEMENTED/VERIFIED xem worklog.

### 2026-10-31 — Mở cổng quiz, chat, notification, hosting (CONFIRMED)

Chủ dự án trả lời: quiz theo lựa chọn giáo viên (Q-028/029/030 → DEC-031); group chat cả lớp, chưa chat riêng 1–1 (Q-015 → DEC-032); email thật + nhắc hạn nộp do giáo viên chọn, chưa push (Q-016 → DEC-033); MySQL cloud Aiven free plan Singapore đã mở (phần hosting Q-026 → DEC-034). Q-025 retention/xóa tài khoản và phần domain/ngân sách/thời hạn của Q-026 vẫn OPEN. Ưu tiên module đã duyệt: TASK-009 bản tin → TASK-012 quiz → TASK-013 chat → TASK-015 Excel → Google/avatar → production; không deploy, không gửi mail thật, không chuyển dữ liệu cũ.

2026-11-01 — Chủ dự án giao TASK-015 xuất Excel attendance/grades. Chốt học sinh tự nhập mã sinh viên không bắt buộc (DEC-038); chi tiết cột và triển khai xem [EXPORTS](EXPORTS.md). Không yêu cầu nhập email/điện thoại vào report mặc định, không bật import hoặc chuyển dữ liệu cũ.

2026-11-02: chủ dự án giao dữ liệu minh họa3teacher×3class có10/20/30HS, có attendance và các bảng UI, chọn Aiven cloud và xác nhận Render My Workspace/dinhvantai079@gmail.com; cho phép tự deploy cho công việc này. Phạm vi/guard tại DEMO-VISUAL.md. Không import dữ liệu thật hoặc gửi mail.

### 2026-11-02 — Phong cách NoteTatCa (CONFIRMED)

Chủ dự án yêu cầu đồng bộ sidebar, nút ẩn/hiện bên trái header, logo, avatar/menu tài khoản và dark/light theo NoteTatCa local. DEC-039 thay phần dark mode để sau của Q-019; giữ nghiệp vụ và thương hiệu Attendance.
