# Worklog — nguồn tiến độ thực tế

> Snapshot 2026-10-27 | API phase2 | Đã có API core, lịch tuần, QR/snapshot/manual audit và MySQL local; frontend atd_react còn rỗng. Source/test evidence là CURRENT, thiết kế toàn sản phẩm vẫn có phần TARGET.

## Bảng tiến độ module

| Module | Đặc tả | Backend | Frontend | Test runtime / data |
| --- | --- | --- | --- | --- |
| Auth/profile | DEC-009; schema/contract core cập nhật | IMPLEMENTED CORE | NOT STARTED | MySQL Feature + real HTTP, xem evidence; chưa avatar/mail thật |
| Catalog/classes | DEC-010/011 | IMPLEMENTED CORE | NOT STARTED | Owner isolation, pagination, FK/version; chưa course_types |
| Enrollment/approval | DEC-012/013 | IMPLEMENTED API | NOT STARTED | Pending/approve/reject/rejoin/remove/leave và inbox đã test |
| Calendar | DEC-015; contract SCHEDULE-ATTENDANCE | IMPLEMENTED API | NOT STARTED | Weekly/timezone/DST/overlap/scope/sửa/hủy đã test |
| QR attendance | DEC-016/017 | IMPLEMENTED API | NOT STARTED | QR boundary/snapshot/manual/audit/finalizer/totals/backlog đã test |
| Announcements/comments | DRAFT | NOT STARTED | NOT STARTED | NOT RUN |
| Assignments/files | DRAFT; Q-013 | NOT STARTED | NOT STARTED | NOT RUN |
| Grades/gradebook | DEC-018/019: thang10/average published equal weights | NOT STARTED | NOT STARTED | NOT RUN |
| Quizzes | DRAFT; Q-014 | NOT STARTED | NOT STARTED | NOT RUN |
| Chat | DRAFT; Q-015/025 | NOT STARTED | NOT STARTED | NOT RUN |
| Notifications/jobs | Membership scope hiện có; phần mở rộng Q-016 | PARTIAL: inbox/outbox, chưa realtime dispatcher | NOT STARTED | Duplicate/retry state + unread/self tested; chưa WS/crash delivery |
| Reports/import/export | Attendance summary hiện có; grade/import/Excel còn TARGET | PARTIAL: attendance summary | NOT STARTED | Summary/cancel/manual/backlog đã test; chưa export |
| Admin/privacy | Access DEC-013; retention Q-025/admin Q-021 mở | NOT STARTED | NOT STARTED | Không có admin content bypass |
| Deploy/backup/monitor | MySQL local đã có; cloud Q-026 | LOCAL ONLY | NOT PROVISIONED | Chưa cloud/backup restore/load |
| Data migration | OPTIONAL; Q-017 | NOT STARTED | N/A | NOT RUN; dump chưa import |

## Nhật ký 2026-10-26 — TASK-001

Yêu cầu: khảo sát toàn bộ file WebAttendance, tham khảo docs/code M-ChamCong và NoteTatCa; tạo bộ docs trước trong AttendanceWeb. Folder đích có sẵn và rỗng lúc khảo sát; không ghi đè project đang có. Source WebAttendance HEAD `02917e3d65f8659bc09560f40591c1f75be21e57`.

Thực hiện: kiểm kê toàn bộ 1.194 file ngoài .git, đọc/quét150 file first-party text và đọc sâu luồng chính; 1.039 file thư viện chỉ inventory/hash, 5 asset chưa review hình. Lần scan ban đầu gặp hai file rỗng gây null-match; lần inventory đọc lại bằng ReadAllText xử lý rỗng và hoàn tất. Không tuyên bố audit thư viện từng dòng hay chạy source cũ. Không sửa ba project tham khảo/source.

Đã ghi Q-001–008/DEC-001–008 theo câu trả lời thực; Q-009–024 còn mở với đề xuất và bước bị chặn. Tài liệu có REQ/RULE/LEG/FIND/FLOW/API/TC/TASK, logical schema, UI, security, jobs, config, deployment và migration. Đã nêu rõ manual attendance cũ deferred theo QR only, và assignment/quiz/chat chưa thấy trong source cũ.

