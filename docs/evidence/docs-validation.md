# Kiểm chứng bộ docs v0.1

> 2026-10-26 | Phạm vi: file tài liệu và inventory; không phải test ứng dụng.

Đây là snapshot phase docs-only trước DEC-014. Đợt API tiếp theo đã có source/tests, xem [api-validation](api-validation.md); các mục NOT RUN dưới đây chỉ phản ánh thời điểm snapshot này, không ghi đè worklog hiện hành.

## Kiểm tra đã chạy

Shell PowerShell, kiểm folder `D:\My Projects\AttendanceWeb` bằng đường dẫn tuyệt đối. `Get-ChildItem -Recurse -File -Filter *.md`, UTF8Encoding strict decoder, regex Markdown links và `Test-Path` tương đối theo thư mục file; đếm fences code đóng/mở. Chỉ kiểm link nội bộ/file, không xác nhận mọi fragment heading hoặc uptime của link web.

- Lần đầu:27 Markdown, UTF-8 đọc được,1 link thiếu là chính `evidence/docs-validation.md` chưa được tạo. Đã tạo file này và chạy lại kiểm cuối.
- Inventory1194 file =150 first-party text +1039 third-party +5 assets; mỗi row có SHA256/kích thước/mức khảo sát. Không tính `.git`.
- `git -C 'D:\My Projects\WebAttendance' status --short` trả rỗng; HEAD vẫn `02917e3d65f8659bc09560f40591c1f75be21e57`.
- Đã rà ma trận REQ001–016, FLOW001–012, TC001–022, TASK001–019; mỗi REQ có tham chiếu TC, task và luồng. Các ID rút gọn trong bảng giữ prefix đầu; dependencies TASK/Q đã tách để tránh hiểu nhầm.
- Đã rà tĩnh sự thống nhất: QR-only/manual deferred, join pending phải duyệt, isolation của teacher/student, grade draft/quiz answer-key redaction, pagination/cursor, CURRENT vs TARGET và Q còn mở.

Kết quả kiểm cuối được ghi trong [validation-summary.json](validation-summary.json) sinh từ công cụ sau khi tạo tài liệu này; đây là bằng chứng file/link/inventory, không test độ đúng nghiệp vụ hoặc runtime. Bản ZIP là snapshot giao nhận, thư mục docs gốc mới là nguồn tiếp tục cập nhật.

## Chưa chạy / giới hạn

Không chạy PHP/Laravel/React, SQL import, unit/Feature/E2E/load/security runtime tests; không provision/deploy/mail/push. Không audit từng dòng vendor hoặc visually review5 asset. Q-009–024 còn mở; giới hạn kỹ thuật ghi PROPOSED, chưa phê duyệt. Tài liệu dự thảo chưa thay thế nghiệm thu và phỏng vấn module trước code.
