# 10 — Chạy local và chuẩn bị môi trường

> 2026-10-27 | CURRENT API phase2 | Backend attendance_api đã có; atd_react là folder rỗng, chưa chạy npm hoặc build React.

## Máy hiện tại

PHP8.4.27/Composer2.10.3/MySQL8.4.13 portable trong .tools, không sửa PATH toàn máy hoặc cài Windows service. PHP extensions phục vụ Laravel/MySQL đã bật. Laravel13.38.0/Sanctum4.3.3 và dev tools pin bằng composer.lock. Xem [API-CURRENT](API-CURRENT.md) cho scope, auth và mailbox; [SCHEDULE-ATTENDANCE](SCHEDULE-ATTENDANCE.md) cho lịch/QR/manual.

Từ root `D:\My Projects\AttendanceWeb` (PowerShell):

```powershell
.\scripts\mysql.ps1 -Action status
.\scripts\mysql.ps1 -Action start
.\scripts\artisan.ps1 migrate --no-interaction
.\scripts\artisan.ps1 serve --host=127.0.0.1 --port=8000 --no-reload
```

Chỉ chạy migrate với DB local mới của project đã xác nhận. Không dùng config/dump WebAttendance cũ. API local có /health/live, /health/ready, /docs/api và /docs/api.json. Dừng API bằng Ctrl+C tại terminal; dừng MySQL bằng `.\scripts\mysql.ps1 -Action stop`.

## Tác vụ nền

Terminal khác: `.\scripts\artisan.ps1 schedule:work` để tự chốt vắng mỗi phút. Lệnh một lần: `.\scripts\artisan.ps1 attendance:finalize`. Command/schedule đã đăng ký và được test; chưa có production cron/Windows service chạy tự động. Các API đọc attendance có fallback finalize overdue đúng scope khi scheduler chưa chạy.

Mail: queue notification verify/reset đã có; `.\scripts\artisan.ps1 queue:work` xử lý khi cần. MAIL_MAILER=array hiện không gửi mail thật. Phải cấu hình mailbox thử/SMTP được phép trước khi dùng email end-to-end; tests dùng fake notifications/token synthetic. Reverb/scanner/upload pipeline chưa cài, không chạy các lệnh tương ứng như thể đã có.

## Kiểm thử

```powershell
.\scripts\verify-api.ps1
```

Script vào đúng backend cwd, Composer validate, Pint, PHPUnit, dependency audit. .env.testing phải trỏ MySQL database attendance_test hoặc attendance_ci_test; test guard từ chối DB khác trước RefreshDatabase. Không SQLite fallback/migrate:fresh trên DB người dùng thật. Tests dùng synthetic fixtures; HTTP smoke có DB attendance_http_test riêng và CSRF thực.

## Clone/source ZIP trên máy mới

Artifact không kèm vendor/.tools/.env/DB data. Cần PHP8.4 phù hợp/Composer/MySQL, extensions theo lock, tạo DB và users riêng dev/test; composer install từ attendance_api. Chỉ copy .env.example nếu .env chưa tồn tại; điền secret local ngoài Git, chỉ key:generate cho file mới chưa có key. Không ghi đè .env hiện có hoặc đổi APP_KEY tùy tiện. MySQL port33306 là cấu hình portable trên máy hiện tại; máy mới dùng port thực của instance và chỉnh env, không tự dùng instance cũ.

Đã chốt một role/account và MySQL (DEC-009/011), không hỏi lại. Cloud/budget/domain Q-026 còn mở. Backend folder attendance_api, frontend atd_react đã tồn tại; frontend không có package.json/source/node_modules. Dev web/API sau này chọn cùng hostname và credentialed CORS/CSRF như API-CURRENT. Frontend implementation là nhiệm vụ sau.

## React local — 2026-10-27

Terminal1 MySQL + Laravel8000 như trên. Terminal2 từ root: `.\scripts\react.ps1 ci`, rồi `.\scripts\react.ps1 run dev`; mở http://127.0.0.1:5173. Hoặc cd atd_react rồi npm ci/npm run dev. Dùng cùng hostname127.0.0.1 cho frontend/API để cookie hoạt động. Không đưa password/token vào VITE_*. Chạy `.\scripts\verify-react.ps1` để typecheck/lint/unit/build.

Browser test dùng database attendance_http_test và file env riêng đã ignore, không .env dev. Nếu cần tạo credentials synthetic mới: `.\scripts\php.ps1 scripts/create-e2e-fixture.php`; helper chỉ cho database attendance_http_test, APP_ENV=http-smoke, MAIL_MAILER=array, password ngẫu nhiên và lưu work/http-credentials.json ignored. Helper không cấu hình database; chuẩn bị env đúng trước. Helper mới đã syntax-check, E2E đợt này dùng fixture đã có. API fixture8001 `--env=http-smoke`, frontend5173 cần `$env:API_PROXY_TARGET='http://127.0.0.1:8001'` ở terminal chạy Vite. Chạy npm run test:e2e, dùng Edge cài trên máy. Sau test dừng fixture, bỏ API_PROXY_TARGET và chạy lại frontend proxy8000. Raw traces có thể chứa session/file/QR, không chia sẻ.

Tài khoản bình thường phải đăng ký/xác minh email; MAIL_MAILER=array không gửi email thật. Không tự tạo tài khoản dev mặc định, không bỏ qua email verification hoặc Policy để trình diễn. Xem [FRONTEND-IMPLEMENTATION](FRONTEND-IMPLEMENTATION.md) và [React evidence](evidence/react-validation.md).
