# AttendanceWeb working agreement

Đọc docs/00-README.md, PROJECT-INTAKE.md, 01-plan.md, 14-architecture-decisions.md và worklog.md trước khi làm. Yêu cầu trực tiếp của chủ dự án có ưu tiên.

- Chủ dự án đã giao triển khai API ngày 2026-10-26; DEC-022 ngày2026-10-27: chủ dự án đã giao triển khai React trong atd_react; DEC-023 yêu cầu phân tầng theo NTC/Marixa/Badminton, thay giới hạn folder rỗng trước đó. Tiếp tục phỏng vấn những Q chặn module. Không tự deploy, gửi mail thật hay chuyển dữ liệu cũ.
- Backend PHP Laravel API; frontend React TypeScript/Vite. Không chuyển sang .NET theo hai repo tham khảo.
- Docs ở root docs/; không tạo docs trùng trong frontend/backend. Dùng UTF-8, ID REQ/RULE/FLOW/API/TC/TASK/DEC/Q ổn định và link tương đối.
- Phân biệt CONFIRMED (người dùng chốt), PROPOSED (thiết kế đề xuất), IMPLEMENTED (có code), VERIFIED (có bằng chứng). Không ghi tính năng DONE khi chỉ có docs.
- Giáo viên chỉ quản lý lớp thuộc mình; học sinh chỉ truy cập lớp được duyệt và bài nộp/điểm của mình. Kiểm quyền cả REST, file download, export, background job và realtime channel.
- DEC-017 ngày 2026-10-27 thay giới hạn QR-only: học sinh check-in bằng QR, giáo viên owner được sửa tay có lý do và audit trước/sau. Không thêm GPS/IP hoặc nhận diện khuôn mặt. QR đổi30s, TTL60s, roster chốt khi mở theo DEC-016.
- Không đưa secret, nội dung chat, bài nộp, dữ liệu cá nhân từ dump cũ vào docs/seed/log. Không sử dụng connection string hoặc SMTP cũ để test.
- Controller mỏng, FormRequest/Policy/Resource rõ; action/service theo nghiệp vụ, Eloquent/transaction Laravel. Không nhân bản generic repository/UoW của .NET khi không có lý do.
- Cập nhật file chủ quản và worklog cùng thay đổi. Trước code module, chốt các Q chặn module đó; không hỏi lại DEC đã CONFIRMED. Thiết kế không bị chặn vẫn tiếp tục.
- Chỉ commit/push/deploy khi được giao phù hợp; quyền push từ NoteTatCa không áp dụng sang repo mới.
