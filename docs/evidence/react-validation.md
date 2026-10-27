# Kiểm chứng React — 2026-10-27

## Story và ranh giới

Giáo viên tạo lớp trong React → Laravel/MySQL riêng → học sinh xin vào và được duyệt → upload/nộp bài → teacher chấm/công bố → student thấy điểm → QR check-in/teacher close/manual correction. Session teacher/student ở hai browser contexts, dữ liệu synthetic. Không mock API trong luồng chính; chỉ cố tình hủy một response sau server đã nhận submission để kiểm retry cùng key.

## Kết quả cuối

- TypeScript/Vite build và ESLint PASS; 16 Vitest tests PASS.
- Playwright cuối PASS3/3scenario: teacher/student membership→assignment→lost-ACK retry→draft/publish/average→QR/close/manual correction; mobile login/register/redirect; dashboard counters và PUT notification read ACK. Không có pageerror trong luồng chính.
- Backend learning regression7tests/135assertionsPASS cho assignment filter/identity scope và studentName display-safe; [JUnit](phpunit-react-filter.xml).
- Full backend51tests/574assertions ở phase3 là bằng chứng lịch sử; không giả là full suite đã chạy lại sau frontend changes.
- OpenAPI79operations, GET /me/submissions nhận assignmentId để filter đúng bài và vẫn giới hạn actor/membership; listing bổ sung studentName, không email/password.

## Các vấn đề tìm được và đã sửa

1. clear toàn QueryCache sau login làm observer session đọc null và redirect lại login. Chỉ remove private queries, set session trên query hiện tại; E2E có logout/đổi teacher và truy cập class khác bị chặn.
2. key theo version trên form chấm làm remount sau refetch, mất thông báo ACK. Giữ form khi save cùng revision; revision thay mới remount bằng revisionId.
3. Screenshot đầu chụp khi calendar/list còn loading; lượt cuối chờ dữ liệu xong. Không dùng screenshot loading làm bằng chứng luồng hoàn tất.
4. FormData cần nhận submitter để phân biệt preview recurrence và create; không thao tác hidden DOM bằng timeout.

5. Roster snapshot cũ gửi expectedVersion0 sau student đã check-in dẫn đến409 đúng. Form sửa tay mới chốt version lúc mở, chặn mở khi đang refetch; test đợi kết quả Có mặt mới điều chỉnh. Không tự lấy version mới để ghi đè.
6. Browser wait /qr từng bắt nhầm qrcode module JavaScript; giới hạn matcher đúng API attendance-windows/.../qr. Select dùng accessible combobox role. Đây là sửa test harness, không đổi quy tắc QR.
7. Kiểm tra inbox phát hiện client dùng POST read trong khi route là PUT; đã sửa và thêm scenario kiểm ACK204 và unread count giảm.

## Giới hạn

Camera vật lý, quét ảnh QR thực ngoài máy, các mail verify/reset thật, production hosting/proxy, load/concurrency, trình đọc màn hình và WCAG audit đầy đủ chưa kiểm. Browser dùng Edge/Chromium, desktop1440 và mobile390; không suy ra Safari/Firefox đã PASS. Screenshot chỉ layout; quyền/data được kiểm bằng API thật trong scenario. ClamAV daemon và quiz/chat vẫn chưa có runtime nghiệm thu.

Không sao chép raw Playwright trace/network/credentials vào docs/ZIP vì có thể chứa session/QR/payload. Evidence tổng hợp và screenshot synthetic được giữ, source test được cung cấp; fixture env/credentials ở work riêng.

## Ảnh layout đã kiểm tra

[Dashboard desktop](react-dashboard.png), [bài tập student mobile](react-student-mobile.png), [login mobile](react-login-mobile.png). Tài khoản/lớp trong ảnh là fixture synthetic, không phải dữ liệu thật hay seed mặc định cho dev. Ảnh cuối chờ query hoàn tất, dashboard không tính các buổi cancelled trong lịch7ngày.

## Ma trận bằng chứng cuối

| Ranh giới | Kết quả | Bằng chứng |
| --- | --- | --- |
| UI render/responsive | PASS desktop/mobile theo viewport trên | screenshots +3Playwrightscenarios |
| Browser→API | PASS cookie/CSRF, multipart, PUT read | Playwright real HTTP; không mocked server |
| API→MySQL→response | PASS fixture riêng | membership, unique revision, grades, attendance được đọc lại |
| Response→UI | PASS | receipt sau retry, published average8.5, QR present→manual late, unread giảm1 |
| Types/layers/hooks | PASS | typecheck + ESLint layer rules/react-hooks |
| Application/HTTP/QR logic | PASS16tests | Vitest3files |
| Backend hỗ trợ React | PASS7tests/135assertions | phpunit-react-filter.xml |

Tổng hợp máy đọc: [react-results.json](react-results.json). Source E2E ở atd_react/e2e; raw JSON/trace trong work/test-results ignored, không đóng gói. `scripts/create-e2e-fixture.php` mới chỉ syntax-check trong đợt này; browser suite sử dụng fixture đã có từ giai đoạn API. Không gọi helper này là đã chạy nếu chưa invoke.