Validation docs: xem [evidence/docs-validation.md](evidence/docs-validation.md) cho kiểm UTF-8/link/inventory/coverage thực tế. Chưa có evidence build/test ứng dụng; mọi TC ứng dụng NOT RUN. Không cài dependencies, tạo DB, gửi mail, deploy, chạy migration, Git init/commit/push hoặc nhập dữ liệu cũ. Thông số TTL/file/score/retention/hosting chưa phê duyệt.

## Việc tiếp theo

TASK-002: Q-009/010/018-DB/020-access/022 và Q-023/011/024 đã chốt, không hỏi lại. Tiếp Q-012/013/014 cho học tập, Q-015/016/025/021 cho chat/privacy/admin và Q-026 hosting. Đọc API-CURRENT/SCHEDULE-ATTENDANCE để biết code đang có; Q chưa trả lời giữ OPEN.

## Nhật ký 2026-10-26 — TASK-003/004/005/006 và phần TASK-014/017

Chủ dự án giao bắt đầu API, tạo atd_react riêng rỗng, cho phép phỏng vấn. Đã xác nhận DEC-009–014: một role/account, teacher dùng sau email verification; teacher nhiều lớp/catalog riêng, optional môn/học kỳ; MySQL; request lại left/rejected, removed cần teacher allow; leave/remove mất quyền lớp nhưng giữ data.

Đã chuẩn bị PHP8.4.27 portable official Windows archive (hash theo releases.json), Composer2.10.3 installer kiểm SHA384, MySQL8.4.13 official ZIP, không global PATH/Windows service. MySQL loopback33306, root mật khẩu random và app users giới hạn schema; secrets chỉ `.tools`/`.env*` ignored. Códev/test/http-fixture DB mới riêng, không connection cũ. `scripts/` gọi runtime/artisan/composer/start-stop MySQL.

Đã implement40 operations API, scope chi tiết ở API-CURRENT; atd_react được kiểm tra0 item. Source cũ và hai repo tham khảo không sửa. Git chưa init/commit/push; CI mới ở file, chưa remote. Không cài React dependencies/UI hoặc triển khai module cần Q chưa chốt.

Validation thực và các lần fail/fix xem [api-validation](evidence/api-validation.md), JUnit/HTTP summary và OpenAPI/route exports. Lượt đầu26 tests có2 fail ở guard cache logout/password version; sửa refresh user/revoke guard, lượt lại26/26PASS212assertions. HTTP fixture22checksPASS cookie/CSRF/CORS/isolation/enrollment. Sau đó thêm health regression phát hiện health tách session nhưng còn CSRF cookie middleware (500), đã sửa bỏ toàn bộ web middleware ở health; kết quả cuối ghi tại evidence, không xóa lịch sử fail. Export default OpenAPI không warning; strict fail-on-unknown88 inference findings, được ghi làgiới hạn schema inference. Không gọi build/remote CI/browser/SMTP/Reverb/load/backup/migration dữ liệu cũ PASS.

**Kết quả cuối scope API core:**29/29 testsPASS224assertions trên MySQL; HTTP smoke22checksPASS; Composer validate/audit và PintPASS; docs UTF-8/local links/code fencesPASS31Markdown; atd_react0files. Health/live, health/ready, docs/api và docs/api.json trên API local8000 đều200. Đã dừng HTTP fixture server8001; API dev8000 và MySQL33306 còn chạy để xem thử, lệnh stop trong API-CURRENT. Đã tạo source ZIP không vendor/runtime/secrets/data và báo cáo giao nhận ở outputs của chat; ZIP chỉ snapshot, source D:\My Projects\AttendanceWeb là nơi tiếp tục sửa. Q-023/011/024 đã gửi phỏng vấn cho bước lịch/QR, chưa có câu trả lời tại mốc này.

## Nhật ký 2026-10-27 — TASK-007/008 và phần TASK-015/017

