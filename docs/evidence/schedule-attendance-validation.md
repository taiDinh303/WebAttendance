# Kiểm chứng lịch và điểm danh — 2026-10-27

## Phạm vi và môi trường

API phase2 theo DEC-015–017, cwd PowerShell `D:\My Projects\AttendanceWeb\attendance_api`. PHP8.4.27/Laravel13.38.0/Sanctum4.3.3, MySQL8.4.13 portable loopback33306. `attendance_test` cho PHPUnit; `attendance_http_test` cho HTTP cookie/CSRF thật (APP_ENV=http-smoke), tách dev. Không DB/mail/dữ liệu thật, không import source cũ.

## Kết quả thực tế

| Kiểm tra | Lệnh / evidence | Kết quả |
| --- | --- | --- |
| Full suite | `php vendor/bin/phpunit --log-junit ../work/phpunit-schedule-final.xml` |44testsPASS446assertions,0fail/0error; [JUnit](phpunit-schedule-attendance.xml), [summary](schedule-attendance-results.json) |
| HTTP phase2 | `node work/http-schedule-smoke.mjs`, server8001 envhttp-smoke |29checksPASS; [HTTP summary](http-schedule-attendance.json) |
| Code style | `php vendor/bin/pint --format=agent` |PASS (repo chưa initGit nên full scope thay --dirty) |
| Composer | `composer validate --strict`, `composer audit --locked --format=json` |Metadata/lockPASS; không advisories ở lần chạy27/10; không thêm dependency phase2 |
| Migrations | `artisan migrate` trên dev/http fixture, Fresh DB ở Feature tests |Thêm2files mới, không sửa migration core đã chạy;6business tables + schedule_owner_locks |
| Schedule | `artisan schedule:list` |Có attendance:finalize everyMinute, withoutOverlapping; chưa chạy scheduler production |
| OpenAPI | `artisan scramble:export --path=../docs/evidence/openapi-current.json --no-interaction` |Export default không warnings; version0.2, route inventory59operations; strict inference chưa được xác nhận |
| Docs/React/legacy | UTF-8/relative-link/fence + empty-folder + gitstatus |Kết quả tại [docs validation](schedule-docs-validation.json); atd_react vẫn rỗng; WebAttendance clean |

15 Feature tests mới bao phủ: weekly/timezone/DST gap-fold, conflict acknowledgment, scope/parent/version, QR signature/rotation/TTL/late/close, snapshot/newly approved/removed, duplicate/precision giây, owner manual+reason+audit, không ghi đè manual, cancel giữhistory/exclude report, expiry idempotency,101windows/202records backlog và31HS chungIP không dùng chung actor quota. Full44 là29core+15mới, không cộng rerun thành số ca độc lập.

HTTP dùng cookie jars riêng teacherA/B/student và CSRF thật: create class/join/approve, weekly preview/persist, session/window/roster, late check-in+replay timestamp, cross-owner QR/report denial, student manual denial, no-CSRF correction419, close+manual correction preserving QR timestamp, before/after audit, summary và cancel/history. HTTP script cho allowConflicts=true trong synthetic fixture để rerun không bị lịch của lần test trước chặn; test overlap strict đã có Feature case riêng.

## Rà soát và sửa trước kết quả cuối

- Lượt module13tests/171assertionsPASS. Thêm assertions kiểm số tổng cụ thể và roster unmarked. Sau đó kiểm full42tests/399assertionsPASS.
- Phát hiện rủi ro precision: MySQL datetime giây nhưng create response có thể giữ microseconds. Normalize UTC giây và thêm regression clock02:00:00.123456Z, HTTP xác nhận replay timestamp không đổi.
- Phát hiện fallback finalize batch100 có thể trả report thiếu khi backlog>100. Thêm drainAllDue và regression101windows,202records/202audits. Lượt hẹp backlog/precisionPASS.
- Rà rate limiter: không áp trầnIP30/min cho cả lớp dùng chungWi-Fi. Check-in30/min theoactor và600/min theoIP; regression31HS cùngIPPASS. Full cuối44tests/446assertionsPASS.
- Rà lock order: bỏ users row lock trong schedule để tránh chu kỳ với FK actor/reviewed_by của audit/membership. Thêm migration schedule_owner_locks, serialize teacher schedule writes rồi class/session/window. Không coi review lock order là stress concurrency đã chạy.
- HMAC/token/window scoped; sửa tay chỉ snapshot owner, reason/version, giữ timestamp QR thật; không thêm GPS/IP hoặc cho student override. Class archive chặn active window; cancelled giữ records nhưng loại thống kê.

## Giới hạn

Chưa browser/camera/scanner/QR image, stress concurrent processes/load, SMTP/Reverb/attendance realtime, reopen window, Excel, production scheduler/cloud/backup restore hoặc import dữ liệu cũ. Token TTL có overlap, QR có thể bị chia sẻ; không chứng minh vị trí. Generic create Idempotency-Key chưa có; client không auto-retry tạo series/buổi nếu chưa giải quyết timeout. Strict OpenAPI phase1 từng fail dynamic inference, phase2 chỉ export default được kiểm; chưa đủ cam kết tự sinh client production không review.

Các evidence này là synthetic/current phase2. Các evidence phase1 ngày26/10 được giữ làm lịch sử; không ghi đè chúng thành kết quả phase2. Source thật tiếp tục ở D:\My Projects\AttendanceWeb; artifact ZIP chỉ snapshot không vendor/runtime/.env/data.
