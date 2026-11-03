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

## AttendanceWeb2.0 — TASK-023 (2026-10-28)

IMPLEMENTED: package private2.0.0, shell/sidebar xanh navy trên nền sáng, dashboard tập trung công việc theo vai trò và dùng GET /dashboard thay fetch dữ liệu rời. Teacher có queue duyệt/chấm/công bố; student có bài chưa nộp và điểm đã công bố; lịch7ngày, lớp active, counts không phụ thuộc preview. Menu mobile có nút đóng riêng/Escape, giữ focus trong menu và inert background khi mở.

Đã bổ sung UI sửa buổi riêng với expectedVersion và giữ đúng timezone/giây; sửa/xóa môn và học kỳ; chọn môn/học kỳ phân trang khi tạo/sửa lớp; student leave bằng membershipVersion; audit timeline phân trang/lazy-load chỉ teacher và attendance dùng tên/giờ buổi. Đổi classId remount workspace để không mang mã mời/draft của lớp trước sang lớp khác. Các thao tác này thay phần “chưa UI” tương ứng trong snapshot phía trên.

Quiz/chat/feed/comment/admin/export, báo cáo ma trận đầy đủ, offline sync/draft persistence, avatar và kênh notification ngoài in-app vẫn chưa triển khai trong2.0; theo đợt2.1/2.2 và Q chặn. Màu/ngôn ngữ theo DEC-025; tên thương hiệu/dark mode chưa được quyết định riêng. Không dùng version package để tuyên bố toàn sản phẩm hoàn tất.

## Admin — TASK-024 (2026-10-28)

Thay trạng thái admin chưatriểnkhai trong snapshot2.0 phía trên: admin dashboard /admin, quản lý/lọc tài khoản /admin/users, nhật ký /admin/audit đã IMPLEMENTED/VERIFIED LOCAL. Login admin tự chuyển từ /app về /admin; menu riêng, role label chính xác. API adapter adminApi tái dùng HTTP CSRF/envelope; typed AdminUser/Summary/Event; cache keys có actor; AdminGuard là UX còn API Policy là quyền thực.

Khóa/mở có reason3..500, expectedVersion, error giữ nội dung nhập; tự khóa không có nút và server vẫn chặn. Audit hiển thị actor/subject/reason/before-after/version, lọc/phân trang. Layout tài khoản chuyển card trên mobile390px. Browser real API đã kiểm counts94/1/3/90, lọc, lock/revoke/unlock/relogin/audit, student bị chặn admin, admin bị chặn lớp; không pageerror. Ảnh evidence/admin-desktop.png và admin-mobile.png. Các module quiz/chat/feed/export vẫn giữ trạng thái trước.
VERIFIED TASK-026: typecheck, lint,23unit tests và build PASS; Edge local kiểm login/register/reset toggle, giữ giá trị và độc lập giữa các ô PASS. Settings dùng chung component; không đổi mật khẩu tài khoản để test.

## 2026-10-30 — Public Home / startup

IMPLEMENTED: / và /home là trang chủ công khai, truy cập login/register/app bằng link. /app giữ Guard và kiểm quyền hiện hành. Startup xác thực có trạng thái/progress indeterminate, không giả lập tiến độ collection; timeout60s cho request me, offline pending không giữ spinner. Chưa VERIFIED browser/cloud.

2026-10-30: đưa PasswordInput dùng chung vào main cho login/register/reset/settings. Mỗi ô hiện/ẩn độc lập, mặc định che, button type=button và aria-label/aria-pressed; giữ value và constraints.

2026-10-30: Home thêm logo graduation-cap và icon tính năng, nền xanh nhẹ. Auth chuyển switch login/register xuống dưới CTA; logo riêng trên mobile, input16px/touch44px, giảm spacing theo viewport và giữ scroll khi màn hình ngắn.

2026-10-30: khôi phục vercel.json trên main: proxy /api và /sanctum/csrf-cookie trước filesystem/SPA fallback để deep-link reload không404. DB Aiven đang tắt; sửa routing không đủ chứng minh login hoạt động.

HTTP timeout60s cho API và CSRF theo hướng bounded request tham khảo NoteTatCa; không tự retry request ghi, giữ cookie/CSRF Laravel.

2026-10-30: AsyncForm bỏ dòng chữ Đang xử lý theo yêu cầu; giữ disabled và aria-busy trong lúc gửi.