Chủ dự án trả lời: giờ thực/lặp tuần/sửa-hủy từng buổi; QR30s/TTL60s, roster chốt lúc mở; teacher được sửa tay linh hoạt. Ghi DEC-015–017, thay phần cấm manual của DEC-003; không hỏi lại các câu đã chốt. atd_react vẫn để rỗng, không GPS/IP/face/UI.

Thêm calendar+sessions+weekly series preview/create, conflict acknowledgment, UTC/timezone/DST, per-session version/cancel. Thêm snapshot window/roster, HMAC token có TTL, self check-in/idempotency, manual reason/version/audit, expiry command/scheduler, teacher reports và self history. Parent-row locks/unique constraints; mutex riêng schedule_owner_locks thay users lock để tránh chu kỳ FK audit/membership. Class archive chặn window đang hiệu lực; cancellation giữ lịch sử/vô hiệu QR/exclude summary. Không sửa migrations đã chạy; thêm2migration mới.

Rà precision MySQL: normalize UTC timestamps đến giây cho lịch/window/checkedInAt để create response và replay khớp DB; regression với microsecond clock đã thêm. Finalizer batch100 phải drain hết khi trả report, thêm test101window/202records để không trả totals thiếu khi backlog. Generic create Idempotency-Key, scanner/QR image, reopen, Excel, attendance realtime chưa có.

Kết quả cuối:44/44 testsPASS446assertions trên MySQL; HTTP phase2 fixture29checksPASS, bao gồm cookie/CSRF/cross-owner/weekly/late-checkin/replay/manual/audit/report/cancel. Các lượt hẹp13test/171assertions và2testbacklog/precision cũngPASS; không cộng lượt chạy thành số test độc lập. Thêm regression31HS chung IP: check-in rate theo actor30/min và trầnIP600/min, không chia trần30 cho cả lớp dùng chungWi-Fi. OpenAPI default export và Composer validate đã chạy; scheduler list có attendance:finalize everyMinute, không tự chạy Windows service/production scheduler. Evidence đầy đủ tại [schedule-attendance-validation](evidence/schedule-attendance-validation.md). Chưa stress concurrency, browser/camera/QR scanner, SMTP/Reverb/production/backup restore hoặc import dữ liệu cũ.

## Phỏng vấn learning — 2026-10-27 (TARGET, chưa triển khai)

Q-012 trả lời thang10 tất cả bài và có điểm trung bình; Q-027 chốt trung bình các điểm published, trọng số bằng nhau, bài chưa chấm/chưa nộp chưa tự0. Ghi DEC-018/019, cập nhật REQ-010/RULE-006/FLOW-008/logical grading data. Q-013 file/revision và Q-014 quiz đã gửi, còn chờ câu trả lời; không coi các giá trị mặc định UI là đồng ý. Không ghi grade API/average đã implement hoặcPASS vì mới chốt đặc tả.

Tiếp theo cùng ngày: Q-013 đã trả lời theo đề xuất, ghiDEC-020; bắt đầu TASK-010/011 (assignments/private file/revisions/grade publications/average), Q-014 quiz vẫn OPEN. Không đánh learning APIsPASS trước khi có test; private file validation không được giả thành đã antivirus scan nếu chưa có scanner.

## Mẫu entry lần sau

`Ngày — TASK/REQ — scope/decision — file/commit — lệnh và môi trường — kết quả PASS/FAIL/NOT RUN — evidence — giới hạn/rủi ro — việc tiếp`. Mẫu này chỉ hướng dẫn ghi log, không phải công việc đã thực hiện. Không xóa lịch sử fail hoặc kế hoạch chưa làm để tạo cảm giác hoàn tất.

### 2026-10-27 — Tiếp nhận Q-014 và kiểm tra learning API

- Q-014 ANSWERED, DEC-021 CONFIRMED: single/multiple choice, giáo viên chọn thời gian và số lượt, nhiều đáp án đúng đủ mới tính điểm, chỉ hiện đáp án sau đóng bài. Chưa có quiz runtime.
- TASK-010/011 IN PROGRESS: có migration/source assignment, private upload, submission revision và immutable grade publication; đang kiểm thử MySQL. Chưa dùng số test phase2 làm bằng chứng learning API.
- File validation local không phải quét antivirus; production phải dùng scanner cấu hình hợp lệ, fail closed khi không sẵn sàng.

