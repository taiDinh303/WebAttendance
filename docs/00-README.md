# 00 — Bảng chỉ dẫn tài liệu

> 2026-10-27 | DOCS v0.4 | Chủ sản phẩm/người duyệt: Đinh Văn Tài (chủ dự án). Bộ docs do Codex soạn theo trao đổi và khảo sát source; chưa nghiệm thu ứng dụng.

## Trạng thái thật

AttendanceWeb đã bắt đầu Laravel API trong `attendance_api/` theo DEC-014; frontend `atd_react/` đã được triển khai theo DEC-022/023, xem FRONTEND-IMPLEMENTATION. Có MySQL local tách dev/test, chưa có cloud hoặc dữ liệu cũ được import. Xem [API hiện có](API-CURRENT.md) và worklog để phân biệt phần đã code với target toàn sản phẩm. WebAttendance cũ là PHP thuần/PDO + MySQL, session, HTML/CSS/JS; xem [khảo sát](LEGACY-AUDIT.md).

2026-10-27/phase2: đã triển khai lịch giờ thực/lặp tuần và QR snapshot, sửa tay/audit, chốt vắng/thống kê theo DEC-015–017. Xem [đặc tả và contract lịch/điểm danh](SCHEDULE-ATTENDANCE.md). Phần cấm manual trong DEC-003 cũ đã được thay thế, không hỏi lại.

Đã xác nhận: nhiều giáo viên tự tạo lớp độc lập; React gọi PHP Laravel API; tự đăng ký; mã lớp và giáo viên duyệt; học sinh QR và giáo viên sửa tay có audit; nộp file/chấm điểm/trắc nghiệm; chat giáo viên–học sinh; thông báo; phân trang và dữ liệu riêng theo quyền. Chi tiết và nguồn tại [intake](PROJECT-INTAKE.md).

2026-10-27/phase3: learning API verified local, full suite51tests/574assertions và HTTP36checksPASS. Xem [bài tập và điểm](ASSIGNMENTS-GRADES.md) và [evidence](evidence/learning-validation.md). Quiz/chat chưa triển khai; React đã có các module backend hiện hành, xem evidence/react-validation.md.

## Thứ tự đọc

1. File này → PROJECT-INTAKE → 01-plan → worklog.
2. Triển khai module: 02 nghiệp vụ → 05 luồng → 04 dữ liệu → 15 API → 06 nghiệm thu.
3. Kiến trúc/auth: 03, 14, 16; giao diện: 09 và FRONTEND-IMPLEMENTATION.
4. Khi tiếp tục công việc, xem evidence mới và trạng thái task trước khi thay docs cũ.

## File chủ quản

