# AttendanceWeb

Web quản lý lớp học cho nhiều giáo viên độc lập: lớp học, lịch học, duyệt học sinh, điểm danh QR, bài tập/nộp file, chấm điểm, trắc nghiệm, chat và thông báo.

**2026-10-27 — API đang triển khai theo giai đoạn.** PHP Laravel REST API, MySQL đã chốt; frontend React + TypeScript + Vite đã được triển khai trong `atd_react` theo DEC-022/023, phân tầng app/domain/infrastructure/application/features/shared. Hosting chưa chọn. Xem [API hiện có và cách chạy](docs/API-CURRENT.md), không dùng target docs làm bằng chứng tất cả module đã hoàn thành.

Bắt đầu tại [docs/00-README.md](docs/00-README.md). Xem [kế hoạch](docs/01-plan.md), [phỏng vấn](docs/PROJECT-INTAKE.md) và [tiến độ thực tế](docs/worklog.md). Source gốc ở `D:\My Projects\WebAttendance`; không chỉnh sửa source gốc khi phát triển phiên bản mới.

## Cấu trúc

- `docs/`: nguồn đặc tả duy nhất; mỗi nội dung có file chủ quản.
- `docs/evidence/`: kiểm kê source cũ và kết quả kiểm tra tài liệu.
- `attendance_api/`: Laravel API và tests.
- `atd_react/`: React frontend, pages/components theo feature, API kết nối Laravel thật.
- `scripts/`: PowerShell gọi PHP/Composer/artisan/MySQL portable.
- `.tools/`, `work/`: runtime/dev DB/secrets và scratch/evidence tạm, đã ignore Git.

Không chạy migration, deploy hoặc import dữ liệu chỉ vì đọc được lệnh trong tài liệu. Bản nâng cấp Laravel/React đã merge vào main của taiDinh303/WebAttendance qua PR #1, giữ toàn bộ lịch sử cũ; xem [Git workflow](docs/GIT-WORKFLOW.md) để push/merge mà giữ commit cũ.
