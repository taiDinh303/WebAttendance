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

### 2026-11-03 — Sửa nhãn tiến độ buổi học theo thời gian

IMPLEMENTED: API status=scheduled là trạng thái lịch chưa hủy, không chứng minh buổi còn ở tương lai. CalendarContent (lịch chung và lịch trong lớp) dùng sessionDisplayStatus: trước startsAt → Sắp diễn ra; startsAt≤now<endsAt → Đang diễn ra; now≥endsAt → Đã kết thúc. Giữ cancelled → Đã hủy ở mọi thời điểm và giữ nguyên trạng thái API, quyền/thao tác sửa/hủy/QR, không tự chốt điểm danh hoặc chuyển dữ liệu DB. Nhãn dựa trên instant UTC/offset so với đồng hồ thiết bị, cập nhật mỗi30giây và focus/visibilitychange. Nhãn chỉ là tiến độ theo thời gian, không xác nhận buổi học đã thực tế diễn ra. Kiểm chứng tại worklog; không gọi đã verified cloud.

### 2026-11-03 — Header không dịch ngang khi scrollbar xuất hiện/ẩn

IMPLEMENTED: root html dùng scrollbar-gutter:stable để giữ bề rộng viewport/layout giữa trang ngắn/dài và body scroll lock lúc mở modal. Fallback trình duyệt chưa hỗ trợ dùng html overflow-y:scroll. Không dùng width100vw hoặc bù padding cứng theo scrollbar hệ điều hành. Giữ nguyên header fixed và Modal lock/restore scroll. Kiểm chứng browser đo bounding boxes tại worklog.

### 2026-11-03 — Sidebar lớp và ghim linh hoạt

IMPLEMENTED: teacher sidebar lấy đủ các trang lớp active theo API actor-scoped, nhóm theo semesterId/chưa gắn; nhóm thu/mở và link vào lớp. Nút Ghim mở modal bật/tắt từng lớp hoặc cả nhóm học kỳ. Đây là ẩn shortcut, không archive lớp hoặc ngưng học kỳ. Lưu preference ID riêng theo actor trên trình duyệt, không lưu tên/nội dung lớp; không đồng bộ thiết bị. Thông báo chuyển ngay trên Cài đặt. Lint/typecheck/build PASS; chưa browser hoặc cloud verification lượt này.

### 2026-11-03 — Banner lớp và dialog ứng dụng

IMPLEMENTED frontend: banner lớp với hình nền hình học có sẵn,6màu và chọn ảnh JPG/PNG/WebP≤2MiB, preview/Hủy/Lưu. Preference local theo actor+class, ảnh dataURL chỉ lưu trình duyệt; CHƯA có upload server/chia sẻ banner cho học sinh/thiết bị khác. Không coi là hoàn thành tính năng banner dùng chung. Bỏ timezone khỏi overview/card và cụm giờ máy của bạn; không đổi chuyển đổi thời gian. Tất cả confirm/prompt trong src/features thay askDialog async dùng Modal (hủy không gọi action). Không override API browser. Lint37unit/build PASS; chưa browser kiểm các dialog/banner mới hoặc cloud.

### 2026-11-03 — Giao diện theo tài khoản, DEC-042

IMPLEMENTED: useAccountPreference dùng private actor/key React Query; chỉ đổi sau ACK, lỗi được hiển thị, chặn lưu trước GET thành công, expectedVersion khi PUT. ClassBanner chuyển từ localStorage sang server (màu/ảnh), AccountThemeToggle cho shell authenticated lưu sáng/tối, Shell lưu collapsed, SidebarClasses lưu hidden trên server. Guest theme vẫn theo trình duyệt/hệ điều hành. Không tự import các key localStorage trước đó hoặc ghi đè server. Preferences actor-specific; ảnh không chia sẻ với tài khoản khác. Refresh/relogin trên thiết bị mới tải server. Backend migration bắt buộc, không coi push là đã hoạt động cloud. Browser fixture hai context cùng account giữ màu banner, account khác default PASS. Backend evidence xem worklog.

### 2026-11-03 — Tab Cài đặt trong lớp