2026-10-30: Home dùng session context để đổi login thành Vào lớp học và ẩn đăng ký khi authenticated. Login/register redirect /app (hoặc next hợp lệ) nếu có session; đợi session trước khi hiện form. Forgot/reset giữ luồng riêng.

## 2026-10-30 — Workspace gọn và preparation
IMPLEMENTED: bỏ avatar topbar/sidebar note/footer lặp; tạo lớp ở Classes, bỏ preview classes trên Dashboard; teacher metrics giữ lớp/buổi, counts duyệt/chấm nằm ở queue. Grid auto-fit phủ chiều ngang, panel nhỏ ghép cùng hàng, responsive1cột; pagination chỉ hiện khi nhiều trang. WorkspacePreparation tải dashboard/classes trang1/code ưu tiên theo private actor keys, progress theo task thật, retry lỗi; màn settings/calendar/notices/catalog tải code nền sau ready, unread/notifications trang1 prefetch. Không tải toàn bộ dữ liệu lớp, không lưu dữ liệu private vào localStorage, giữ invalidation/authorization. Admin bỏ qua preparation học tập.

## 2026-10-30 — Hồ sơ/sidebar

IMPLEMENTED: Settings cuối sidebar; desktop collapse lưu preference local; mobile drawer giữ focus trap. Bỏ bell/date topbar. Hồ sơ xem trước, sửa/hủy; gender/birthday optional và avatar preset5màu qua PATCH/me/profile (version/self-only); timezone thiết bị gửi khi save. Migration2026_10_30_160000 cần chạy ở backend trước sử dụng các field mới. Google login/register và upload avatar hiện disabled, chỉ UI chuẩn bị: CHƯA có OAuth engine/callback/token validation hay storage upload; cần cấu hình và triển khai tiếp, không gọi đã tích hợp Google. Không lưu ảnh/private profile vào localStorage.

2026-10-30: nút thu/mở sidebar desktop chuyển sang trái header, trước breadcrumb.

2026-11-01 — Bản tin: học sinh/giáo viên đều có composer trên published; hiển thị tombstone, xử lý lỗi gửi, retry giữ id/body. Teacher có publish draft, sửa nội dung/hạn, ghim/bỏ ghim, ẩn/hiện, archive; comment hide/unhide, xóa có confirm và bỏ nút trên tombstone. Comments phân trang20.

2026-11-01: notification announcement.published/comment.removed có nhãn Việt và link tab Bản tin. Form giữ ID bản nháp sau save để retry bước publish không tạo thêm draft, gửi expectedVersion khi sửa lại. Owner thấy nhãn hidden/hết hạn.

### Shell/theme — DEC-039 (2026-11-02)

IMPLEMENTED: Brand dùng chung Home/auth/workspace và favicon SVG; header đầy chiều ngang, sidebar ẩn/mở có lưu trạng thái desktop, drawer mobile có focus trap/Escape; menu avatar chỉ một vị trí với Settings/đăng xuất. ThemeToggle dùng useSyncExternalStore, localStorage không chứa dữ liệu tài khoản, theo OS khi chưa có lựa chọn; theme khởi tạo trước React render. Light/dark dùng tokens trong shared/styles/theme.css, hỗ trợ form/table/card/auth. Không đổi API/session, không bật Google hoặc upload ảnh đang chờ cấu hình.

### 2026-11-02 — Tinh gọn workspace và Settings

Theo yêu cầu chủ dự án: catalog dùng “Điều chỉnh”/“Xóa” (đỏ), menu đăng xuất đồng bộ thay native button mặc định do ActionButton có wrapper; dropdown/select theo theme. Settings chia Profile/Account bằng query tab có lưu qua reload; Profile giữ xem trước/chỉnh sửa, Account trình bày email/điện thoại/Google và form mật khẩu chỉ mở khi chọn. Google vẫn chưa khả dụng khi chưa cấu hình. Ẩn tiêu đề/lời giới thiệu chung tại dashboard/classes/calendar/notifications/catalog, giữ tên trang cho accessibility và action chính; giữ tiêu đề ngữ cảnh lớp/bài tập.

### 2026-11-02 — View/edit modal cho catalog và calendar