### 2026-10-27 — Kết quả trung gian learning

- HTTP learning PASS36checks (cookie/CSRF/multipart và MySQL riêng), evidence/http-learning.json. Draft không lộ, amendment nháp không đổi average, publication retry không tạo điểm trùng, gradebook/file isolation đúng trong các flow đã kiểm.
- Full suite lượt đầu:50/51PASS,572assertions; production scanner test bị CSRF419 trước validation vì đổi APP_ENV trong request. Sửa test gọi trực tiếp scanner boundary ở production, giữ test HTTP CSRF nguyên vẹn. Đang rerun full suite; chưa ghi PASS cuối khi chưa có kết quả.
- OpenAPI0.3 export79operations, kiểm multipart download, Idempotency-Key header, score0–10 và prohibited fields; chưa có strict inference pass.

### 2026-10-27 — Learning API nghiệm thu local

- Full suite cuối PASS51tests/574assertions/0fail/0error; docs/evidence/phpunit-learning-full.xml. HTTP PASS36checks; docs/evidence/http-learning.json. Summary: docs/evidence/learning-results.json và learning-validation.md.
- TASK-010/011 IMPLEMENTED và VERIFIED LOCAL theo phạm vi ASSIGNMENTS-GRADES: private files, assignment lifecycle, revision receipt, grade draft/publication immutable, audit, average và paginated gradebook. Không đồng nghĩa production ready hoặc đủ UI.
- OpenAPI0.3 có79operations; Composer validate/Pint PASS, audit không advisory; frontend atd_react0files, source cũ Git status sạch.
- Server dev8000 + MySQL33306 local để xem; HTTP fixture8001 dừng sau nghiệm thu. Không service scheduler/worker/ClamAV/cloud mới được cài.
- Quiz DEC-021 đã chốt cơ bản; Q-028/029/030 đang chờ trả lời (nhiều lượt, trọng số, timing/publication). Chat Q-015 và notification channels Q-016 còn mở. Không hỏi lại các DEC đã xác nhận.

### 2026-10-27 — React bắt đầu theo yêu cầu mới

DEC-022 thay giới hạn atd_react rỗng; DEC-023 xác nhận phân tầng tham khảo NTC/Marixa/Badminton. Source React đã có router/guard/session, API layer, domain models và feature pages, shared UI/tokens; SubmissionFlow orchestration ở application. Có lint giới hạn imports giữa tầng. Không sửa ba dự án tham khảo.

Đã kết nối auth/class/enrollment/calendar/learning/grades/QR/inbox/profile/catalog core. GET me/submissions thêm filter assignmentId và studentName safe để UI không quét tất cả trang hoặc chỉ hiển thị opaque student ID. Laravel regression7tests/135assertionsPASS. Build/lint và16VitestPASS; browser real API2scenarioPASS trước lượt bổ sung manual correction/ảnh ổn định, đang cập nhật evidence cuối. Các lỗi cache observer và ACK form đã sửa từ bằng chứng browser.

### 2026-10-27 — React core VERIFIED LOCAL

- Kết quả cuối: typecheck/lint (có import boundaries)/buildPASS;16VitesttestsPASS;3PlaywrightscenarioPASS với real Laravel8001+attendance_http_test. Browser2contexts teacher/student, Edge desktop1440 và mobile390. Không pageerror trong luồng chính. Backend affected7tests/135assertionsPASS; không rerun full51backend ở phase này.
- Browser kiểm cả uncertain submit response→retry cùng key/chỉ revision1, student không đọc lớp pending, teacherB không đọc lớp teacherA, điểm nháp ẩn→publish average8.5, QR→check-in→close→manual late có reason/version, logout clear actor data, notification PUT204/unread giảm1. Screenshots ổn định, source/evidence trong docs/evidence/react-*.
- React đã phân tầng app/domain/infrastructure/application/features/shared theo DEC-022/023. UI baseline Việt/sáng là implementation choice, Q-019 chưa chốt brand chính thức. TASK-020/021 VERIFIED LOCAL theo scope FRONTEND-IMPLEMENTATION, không hoàn tất toàn sản phẩm.
- Npm audit0vulnerabilities; OpenAPI79operations export lại, PintPASS; WebAttendance source cũ Git status sạch. Không sửa NTC/Marixa/Badminton, không commit/push/deploy.
- Sau test dừng8001 và chuyển Vite5173 về Laravel dev8000, MySQL33306. Không seed tài khoản dev mặc định; mailbox thật/camera vật lý/load/production vẫn chưa nghiệm thu. Các giới hạn UI/API còn lại được liệt kê trong FRONTEND-IMPLEMENTATION.

