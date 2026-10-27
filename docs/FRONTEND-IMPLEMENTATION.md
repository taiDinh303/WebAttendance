# React frontend — implementation hiện có

> 2026-10-27 | IMPLEMENTED / VERIFIED LOCAL theo phạm vi dưới đây, kiểm chứng theo evidence/react-validation.md. Chủ dự án đã yêu cầu bắt đầu React và phân tầng như NTC/Marixa/Badminton; giới hạn folder rỗng của DEC-014 được thay bằng DEC-022.

## Phân tầng đã áp dụng

```text
atd_react/
  src/
    app/              App/router, providers, auth guard, Shell
    domain/           Models, errors, formatting, QR parser; không React/HTTP
    infrastructure/   HTTP cookie/CSRF + auth/class/learning/attendance APIs
    application/      Session, actor-scoped queries, SubmissionFlow
    features/
      auth/           Đăng nhập/đăng ký/forgot/reset
      dashboard/      Tổng quan từ dữ liệu API
      classes/        Danh sách/workspace/members/join requests
      calendar/       Lịch, tạo buổi/lặp tuần/preview/hủy
      assignments/    Đề, tài liệu, upload, receipt/revisions/grade editor
      attendance/     QR, camera, check-in, roster, sửa tay, history
      grades/         Gradebook/self average
      notifications/  Inbox/read
      settings/       Profile/password
      catalog/        List/create môn/học kỳ
    shared/           UI primitives, tokens/global CSS
  e2e/                Playwright với Laravel/MySQL fixture riêng
```

Học cấu trúc app/application/domain/infrastructure/features/shared của NTC; module/pages/components của Marixa và Badminton. Không copy auth bearer/.NET hay cấu hình deploy/secret của các dự án tham khảo.

Domain không nhập React/Query/API; infrastructure không nhập UI/application; shared không nhập feature/HTTP; feature không dùng fetch trực tiếp. ESLint chặn các hướng phụ thuộc này. Pages điều phối hiển thị; HTTP protocol ở infrastructure; SubmissionFlow giữ staging, payload và idempotency khi mất ACK trong application.

## Công nghệ thực tế

React19.3.1, Router7.18.4, TanStack Query5.105.0, TypeScript6.0.3, Vite8.3.5; QRCode1.5.4, Lucide, Manrope self-hosted. Pin package.json/package-lock.json; không CDN/font API. CSS tokens tập trung, chưa cài Tailwind/shadcn hoặc component framework nặng. Node24.x.

## Session và API

Vite5173 proxy /api và /sanctum tới Laravel8000. API_PROXY_TARGET là biến Node của Vite để test proxy8001, không secret và không phải cấu hình production. VITE_API_ORIGIN trống mặc định; production cần same-site app/API, history fallback và proxy phù hợp. Chưa deploy.

HTTP credentials include; bootstrap XSRF cookie trước mutation, gửi X-XSRF-TOKEN đã decode; JSON envelope data và multipart được xử lý riêng. Không bearer/localStorage auth, không replay mutation khi401/419/429/network error. Server errors/fields/traceId được hiển thị. Query keys private+actorId+resource/filter/page; logout/expire hủy và xóa private queries, cập nhật session riêng. Không clear toàn bộ QueryCache đang có session observer.

Auth guard phân biệt loading/network/unauthenticated/unverified. Vai trò chỉ dùng ẩn thao tác UI; Laravel Policy vẫn quyết định quyền. Không hiển thị tab Thành viên/điều khiển giáo viên cho học sinh. Server404 xử lý mất quyền an toàn.

## Luồng đã có trong source

- Auth: login/register/forgot/reset, verify-email gate/resend/recheck; profile/password.
- Teacher: create/search/paginate classes, update/archive, rotate/disable invite; requests approve/reject, remove/allow-rejoin.
- Student: join code, pending/rejected list/cancel; active class view.
- Calendar: tháng/list/pagination, single session hoặc weekly recurrence preview, cancel; QR entry.
- Learning: draft/create/edit/publish/close, upload/attach material; student5file×20MiB, progress từng file (không giả % byte), receipt và revision list/download.
- SubmissionFlow: giữ các file staging đã upload; sau khi bắt đầu submit khóa payload/key đến ACK; retry cùng key, không tạo revision mới do mất response. Reload chưa giữ draft qua storage; kiểm lịch sử trước khi gửi mới.
- Grade: teacher draft/save/publish with amendment reason; self published grades/average và paginated teacher gradebook. Client không tự tính/giả điểm.
- Attendance: teacher mở QR/snapshot, QR tự refresh, countdown theo serverTime, roster polling, close và manual correction reason/version; student QR link/paste/native BarcodeDetector camera nếu hỗ trợ; dừng camera khi rời component.
- Notifications: inbox/read và unread polling60s; không gọi là realtime.
- Catalog: list/create môn/học kỳ của teacher.

## Chưa hoàn thành

Không có quiz/chat/feed/comment/admin/export UI vì backend tương ứng chưa có. Chưa UI sửa buổi riêng, sửa/xóa catalog, chọn course/semester trên form lớp, self-leave, attendance audit timeline hoặc báo cáo ma trận đầy đủ. API các thao tác này có thể đã có, không đồng nghĩa UI đã làm. Student grades/history còn dùng mã buổi ở một số chỗ vì contract chưa trả tiêu đề/name tổng hợp. Không có offline sync, draft persistence, per-byte upload progress, avatar hoặc notification channels ngoài in-app.

Camera vật lý/permission-denied trên nhiều trình duyệt chưa nghiệm thu; QR URL chạy điện thoại cần hostname/HTTPS truy cập được, localhost trên máy khác không trỏ tới máy dev. Không tự thêm GPS/face. Mail local array không gửi email thật; verification/resets qua email cần mailbox được cấu hình riêng.

## Cách chạy và kiểm chứng

```powershell
# Root AttendanceWeb, hai terminal backend/frontend
.\scripts\mysql.ps1 -Action start
.\scripts\artisan.ps1 serve --host=127.0.0.1 --port=8000 --no-reload
cd atd_react
npm ci
npm run dev
# http://127.0.0.1:5173
npm run typecheck
npm run lint
npm run test
npm run build
```

Đọc [evidence/react-validation](evidence/react-validation.md). Test browser cần HTTP fixture riêng: Laravel8001 --env=http-smoke, frontend5173 với API_PROXY_TARGET=http://127.0.0.1:8001; credentials synthetic nằm work/http-credentials.json ignored, không đưa vào bundle/evidence/ZIP. Không chạy các luồng ghi E2E vào dev/production hoặc source cũ. Sau test dừng fixture và chạy lại frontend với proxy mặc định8000.