| File | Nội dung |
| --- | --- |
| [PROJECT-INTAKE](PROJECT-INTAKE.md) | Đầu vào, câu trả lời, Q còn mở |
| [API-CURRENT](API-CURRENT.md) | API đã triển khai, cách chạy/test, giới hạn thực tế và OpenAPI |
| [ASSIGNMENTS-GRADES](ASSIGNMENTS-GRADES.md) | Learning API verified local: private file, revision, điểm công bố/average |
| [QUIZZES](QUIZZES.md) | Quy tắc quiz đã chốt và câu hỏi engine còn mở |
| [SCHEDULE-ATTENDANCE](SCHEDULE-ATTENDANCE.md) | Lịch/lặp tuần, QR30/60s, snapshot roster, sửa tay/audit, finalize và thống kê |
| [LEGACY-AUDIT](LEGACY-AUDIT.md) | Source cũ, tính năng kế thừa, phát hiện và giới hạn khảo sát |
| [01-plan](01-plan.md) | Backlog, thứ tự, dependencies và cổng nghiệm thu |
| [02-business-analysis](02-business-analysis.md) | REQ, RULE, phạm vi và ma trận quyền |
| [03-architecture](03-architecture.md) | Backend/frontend, tổ chức code và hạ tầng đề xuất |
| [04-database-design](04-database-design.md) | Entity, trường, ràng buộc, indexes và dữ liệu hiện có |
| [05-workflow](05-workflow.md) | Luồng, state machine và ngoại lệ |
| [06-testing](06-testing.md) | TC, môi trường, bằng chứng và release gates |
| [07-deploy-overview](07-deploy-overview.md) | Phương án triển khai và thành phần cần vận hành |
| [08-deploy-detail](08-deploy-detail.md) | Runbook release/rollback, chưa chạy |
| [09-ui-design](09-ui-design.md) | Màn hình, tokens, trạng thái và tương tác |
| [10-setup-local](10-setup-local.md) | Chuẩn bị môi trường, lệnh tương lai |
| [11-setup-cloud](11-setup-cloud.md) | Checklist cấu hình cloud chưa chọn provider |
| [12-glossary](12-glossary.md) | Thuật ngữ và quy ước ID |
| [14-architecture-decisions](14-architecture-decisions.md) | DEC đã chốt và ADR đề xuất |
| [15-api-contract](15-api-contract.md) | Route, payload, response, lỗi, pagination |
| [16-security-privacy](16-security-privacy.md) | Isolation, auth, file, chat và dữ liệu cá nhân |
| [17-environment-config](17-environment-config.md) | Nhóm biến cấu hình, secret và validation |
| [18-release-checklist](18-release-checklist.md) | Điều kiện phát hành, bằng chứng cần thu |
| [23-data-migration](23-data-migration.md) | Mapping cũ→mới, dry run và đối soát |
| [27-realtime-jobs](27-realtime-jobs.md) | Chat, notification, outbox, worker, reconnect |
| [BACKEND-IMPLEMENTATION](BACKEND-IMPLEMENTATION.md) | Thiết kế module backend và checklist thực hiện |
| [FRONTEND-IMPLEMENTATION](FRONTEND-IMPLEMENTATION.md) | Route, query keys, forms và checklist frontend |
| [GIT-WORKFLOW](GIT-WORKFLOW.md) | Nhánh nâng cấp, giữ lịch sử commit và hướng dẫn push/merge |
| [worklog](worklog.md) | Công việc thực tế, bằng chứng, giới hạn và bước tiếp |
| [evidence/legacy-file-inventory.csv](evidence/legacy-file-inventory.csv) | Tất cả file cũ ngoài .git: loại, kích thước, SHA256, mức khảo sát |

Không tạo file 13/Figma hoặc các chương thanh toán/AI chỉ để đủ số; chưa có yêu cầu tương ứng. Bổ sung khi phát sinh nhu cầu thật.

## Quy tắc cập nhật

- CONFIRMED là quyết định chủ dự án, không đồng nghĩa runtime đã có. PROPOSED là thiết kế có thể điều chỉnh sau phỏng vấn. NOT RUN/NOT VERIFIED phải giữ nguyên cho bước chưa thực hiện.
- Code và test phản ánh CURRENT; DEC và yêu cầu chủ dự án phản ánh TARGET. Nếu khác nhau, ghi khoảng cách, không sửa đặc tả để hợp thức hóa lỗi.
- Mỗi task ghi: ID, owner, trạng thái, evidence, ngày, việc còn lại. Chỉ đánh VERIFIED sau test phù hợp; build không chứng minh isolation hoặc realtime.
- Quyết định mới cập nhật intake + DEC + file chủ quản + task/TC liên quan + worklog trong cùng thay đổi.
- Không kế thừa quyết định sản phẩm, secrets, hosting, quyền push hoặc quy tắc docs đóng băng từ hai repo tham khảo. Chỉ học cách tổ chức, phân tầng và ghi evidence.

## Cập nhật2.0 — 2026-10-28

TASK-023 theo DEC-025/026: dashboard theo role, shell mới, UI catalog/class/schedule/self-leave/attendance audit đã triển khai và kiểm chứng local. Full backend79tests/690assertions, frontend23unit,4browser scenario;30process check-in cùng window PASS tại tầng Laravel/MySQL. Chi tiết và giới hạn tại [06-testing](06-testing.md); source phiên bản frontend2.0.0 không đồng nghĩa quiz/chat/feed/export/production đã hoàn tất.