IMPLEMENTED: tab settings nhãn Cài đặt theo yêu cầu ngắn gọn; overview chỉ thông tin read-only. Sửa thông tin, quản lý mã tham gia, lưu trữ lớp (teacher) và rời lớp (student) chuyển sang tab Cài đặt; giữ role/version/confirm modal, bổ sung confirm tắt mã. Không thêm xóa lớp vì chưa có nghiệp vụ/API xóa. Lint/typecheck/build kiểm local; chưa browser/cloud verification mới.

### 2026-11-03 — Sidebar lớp học sinh

IMPLEMENTED: học sinh cũng dùng SidebarClasses và Ghim theo account_preferences. API classes sẵn có scope active membership, lấy đủ pagination lớp active. Student gom nhóm Đang theo học, không gọi catalog semesters vốn chỉ teacher có quyền; giáo viên vẫn nhóm học kỳ như cũ. Không mở quyền xem pending/removed hoặc catalog giáo viên. Lint/typecheck/build PASS; Edge API fixture student hiển thị lớp, bỏ ghim, context mới vẫn ẩn, bật lại và không request teacher catalog PASS. Checkbox controlled đợi server ACK, QA dùng click+wait thay uncheck kiểm tức thời. Chưa cloud/student real API kiểm mới.

### 2026-11-03 — Nhắc yêu cầu vào lớp tại Tổng quan

IMPLEMENTED: teacher overview có list notification full-width phía trên thông tin lớp, ghi Có n yêu cầu vào lớp đang chờ duyệt, link /members?queue=pending&page=1 mở thẳng queue. Dùng totalItems API join-requests scoped owner, không dùng số item trang hoặc unread. Chỉ query khi teacher+class loaded+overview; refresh30s và invalidation sau xử lý membership. Không hiện khi0, không gọi pending endpoint cho student. Lint/typecheck/build PASS; Edge fixture3pending kiểm nhãn, width bằng content main và link queue PASS (work/check-request-notice.cjs ignored). Chưa cloud verification mới.

2026-11-03: theo chỉnh vị trí của chủ dự án, dòng yêu cầu vào lớp chuyển xuống ngay dưới khung Thông tin lớp, ngoài detail-grid để giữ width100%. Build/typecheck PASS; không đổi query/quyền/link.

### 2026-11-03 — Thao tác cùng hàng thanh mục lớp

IMPLEMENTED: ClassToolbar context/portal đưa control tại panel đang mở vào slot cạnh tabs, không nhân bản state/form handlers. Assignment bỏ heading Bài tập của lớp, Members bỏ heading lặp; create assignment/announcement, calendar month+create, members segmented, teacher grades/attendance export cùng hàng nav khi đủ width. Flex wrap khi hẹp, mobile slot hàng riêng. Calendar ngoài class vẫn có fallback toolbar. Không đổi permission/API/form behavior. Lint/typecheck/build PASS; Edge fixtures6tab ×1920/390 kiểm alignment desktop, hàng riêng mobile, không overflow và không còn heading assignment PASS. Evidence work/check-class-toolbar.cjs ignored; chưa cloud verification.

2026-11-03 — Trang danh sách lớp: Tạo lớp học/Tham gia lớp chuyển vào cùng filter-bar, phải hàng desktop, xuống hàng riêng mobile. Bỏ totalItems text và header action rỗng, giữ heading sr-only. Không đổi dashboard count. Lint/build PASS; browser fixture1920/390 align/wrap/create modal PASS. Chưa live verification.

2026-11-03 — Bỏ heading/description hiển thị của JoinRequestsPage và CheckInPage student theo ảnh; giữ h1 sr-only, không khoảng cách header cũ. Không đổi nội dung trạng thái và thao tác trong panel. Lint/typecheck/build PASS, chưa browser/live mới.

### 2026-11-03 — Nhập lịch tay và tách preview/create

IMPLEMENTED: form tạo/sửa dùng ô text cho ngày/giờ, parser chấp nhận dd/mm/yyyy hoặc ISO date, giờ24h và giây; từ chối ngày không tồn tại/giờ sai. Lặp hằng tuần chuyển xuống nhóm checkbox, nhãn Chấp nhận trùng; không remount cả form khi đổi recurrence (giữ tên/phòng). Preview chỉ endpoint preview khi intent chưa phải create hoặc chưa có preview; Tạo lịch cần intent explicit+preview+modal xác nhận. Hủy luôn có ở footer; sửa field làm preview mất hiệu lực. Không còn thông báo thành công giả sau preview hoặc nhãn tạo hủy nếu trùng mâu thuẫn allowConflicts. Lint/typecheck39unit/build PASS; browser fixture nhập tay/toggle giữ tên/preview không create/hủy confirm và hủy modal không ghi PASS. Chưa real cloud API/DB mới.