Yêu cầu trực tiếp chủ dự án: bỏ “Các buổi học” và hướng dẫn vào lịch từng lớp. Catalog full-width, nút thêm mở form; Điều chỉnh mở chi tiết read-only rồi nút Điều chỉnh mở form. Buổi học: Xem → chi tiết → Điều chỉnh, tạo/sửa trong modal. Footer Đóng/Lưu bên phải dưới. Modal native dialog portal có focus trap/Escape, khóa scroll nền, trả focus khi đóng; API/expectedVersion và lỗi form giữ nguyên. Browser fixture desktop/mobile PASS catalog view/edit/create/close/Escape và calendar view/edit, không gọi DB cloud. Typecheck/lint/build PASS.

### 2026-11-03 — DEC-040: lớp xem trước, học kỳ filter, bản tin dạng bài viết

IMPLEMENTED: CatalogHeader gộp tab/nút thêm cùng hàng ở desktop/mobile; tab lớp có padding và khoảng cách, bỏ mô tả/timezone lặp phía trên. Tổng quan lớp hiển thị read-only description/môn/học kỳ/timezone; nút Sửa teacher mở Modal với expectedVersion, đóng sau save thành công, giữ form khi lỗi. Học sinh không có form sửa. Danh sách lớp teacher có bộ lọc học kỳ lưu query URL, reset page khi đổi, giữ filter khi tìm/chuyển status; tải đủ trang catalog trong private query (không lấy catalog của giáo viên cho học sinh). API vẫn lọc phía server, không lọc riêng trang đang xem. Học kỳ optional theo DEC-010, không có hành vi tự archive theo ngày.

Bản tin là từng article full-width, body giữ xuống dòng và wrap URL dài; published mở comments/composer ngay bên dưới cho cả teacher/student, comment count theo totalItems và pagination có sẵn. Không đổi retry idempotency, tombstone, moderation, expiry hoặc permission API. Bỏ tiêu đề/hướng dẫn lặp attendance/teacher gradebook (giữ heading cho screen reader); export actions và bộ chọn tháng/nút tạo bố trí ngang, wrap theo viewport. Giữ tên lớp làm ngữ cảnh.

VERIFIED LOCAL: typecheck/lint, Vitest29, build PASS; browser Edge API fixtures 8 tổ hợp teacher/student × 1440/390px × light/dark kiểm catalog cùng hàng, filter học kỳ trang2 và request semesterId, read-only → modal save có version/Escape, gửi bình luận cả hai vai trò, không tràn ngang các tab schedule/attendance/grades. Evidence screenshot và script ở work/check-compact.cjs, work/compact-*.png, work/compact-verification.json (ignored, synthetic). Đây là kiểm frontend/contract bằng fixture; chưa browser real HTTP→DB hoặc cloud mới. Không commit/push/deploy trong lượt này.

### 2026-11-03 — Rà toàn bộ form theo DEC-041 (IMPLEMENTED)

Đã rà input/textarea/select/AsyncForm/prompt trong src/features và shared/app. Chuyển các form mutation còn inline sang Modal dùng chung: grade read-only + edit/publish riêng, assignment edit/materials, profile/password, attendance correction/config, class create/join, assignment create, announcement create/edit, student submit/re-submit và QR paste/apply. Inventory/ngoại lệ ở [09-ui-design](09-ui-design.md). Giữ nguyên bình luận trực tiếp bên dưới bài theo yêu cầu bổ sung. Modal không cho Escape đóng trong khi fieldset aria-busy=true; footer wrap. QR opensAt lấy lại thời điểm bấm mở cấu hình, không lấy mốc cũ khi đã đứng trên trang lâu. StudentSubmit giữ flow/file/note ở component cha để đóng/mở popup không mất idempotency của lần gửi đang chờ xác nhận. Không đổi API/backend/permissions.

VERIFIED LOCAL DEC-041: typecheck/lint/Vitest29/build PASS; Edge6scenario teacher/student/admin ×1440/390,24snapshots với API fixtures kiểm các modal, grade409 giữ draft, version/reason payload, Escape trong lúc lưu, nộp file tổng hợp và composer bình luận inline. Footer/QR Apply được lint/build cuối và browser kiểm sau thay đổi. Chi tiết/giới hạn xem worklog; chưa kiểm production auth/API/DB mới. Có quyền push theo yêu cầu trực tiếp phiên này.