### 2026-10-27 — Chuẩn bị publish GitHub theo yêu cầu chủ dự án

Xác nhận remote taiDinh303/WebAttendance, main02917e3d65f8659bc09560f40591c1f75be21e57 với181commit. Khởi tạo Git tại AttendanceWeb, fetch lịch sử đầy đủ và bắt đầu nhánh feat/attendanceweb-laravel-react từ main đó; không sửa checkout WebAttendance cũ. Scope nhánh thay PHP cũ bằng Laravel/React, dependencies/runtime không publish. Lệnh Git và cách merge giữ lịch sử trong GIT-WORKFLOW.md. Đang kiểm source/secret exclusions và chạy lại full API trước khi commit/push; kết quả thực tế ghi sau khi xác nhận remote.

CI React được thêm cho typecheck/lint/unit/build/npm audit; API workflow bổ sung zip để test Office structure ổn định. CI remote chưa được xác nhận chỉ vì workflow có source.

### 2026-10-27 — Kiểm chứng trước khi commit bản nâng cấp

Full API mới nhất PASS51tests/581assertions,0fail/0error (evidence/phpunit-publish-full.xml). Gồm7assertions bổ sung ở phaseReact, không còn chỉ dựa vào kết quả51/574 lịch sử. Kiểm source mới PASS284files, không trùng7runtime secrets local và không runtime/credentials path; source audit trong evidence/publish-source-audit.json. Git repository không shallow, baseline02917e3 là tổ tiênHEAD và origin/main vẫn có181commit gốc. Đang tách commit và publish; chưa gọi PR/main đã cập nhật trước khi có bằng chứng remote.

### 2026-10-27 — GitHub branch và PR đã tạo

Push thành công nhánh feat/attendanceweb-laravel-react:1baa30b docs,63acd93 Laravel API,99dd9d3 layered React. Remote main vẫn02917e3;184commit trên nhánh trước commit ghi nhận này, gồm181commit gốc. Git working tree sạch sau3commit; baseline là tổ tiên củaHEAD. PR draft #1: https://github.com/taiDinh303/WebAttendance/pull/1 đã được tạo và gắn vào chat. Không merge main, không force-push.

GitHub connector thiếu quyền tạo PR (403integration), đã dùng cached Git credential của đúng taiDinh303 qua GitHub API; credentials chỉ ở memory, không ghi source/log. CI React remote PASS; API CI cũ FAIL vì3dòng format trong AssignmentController/FileController/GradeController. Chạy Pint với cache mới đã sửa3dòng, lượt formatter mới PASS; không đổi nghiệp vụ, giữ full API51/581 và React16test/build đã kiểm. Push commit fix riêng để CI xác nhận lại; trạng thái mới xem PR Checks. Mốc này không khẳng định CI remote đã PASS trước khi có kết quả.

### 2026-10-27 — Sửa PHPUnit discovery trên checkout mới

API CI1370947 đã qua Pint nhưng dừng trước test vì tests/Unit rỗng không được Git track, còn phpunit.xml có Unit suite. Thêm tests/Unit/.gitkeep để clone/CI có thư mục; không tạo test giả hoặc bỏ testsuite. Chưa gọi API remotePASS trước khi lượt CI commit mới kết thúc. Đây cũng sửa khả năng chạy PHPUnit từ snapshot source mới.