### 2026-11-03 — Hủy hàng loạt buổi học

IMPLEMENTED frontend: teacher calendar có checkbox row (scheduled), chọn trang/chọn tất cả trong tháng qua đủ pagination/Bỏ chọn/Hủy đã chọn(n). Đổi tháng xóa selection; chọn tất cả disable thay tháng trong khi tải. Modal lý do3–500 và xác nhận số buổi, không hard-delete. Gọi API cancel hiện hành tuần tự từng session, giữ version/class scope và server guard attendance. Success loại khỏi selection; lỗi giữ lại, báo tổng thành công/thất bại, không tuyên bố atomic hoặc rollback cả batch. Lint/typecheck/build PASS. Edge fixture2trang chọn hết, đóng modal không mutation, confirm hủy2 PASS; fixture409 ở buổi thứ2 kiểm success1+error và giữ selection1 PASS. Evidence work/check-bulk-cancel.cjs/check-bulk-partial.cjs ignored; chưa HTTP DB/cloud mới. Không backend/migration đổi.

### 2026-11-03 — Thùng rác và tiến trình nền (DEC-043)

IMPLEMENTED: CalendarContent gửi status scheduled/cancelled phía server (pagination/filter khớp), teacher toggle Thùng rác, reuse selection đủ trang, action deleteSession trong trash, modal xác nhận vĩnh viễn và báo kết quả từng phần. Nút danger modal nền đỏ/chữ trắng cả disabled. Shared AsyncForm/ActionButton + account preference save dùng MutationStatus observer toàn cục, giữ promise chạy khi đóng/thu gọn modal/điều hướng, báo pending/done/error; không tự retry. Modal có Thu gọn ngoài fieldset để không phải đợi request mới thoát. Không tự coi click là lưu thành công. Đây là browser-session processing: đóng/reload tab có thể làm mất tiến trình/chuỗi request, KHÔNG phải queue bền vững server. Khi thu gọn form rồi lỗi, xem thông báo toàn cục và mở lại form để kiểm tra, không tự phục hồi draft của component đã unmount. Comment composer/upload flow đặc thù vẫn giữ trạng thái riêng; chưa chuyển tất cả thao tác thành durable background jobs.

VERIFIED LOCAL lint/typecheck39unit/build PASS. Edge fixture calendar/trash, select cancelled, danger contrast, delayed DELETE700ms + Thu gọn rồi hoàn tất/refresh PASS. Backend17tests236assertionsPASS, không cloud mới. Backend phải cập nhật trước dùng DELETE trên production.

Tiến trình ghi còn được theo dõi tại HTTP gateway cho mọi POST/PUT/PATCH/DELETE; composer/upload vẫn giữ logic retry/idempotency riêng nhưng đều có thông báo nền chung. Không báo thành công trước ACK, không tự retry hoặc cam kết tiếp tục khi tắt trình duyệt.

2026-11-03 — Bỏ Thùng rác theo yêu cầu mới, calendar row Xóa và Xóa đã chọn gọi DELETE trực tiếp; không bước cancel trước. Bỏ ô lý do vì đây là xác nhận xóa vĩnh viễn, không ghi giả lý do vào server. Backend bỏ yêu cầu status cancelled nhưng giữ owner/version/class active/history guards. ScheduleAttendanceTest18/239PASS; lint/buildPASS; Edge fixture scheduled direct-delete/no-trash/confirm/minimizePASS. Không xóa dữ liệu cloud thật.

2026-11-03 — Nhập ngày/giờ theo từng phần ở tạo/sửa lịch: tự phân cách, tự chuyển phần, giữ placeholder, chặn chữ; tháng13→12, ngày vượt giới hạn→cuối tháng theo năm nhuận, giờ/phút giới hạn23/59. Đổi tháng/năm điều chỉnh ngày; thiếu phần không submit. Preview vẫn không tạo, Hủy không ghi. VERIFIED local: lint/build,39unit và Edge fixture nhập03,30/,20 02, chặn chữ, clamp tháng/ngày nhuận, payload, preview/cancel PASS. Không API/DB đổi hoặc cloud verification.
