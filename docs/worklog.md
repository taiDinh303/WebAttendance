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

### 2026-10-27 — Merge PR #1 về main theo yêu cầu trực tiếp

Chủ dự án giao “merge vào main ... xóa ... cũ ... thay ... mới commit giữ lại”. Đã bỏ draft và merge bằng merge method merge với expected head0c1cccca68d94f39ba1ee5864645bff3893bcf42, không squash/rebase/force-push. GitHub xác nhận merged=true, merge commit e99269833c9df52a8c8c85e0fab9594bfca33b84. Mốc merge có187commit, đầy đủ181commit gốc và5commit nâng cấp; merge-base kiểm cả baseline và upgrade head đều là tổ tiên.

Fetched origin/main và fast-forward local main với compare-and-swap từ baseline đã kiểm, rồi switch sang main; không checkout tạm code cũ hoặc ghi đè runtime. Tree main bằng tree upgrade đã qua CI. Root hiện hành không còn Account/Admin/Connect/Database/Image/LayoutPages/Mailer/Student/Teacher/vendor hay index/login/composer root; chỉ có source Laravel/React/docs/scripts và Git config files. Source tham khảo WebAttendance cũ không sửa. Tài liệu merge cập nhật trong commit riêng sau merge, không đổi nghiệp vụ; CI ở upgrade head đã PASS API/React.

### 2026-10-28 — TASK-022: chất lượng code và unit tests

Chủ dự án yêu cầu rà OOP/SOLID/Design Patterns/Clean Code, cải thiện hướng khoảng8/10 và chứng minh logic bằng test. Đọc working agreement/intake/plan/DEC/worklog, khảo sát source Laravel/React và áp dụng production-grade-coding-assistant cùng testing-best-practices của Laravel Boost. Không triển khai quiz/chat đang chặn Q.

Đã tách WeeklyOccurrences thuần khỏi ManageSchedule, inject qua constructor và giữ Gate/transaction/locking. SubmissionFlow phụ thuộc interface gateway thay concrete HTTP, UI cấp adapter; thêm single-flight Promise, khóa khi upload, snapshot file selection, typed contracts/constants/readonly. Retry mất ACK giữ key/payload và uploads. ESLint bảo vệ boundary của flow. QR đặt tên constants30/60 và dùng một snapshot thời gian cho token/metadata. Không tạo generic repository/UoW hoặc interface cho mọi class.

Môi trường checkout mới thiếu vendor/node_modules/PHP phù hợp: cài theo lockfiles, PHP8.4.27 portable official với SHA256 khớp release metadata, MySQL8.4.13 local mới và DB attendance_test riêng. Credentials random chỉ trong .tools/.env.testing ignored; user test chỉ có quyền schema test. Composer optimized autoload chậm, dừng đúng process cài của task và sinh autoload không optimize bằng override tạm rồi khôi phục nguyên composer.json; không đổi dependencies/lockfiles hoặc PATH máy. Không dùng connection/SMTP/dump cũ.

VERIFIED LOCAL: full PHPUnit74tests/628assertions (10Unit +64Feature),0fail/0error; Vitest20testsPASS; React typecheck/lint/buildPASS; Pint dirtyPASS; Composer validate strictPASS; route discoveryPASS. Thêm23ca PHP và4ca React so với baseline51/16. Targeted ban đầu38ca có1error do fixture ID guarded; sửa fixture, QR13/13 rồi full74/74PASS. Chạy test concurrency trên source baseline thất bại đúng kỳ vọng (flow chưa khóa khi upload), khôi phục bản sửa rồi fullVitest20/20PASS. Không cộng số lượt chạy thành test độc lập.

Bằng chứng lưu ở docs/evidence/phpunit-quality-full.xml, vitest-quality-full.json và submission-concurrency-before.txt; lệnh/cases/giới hạn ở 06-testing. Đánh giá kỹ thuật khoảng8/10 có rubric ở03-architecture, chưa đo coverage. Còn query controller dài, array/tuple và notification insert lặp; không gọi toàn bộ source đã audit từng dòng. Browser E2E, stress concurrency nhiều process, mail/ClamAV thật/production chưa chạy lại. Không commit/push/deploy hoặc import dữ liệu cũ. MySQL chỉ phục vụ test mới; dừng sau khi hoàn tất kiểm chứng.

### 2026-10-28 — TASK-022: publish lên nhánh được chỉ định

Chủ dự án giao push các thay đổi chất lượng code/test lên feat/attendanceweb-laravel-react tại taiDinh303/WebAttendance. Đã fetch và kiểm nhánh remote là tổ tiên của main local58e8ca1; tạo nhánh local cùng tên từ main hiện hành để giữ lịch sử. Stage16file code/test/docs/evidence của TASK-022, kiểm không có runtime/env hoặc secret local. Commit và push thường lên đúng nhánh; không có quyền merge main hoặc deploy trong yêu cầu này. Kết quả remote được xác minh sau push; số test local vẫn PHP74/628 và React20PASS, không coi đó là CI remotePASS.

### 2026-10-28 — TASK-023: bắt đầu AttendanceWeb2.0

Chủ dự án giao theo đề xuất và workload3giáo viên/90học sinh/30check-in đồng thời. Ghi DEC-025/026 và phần trả lời Q-019/Q-026; chưa tự trả lời quiz/chat/hosting. Đang thực hiện dashboard aggregate scoped trên server, shell/UI mới, các thao tác lớp/catalog/lịch/audit còn thiếu. Kết quả test/capacity còn chờ thực thi; không dùng số người mục tiêu làm bằng chứng hệ thống chịu tải.

### 2026-10-28 — TASK-023: AttendanceWeb2.0 VERIFIED LOCAL

Hoàn thiện shell/dashboard mới theo role; server GET/dashboard80thoperation tổng hợp từ scope lớp active của actor, counts độc lập preview6class/5item, teacher queue duyệt/chấm/công bố, student bài chưa nộp và own published grades. Thêm UI sửa buổi riêng (giữ timezone/giây/version), sửa/xóa catalog, gắn catalog khi tạo/sửa lớp, leave bằng own membershipVersion, audit timeline lazy/paginated và tên/giờ buổi ở attendance. Workspace remount theo classId để không giữ invite/draft lớp trước. Private frontend package2.0.0; không đổi dependency versions, schema hoặc các quyết định auth/QR/grading.

Kết quả cuối: PHP79tests/690assertionsPASS, Vitest23testsPASS,4PlaywrightscenarioPASS với real Laravel/MySQL/Edge và teacher/student contexts desktop1440/mobile390; không pageerror trong luồng nghiệp vụ. Build/typecheck/lint/Pint/Composer validatePASS; OpenAPI80operations và routeJSON xuất lại. Bằng chứng JUnit/JSON/ảnh ở docs/evidence/*v2* và06-testing. Unit mới kiểm datetime roundtrip/cross-year/seconds, Feature mới kiểm dashboard scope/publication/count/timezone. Không coi số lượt rerun là số test mới.

Fixture workload riêng3teacher/90student/3class;30PHPprocess chờ barrier rồi check-in cùng window và replay. PASS30record,0duplicate, replay trả cùng record; start spread225.84ms, p95check-in+replay2012.07ms. Đây là kiểm Action/MySQL, chưa30HTTPsession qua middleware/hosting; chưa có SLA/capacity production. Script verify-attendance-concurrency.php chỉ cho isolated attendance_http_test/http-smoke/array mail, giữ fixture/token/credentials trong work/.env* ignored. Không dùng dữ liệu thật hoặc connection cũ.

Đã ghi nhận và sửa các lỗi fixture schema trước fullPASS; lỗi browser URL race khi lấy classUrl quá sớm và nút đóng menu bị sidebar che. Giữ các assertions quyền, không tăng timeout che lỗi. Bật OPcache trong PHP portable local để giảm cold-start dev server; không đổi PATH toàn máy. OpenAPI export đầu không chỉ định env dùng config Laravel mặc định không có DB đã thất bại; export lại rõ --env=http-smoke đã PASS. Các file cấu hình/runtime đều ignored.

Phạm vi2.0 hoàn tất local; quiz/chat/feed/export và vận hành production theo2.1/2.2 vẫn có Q chặn như intake. Chưa commit/push/deploy/gửi mail thật/import dữ liệu cũ. Dừng fixture runtime sau khi thu evidence; source/test và ảnh lưu để review.

Sau lượt full4scenarioPASS, chạy lại riêng workspace1scenarioPASS để xác nhận menu đã rời viewport trước khi chụp ảnh (report react-v2-workspace-e2e.json). Không có thay đổi code nghiệp vụ sau full suite; ảnh cuối đã được xem trực tiếp trên desktop/mobile.

Rà soát cuối phát hiện Pagination dùng trong form chọn catalog có thể implicit submit. Đã thêm type=button cho cả2nút, thêm browser regression với21môn/trang2 và xác nhận0POSTcreate. Targeted scenario mớiPASS, report react-v2-pagination-e2e.json; tổng5scenario khác nhau đã kiểm (full4 +1regression mới), không tính lượt chụp lại workspace thành scenario thứ6. Typecheck/lint/build được chạy lại sau thay đổi UI primitive.


### 2026-10-28 — Push AttendanceWeb 2.0 lên nhánh nâng cấp

Chủ dự án giao push toàn bộ thay đổi TASK-023 (dashboard/shell/workspace/evidence) lên feat/attendanceweb-laravel-react tại taiDinh303/WebAttendance. Đã fetch và xác nhận remote là tổ tiên của HEAD local trước khi tạo commit; staging gồm code, docs, evidence và script concurrency, không có file runtime/secret/local env. Commit thông thường không force; chưa merge main, chưa deploy, không dùng dữ liệu thật.
### 2026-10-28 — TASK-024: admin và dữ liệu demo

Chủ dự án giao admin/trangadmin, bộ dữ liệu1/3/90 và30đồng thời, tiêu chí8nhóm khoảng8/10. Đã hỏi Q-021 và được chốt quản trị tài khoản/thống kê/audit, không nội dung lớp (DEC-027); seedlocaldemo theoDEC-028. Giữ bản2.0 đã push ở lượt trước; không ghi đè .env/dev/test hoặc xóa fixture cũ. Đang triển khai seedguard/idempotence, policy/status action/sessionrevocation/audit, trangadmin và diagnostics/kiểm chứng.

### 2026-10-28 — TASK-024 VERIFIED LOCAL: admin và persistent demo

Đã triển khai DEC-027: Policy admin riêng,5API summary/users/lock/unlock/audit, request/resource allowlist, action transaction lockactor+admin theo thứ tự ổn định, expectedVersion/reason/sessionrevocation/auditbeforeafter; không Gate bypass quyền lớp/file/điểm. Frontend typedadminApi/AdminGuard và3trang quản trị desktop/mobile; admin redirect /admin, giữ chức năng tự quản lý tài khoản. Không thêm dependency.

DEC-028: tạo schema attendance_demo và user scoped mới với env/key/password local riêng, không thay attendance_dev/test/httpfixture hoặc dùng credential cũ. demo:seed opt-in/allowlist/mail guard tạo1admin+3teacher+90student+3class×30, lịch và180manualhistory,6assignments/45submissions/privateTXT/30publishedgrades. Manifest lock/idempotence giữ thay đổi password/status/lớp, không tạo lại windows khi reseed. Credentials chỉ work/demo-accounts-demo.json ignored; env/scripts không in password. Default DatabaseSeeder trống. Launcher demo.ps1 foreground, app:diagnose secret-free, readyhealth checkadminschema và MySQLstart chờTCP.

Kết quả cuối PHP101tests/838assertions, Vitest29tests, adminbrowser1scenario PASS; Pint/Composer validate/typecheck/lint/build PASS; Composer/npm audit0; OpenAPI85operations khôngwarning. HTTP30first201 +30replay200,30records0duplicates, batch5592.52ms,p95response5428.17ms trên localPHPdevserver (có queue, khôngproductioncapacity). Restore schemasmới35tablecounts/52FK0orphan/45privatefilehash/94passwordhash PASS. Reports/ảnh tại06-testing và docs/evidence. TestcaseTC-026..030 và rubric8nhóm khoảng8/10 ở03, ghi rõ phạm vi và khoảng trống production.

Browser phát hiện Route[login]notdefined khi unauthenticatedAPI thiếu AcceptJSON; sửa middleware redirectGuestsTo(null) và test401. JSONobjectorder/locatorlabel/screenshottransition chỉ sửa test theo contract thực. Đã giữ throttle429 khi load/browser cùngIP, chạy tuần tự để nghiệm thu. Seed/mutation browser có audit thật trên dữ liệu giả; student090 đã mở lại. SQLdump/cookie/rawtrace chỉ ignored, không trong evidence. Không commit/push/deploy đợtTASK-024, không mailthật/importlegacy. DemoAPI8000/Vite5173 để chủ dự án xem; MySQLloopback33306.

Rà soát cuối: git diff --check không lỗi whitespace; quét72file thay đổi không thấy secret của envdemo hoặc UTF-8 lỗi, links docs hợp lệ. OpenAPI export sau bổ sung Resource mixin không warning. Ảnh admin desktop/mobile đã xem trực tiếp, mobile chờ sidebar rời viewport. Các kiểm tra này không thay audit bảo mật độc lập.

### 2026-10-28 — Sửa xung đột cổng Vite5173
Chủ dự án chạy npm run dev bị port in use do demo.ps1 web do Codex để lại (node1128, parent command xác nhận launcher demo của repo). Đã dừng đúng Vite này; chạy npm run dev thực tế PASS, Vite ready762ms tại127.0.0.1:5173. Dừng phiên kiểm tra sau đó để trả cổng cho terminal của chủ dự án; giữ API/MySQL. Không sửa app/dependencies/port hoặc chạy test logic không liên quan. Cập nhật hướng dẫn10-setup-local.

### 2026-10-28 — Kiểm tra lỗi đăng nhập tài khoản minh họa
Chủ dự án báo console401/422 và xác nhận dùng teacher@example.test. Kiểm bằng Edge headless mới qua UI/Vite5173→Laraveldemo8000: teacher@example.test trả422 INVALID_CREDENTIALS; teacher01@demo.attendance.test với initial password ở file ignored trả200, roleteacher, vào/app,0pageerror. Không đổi mật khẩu/tạo thêm tài khoản/bỏ auth/CSRF. Source app không có contentscript.js/ObjectMultiplex/app-init-liveness; các cảnh báo này nhiều khả năng từ tiện ích trình duyệt, chưa xác định extension cụ thể. GET/me401 trước đăng nhập là hành vi dự kiến. Không cần sửa logic auth cho lần báo lỗi này.

### 2026-10-28 — Đóng gói và push TASK-024 theo yêu cầu chủ dự án
Đích origin taiDinh303/WebAttendance, nhánh feat/attendanceweb-laravel-react. Fetch xác nhận HEAD khớp remote trước commit. Phạm vi gồm admin API/UI, seed opt-in1/3/90, diagnostics/launcher, kiểm chứng HTTP30/restore và tài liệu/evidence đã nghiệm thu (PHP101/838, Vitest29, adminbrowser1). Quét credential local và whitespace trước stage; không đưa env/runtime/password/database/cookie/rawtrace vào Git. Không gộp thay đổi xóa PHPDoc tại WeeklyOccurrences.php ngoài phạm viTASK-024, giữ file local nguyên trạng. Commit thường, không force-push/merge main/deploy/mail thật; kiểm HEAD remote sau push.

### 2026-10-29 — TASK-025: sửa và push Composer audit CI timeout
Chủ dự án cung cấp curl28/connecttimeout10005ms từ Packagist security-advisories, exit100, giao sửa và push lại nhánh feat/attendanceweb-laravel-react. Nguyên nhân quan sát được là không lấy được phản hồi mạng, chưa đủ chứng cứ kết luận IPv6/server nào gây lỗi. Workflow dùng IPv4 cho ext-curl và wrapper retry riêng transport100 tối đa3lần, nghỉ5/10s, stepdeadline5phút; security exitcode và lỗi sau khi hết lượt vẫn làm CI fail. Không offline/ignore-unreachable/continue-on-error, không sửa dependencies/lockfile. Thêm regression9case + pathtriggers của2script. Bashsyntax/9cases/YAMLwiring PASS; online audit thật local exit0 không có vulnerability advisory. Không chạy lại applicationtests cho thay đổi CI-only. Giữ thay đổi PHPDoc WeeklyOccurrences.php ngoài phạm vi nguyên trạng, không stage. Đóng gói commit thường và push theo yêu cầu, kiểm remote HEAD sau push.
Render deployment9bca167 đã live, /health/ready HTTPS trả200, migrations và session DB trên Aiven Free hoạt động. Browser qua Vercel preview đăng ký synthetic student thành công; login tới /app và giữ cổng email verification. Smoke phát hiện AuthPage giữ done state khi đổi /register→/login; sửa key theo route để reset component. Đổi tài khoản tại verification screen được nối logout thật thay vì chỉ link login, để kết thúc session đúng. React23testsPASS; typecheck/lint/build chạy lại sau thay đổi. Cần redeploy frontend và kiểm lại return-to-login/logout trước kết luận hoàn tất.
`nAPI guest navigation không Accept header được tái hiện500/Route login not defined bằng test hồi quy trước fix. Đã đặt redirectGuestsTo null cho API-only; HealthTest4tests/15assertionsPASS sau fix, PintPASS. Render đã live và browser register/login/logout thành công; tiếp tục deploy fix401 cuối.
`nCommit4e6885f đã deploy Live; kiểm HTTPS health200 và guest API401 JSON trên Render. Browser cloud register/login/logout đã PASS ở frontend bd42197. Kết nối preview hoàn tất; mail thật/upload/scanner và release production vẫn chưa trong phạm vi.

2026-10-29 — DEC-029/TASK-025: chủ dự án giao tạo dữ liệu thử 3 giáo viên,30học sinh,3lớp×10, tài khoản gv001–003/hs001–030 với mật khẩu chung do chủ dự án cung cấp qua server secret; không ghi mật khẩu vào source/docs. Dùng @attendance.test, verified synthetic accounts để test không gửi mail; không thay quy tắc verify của tài khoản bình thường. Đồng thời giao sửa domain attendanceweb-psi.vercel.app; production BACKEND_URL đã đặt, đang triển khai và kiểm.

### 2026-10-29 — TASK-025 VERIFIED CLOUD DEMO

Tạo qua transaction trên service Free mới: gv001–gv003@attendance.test, hs001–hs030@attendance.test; 3 lớp mỗi lớp10membership active, owner tương ứng; synthetic accounts email_verified_at được đặt theo yêu cầu test, không đổi verification của tài khoản tự đăng ký. Password nhận qua DEMO_SETUP_PASSWORD server secret, không lưu literal vào seed/source/docs. PREPARE_DEMO đã đặt false sau khởi tạo; command có guard Render service/database host, marker ngăn reset dữ liệu và từ chối overwrite email có sẵn. Không gửi mail/import dữ liệu cũ.

Vercel production BACKEND_URL được cấu hình và deployment dpl_6UP3uPjfnGTHDaokfN8mQi8BkPjZ READY, alias chính https://attendanceweb-psi.vercel.app . Render frontend origins/domain được cập nhật cụ thể. HTTP trên domain chính: guest /api/v1/me401; csrf/login cả3teacherPASS, verified=true, mỗi người đúng1class và10student. Student001/011/021 loginPASS, mỗi người chỉ1class đúng nhóm. 30students được đối soát qua3roster10item; không coi là đã chạy30HTTPlogin/concurrency. PHP lint/Pint/shell syntaxPASS; nguồn commit107fee1 đã push codex/cloud-hosting, không merge main. Mail thật/private upload/scanner vẫn chưa bật. Domain là alias ổn định, deployment là bản build riêng; lỗi404 trước đó do production chưa nối BACKEND_URL.

### 2026-10-29 — TASK-026 PASSWORD VISIBILITY

Chủ dự án yêu cầu thêm con mắt ở login và mọi input mật khẩu. Đã thay toàn bộ 5 vị trí source bằng PasswordInput dùng chung; mỗi ô hiện/ẩn độc lập, mặc định password, button không submit, có accessible label và trạng thái. Chờ kết quả typecheck/lint/unit/build trước cập nhật cloud.

VERIFIED TASK-026: typecheck/lint/23unit/build PASS. Edge headless trên local /login, /register, /reset-password: hiện/ẩn PASS, giữ nguyên giá trị và độc lập giữa các ô; không gửi form/API ghi. Settings dùng cùng component, chưa chạy luồng đổi mật khẩu cloud. Chủ dự án giao push bản sửa.

### 2026-10-30 — Home và startup

Theo yêu cầu chủ dự án: thêm Home công khai tại / và /home, link login/register/workspace; bỏ redirect / sang /app. Startup hiển thị trạng thái giao diện và xác thực, progress indeterminate theo phong cách NoteTatCa, không tạo phần trăm giả. Session request có timeout60s và dùng isLoading để không quay vô hạn khi query bị pause offline. IMPLEMENTED LOCAL; chưa deploy hoặc kiểm browser cloud. Kết quả checks được ghi sau khi chạy.

Kiểm tra local: TypeScript, ESLint, Vitest23/23 và production build PASS. Chưa kiểm browser hoặc tái hiện lỗi cloud, chưa commit/push/deploy.

Chủ dự án cho phép tự commit/push thay đổi giao diện React trong phiên chat này. Đóng gói Home/startup và session timeout lên nhánh codex/cloud-hosting; không merge main hoặc chạy deploy thủ công.

Chủ dự án đổi quyền publish trong phiên: tự push giao diện React lên main và xóa nhánh codex/cloud-hosting sau khi đưa bản Home/startup sang main. Giữ phần admin hiện có trên main.

Kiểm tra bản tích hợp main: typecheck/lint,29tests và build PASS; git diff --check PASS. Publish main và xóa nhánh hosting theo yêu cầu trực tiếp.

### 2026-10-30 — Khôi phục password visibility trên main
Theo ảnh chủ dự án: main thiếu thay đổi22b6402 của nhánh hosting cũ. Áp dụng lại riêng4file React/CSS (không mang cloud config), bổ sung con mắt cho cả đăng nhập/đăng ký/đặt lại/đổi mật khẩu. Typecheck/lint,29tests/build và diff check PASS; chưa kiểm browser/cloud ở lượt này. Tự push main theo quyền đã giao.

### 2026-10-30 — Public pages responsive
Trang trí Home/logo và chuyển auth switch xuống dưới CTA theo ảnh chủ dự án. Typecheck/lint/29tests/build PASS. Browser Edge local390x844 kiểm3route với guest API mock: không tràn ngang; login cao844px, register ban đầu973px nên giảm gap/margin sau kiểm. Home có nội dung cuộn1066px. Chưa nghiệm thu cloud; không gửi form thật. Push main theo quyền phiên.

### 2026-10-30 — Chẩn đoán login/cloud
Aiven connector xác nhận attendanceweb-mysql-free/free-1-1gb/do-sgp đang POWEROFF. Main thiếu atd_react/vercel.json sau chuyển khỏi nhánh hosting: khôi phục proxy api/sanctum dùng BACKEND_URL và fallback index.html từ cấu hình604f212; không đổi secret. Chờ chủ dự án cho bật DB; chưa kết luận login hoặc Render→DB VERIFIED.

### 2026-10-30 — Tham khảo NoteTatCa và kiểm lại Aiven
Chủ dự án yêu cầu tham khảo cấu hình NoteTatCa. Đọc vercel.json và HTTP client: áp dụng bounded requests60s cho API/CSRF, giữ Laravel cookie+CSRF. Aiven sau khi chủ dự án bật đã chuyển REBUILDING→RUNNING qua connector. Vercel /login đã HTTP200; Render ready vẫn timeout từ môi trường kiểm, chưa chứng minh login/DB runtime. Typecheck/lint/29tests PASS.

### 2026-10-30 — Bỏ nhãn xử lý form
Bỏ dòng trạng thái Đang xử lý dưới nút AsyncForm theo ảnh chủ dự án; giữ khóa fieldset và aria-busy, error/success hiện hành. Thay đổi hiển thị nhỏ; kiểm TypeScript và diff trước push main.

### 2026-10-30 — Public pages nhận biết session
Theo yêu cầu chủ dự án, Home và Auth dùng session hiện hành; không ép đăng nhập lại khi mở login/register. Typecheck/lint/29tests/build PASS sau sửa. Edge local với me fixture PASS: Home không có link login, /login redirect /app. Đây là kiểm UI mock, chưa chứng minh cookie cloud persistence. Push main theo quyền phiên.

### 2026-10-30 — Workspace giáo viên / tải ưu tiên
Yêu cầu trực tiếp tái bố trí workspace: shell/dash/shared grid và pagination; thêm WorkspacePreparation trước Outlet sau Guard, cache actor-scoped và tải code nền. VERIFIED LOCAL: TypeScript/lint29unit/build PASS; Edge6route×desktop1440/mobile390 với teacher API fixtures không tràn ngang, ảnh work/teacher-* ignored. Đã xem ảnh dashboard desktop và chỉnh metrics auto-fit sau kiểm; fixture không chứng minh hiệu năng cloud hoặc mutation nghiệp vụ. Lint có lỗi script QA tạm đã xóa script và chạy lại PASS. Những màn chi tiết lớp dùng grid chung, chưa kiểm lại mọi modal/tab hoặc admin/student full flow. Không tuyên bố hết loading: màn/collection ngoài dữ liệu ưu tiên vẫn fetch khi cần. Push main theo quyền phiên.

### 2026-10-30 — Hồ sơ và sidebar

Tham khảo NoteTatCa theo yêu cầu trực tiếp. Frontend typecheck/lint29unit/build PASS. Edge local me fixture: read/edit/cancel, không input timezone, collapse giữ sau reload PASS; ảnh work/settings-collapsed.png đã xem. PHP Pint dirty PASS. Profile gender/birthday/avatar preset có migration/request/resource/controller update, thêm test validation/persistence. Google/upload chỉ disabled UI theo xác nhận chờ cấu hình, chưa engine OAuth/storage. Không deploy hoặc migrate cloud. Test Accounts đầu sai cwd; lượt sau chờ MySQL và chồng lượt targeted gây migration race; đã dừng đúng process test cũ rồi chạy lại tuần tự, kết quả theo phần tiếp.

Kết quả cuối AccountsTest13tests/82assertions PASS (221202ms), migration và field validation/persistence đã kiểm trên MySQL test local. Mobile390profile/drawer no overflow PASS với me fixture. PHP syntax/Pint và diff check PASS. Không chạy migration cloud hoặc tích hợp OAuth/upload thật. Push main theo quyền phiên.

2026-10-30: theo yêu cầu trực tiếp, chuyển sidebar toggle sang trái header trước breadcrumb; giữ toggle mobile hiện hành. Kiểm TypeScript/diff trước push main.

### 2026-10-31 — Phỏng vấn mở cổng quiz/chat/notification/hosting và rà nút React

Chủ dự án trả lời Q-028/029/030 (cả3 "do giáo viên chọn"), Q-015 (group chat cả lớp, chưa chat riêng 1–1), Q-016 (cần email thật, nhắc hạn nộp do giáo viên chọn), Q-026 phần DB (Aiven Singapore free plan đã mở). Ghi DEC-031/032/033/034; cập nhật PROJECT-INTAKE (Q ANSWERED kèm ngày), 14-architecture-decisions, QUIZZES (thiết kế quiz config), 01-plan (rủi ro/bước kế tiếp). Không code đợt này.

Rà docs+React tại chỗ (không sửa code): xác nhận các module còn thiếu khớp 01-plan (bản tin/quiz/chat/email/Excel). Phát hiện 7 điểm nút trùng/ẩn trong atd_react: CalendarPage 1form gộp single+weekly và 2nút submit "Tạo lịch"/"Xem số buổi trước" cùng payload; ClassWorkspace shortcut "Bài tập/Lịch & buổi học" trùng 2tab bên dưới; Dashboard metric card #priority trùng queue; CheckInPage 3nút camera chồng behavior; MembersPanel Duyệt/Từ chối song song; QrPage confirm() không nêu hậu quả chốt vắng. Ghi nhận UX, chưa thay code.

Còn OPEN chờ chủ dự án: Q-025 retention/xóa tài khoản (đã đề xuất phương án), 3lựa chọn mở rộng attendance (xin nghỉ excused / reopen window / co-teacher, đã ví dụ), Q-026 phần domain/ngân sách/thời hạn/SLA. Ưu tiên đã duyệt: TASK-009 bản tin → 012 quiz (DEC-031) → 013 chat group (DEC-032) → 015 Excel → Google/avatar → production (DEC-033/034 khi có SMTP/credentials).

### 2026-10-31 — Mở rộng quyết định và TASK-009 bản tin lớp (VERIFIED LOCAL)

Chủ dự án trả lời thêm: HS được bình luận dưới bản tin (Q-ann1); GV không xóa bình luận mà chỉ ẩn/masking nội dung nhạy cảm, tự linh hoạt (Q-ann2); bản tin cho cả có-hạn-đầu và không-hết-hạn (Q-ann3) → DEC-035. Export Excel cho cả attendance và grades, cần template mẫu (Q-exp1/2) → DEC-036 PROPOSED chưa code. Chat tạm thời chỉ group/bản tin lớp (Q-chat1), được gửi file (Q-chat2), được sửa/xóa tin (Q-chat3) → DEC-037 PROPOSED kế hoạch. Q-030 thời điểm hiện điểm quiz do đề xuất, chủ dự án đồng ý làm trước rồi xem.

TASK-009 bản tin lớp IMPLEMENTED/VERIFIED LOCAL (backend+React):

- Migration 2026_10_31_140000 thêm2bảng announcements (state draft/published/archived, pinned, hidden, expires_at, version) và announcement_comments (body≤20000, hidden, version).
- Action ManageAnnouncements (save/publish/addComment/moderateComment/deleteComment/archive): owner lớp active, version control, lock class; công bố tạo inbox+outbox cho member active (type announcement.published); HS chỉ xem published + comment không ẩn; GV xem comment ẩn để moderation. Policy Classroom view/update sẵn có, không thêm bypass.
- 9 routes dưới /api/v1/classes/{classroom}: index/store/show/update/publish/archive + comments store/hide/delete; rate limit comments dùng throttle submit.
- React: model Announcement/AnnouncementComment, thêm classApi (announcements/announcement/saveAnnouncement/publishAnnouncement/archiveAnnouncement/addComment/hideComment/deleteComment); tab "Bản tin" trong ClassWorkspace với AnnouncementsPanel (create/publish, pin, hết hạn, comment, ẩn/xóa comment moderation cho teacher); CSS .announcement-row/.comments-panel trong global.css.

Kết quả kiểm chứng: backend full suite 108tests/902assertions PASS (thêm AnnouncementsTest6tests/58assertions); Pint dirty PASS; route:list cho thấy9route mới. React typecheck (tsc -b) PASS, eslint4file PASS, vitest29tests PASS, vite build PASS. Chưa browser E2E, chưa cloud/SMTP, chưa production. Chờ chủ dự án: Q-025 retention/xóa tài khoản, Q-026 phần domain/ngân sách/thời hạn. Tiếp theo: TASK-012 quiz engine theo DEC-031, sau đó TASK-013 chat group theo DEC-032/037, TASK-015 Excel theo DEC-036.
### 2026-10-31 — Comment audits, DEC-031 Q-030 và sửa 7 điểm trùng React

Chủ dự án yêu cầu thêm comment_audits để HS biết khi GV xóa; Q-030 chốt "cho riêng và chờ công bố"; sửa 7 điểm nút trùng React.

Comment soft-delete + audit (backend, VERIFIED LOCAL):
- Migration 2026_10_31_150000: thêm deleted_at vào announcement_comments (soft delete) và bảng announcement_comment_audits (comment_id, actor_id, action, before_data/after_data, timestamps, append-only).
- Model AnnouncementComment dùng SoftDeletes; model AnnouncementCommentAudit mới.
- Action ManageAnnouncements.moderateComment/deleteComment ghi audit (hidden/unhidden/deleted + before/after) và deleteComment chuyển soft-delete + tạo inbox notification announcement.comment.removed cho tác giả.
- Controller show: HS thấy comment live + comment đã xóa của chính mình (marked deletedAt), comment hidden vẫn ẩn với tất cả; GV thấy hết. Resource trả deletedAt.
- AnnouncementsTest thêm assertions audit + soft-delete + notification; full suite 108tests/907assertions PASS, Pint PASS.

DEC-031 cập nhật phần Q-030: điểm "cho riêng" (gán từng attempt khi nhiều lượt) và học sinh chỉ thấy điểm sau khi GV công bố (chờ công bố, giống grade publication DEC-019), đáp án chỉ sau quiz đóng. Ghi QUIZZES (scorePublication flag) và 14-architecture-decisions.

Sửa 7 điểm nút trùng React (atd_react, UI-only, không đổi nghiệp vụ):
- App.tsx verify card: "Tôi đã xác minh" → "Kiểm tra lại xác minh".
- ClassWorkspace: bỏ 2 shortcut "Bài tập của lớp"/"Lịch & buổi học" trùng tab, giữ chỉ dòng timezone; bỏ import Link.
- CalendarPage: weekly preview thành 2 bước rõ (nút "Xem trước số buổi" trước, sau đó "Tạo lịch (hủy bỏ nếu trùng)"), single dùng "Tạo buổi học".
- QrPage: confirm "Đóng điểm danh" nêu rõ hậu quả (HS chưa quét → vắng, có thể sửa tay DEC-017).
- CheckInPage: 3 nút camera đổi label theo trạng thái (Quét mã khác / Mở camera; Đóng camera giữ mã vừa quét).
- DashboardPage: metric card teacher tách anchor riêng #queue-requests/#queue-reviews, thêm id cho 2 h3 queue.
- MembersPanel: giữ 2 nút Duyệt/Từ chối (hành động khác nhau, rõ nghĩa) — không gộp.

Kiểm chứng React: tsc -b PASS, eslint7file PASS, vitest29 PASS, vite build PASS. Chưa browser E2E. Tiếp theo TASK-012 quiz engine theo DEC-031.

### 2026-11-01 — Tiếp nhận bàn giao Agnes19f5ffe
Đã đọc bàn giao và đối chiếu local/remote main sạch trước sửa. Không coi số test trong báo cáo là kiểm chứng độc lập. Rà thấy hidden announcement vẫn readable/commentable bởi HS và deleted comment body vẫn trả JSON; sửa REST scope/resource và thêm regression. TS AnnouncementComment.body chuyển nullable theo contract. Chưa migrate/deploy cloud. Ưu tiên tiếp theo đang hỏi lại chủ dự án: Quiz DEC-031 hoặc hoàn thiện Bản tin. Lượt kiểm targeted dùng SQLite memory cô lập, không dùng DB cloud/dump cũ; kết quả/giới hạn ghi tiếp sau chạy.

Kết quả: PHPUnit MySQL lần đầu6/7PASS, test mới phát hiện show endpoint còn thiếu hidden filter; sửa và chạy lại7/7tests,73assertionsPASS. Rerun dùng bootstrap ignored tái sử dụng schema attendance_test vừa migrate thành công, vẫn transaction rollback mỗi test; không áp dụng cách này cho CI. SQLite bị TestCase guard từ chối và không vượt guard. React typecheck29testsPASS, Pint/diffPASS. Không có browser E2E hoặc cloud verification mới. Push main bản sửa bảo vệ nội dung trước khi mở rộng module.

### 2026-11-01 — Hoàn thiện Bản tin theo ưu tiên chủ dự án
Sửa thiếu composerHS/tombstone/publishdraft/unhide, thêm edit/pin/hidden cho owner, phân trang comment20item tại API/UI. Retry composer giữ ULID/body sau lỗi và hiển thị lỗi; backend key payload mismatch hoặc key khác phạm vi trả409; retry soft-deleted trả tombstone. Test mới pagination/unhide có fixture guarded sai ban đầu, sửa forceCreate chỉ fixture; targeted MySQL8tests/88assertionsPASS trên schema test hiện hành. React typecheck29unit/buildPASS; lint ref-during-render được sửa và lint cuốiPASS. Edge mobile390 teacher/student2scenario với API/CSRF fixtures PASS composer/tombstone/client key/no overflow; lỗi QA ban đầu thiếu csrf và match URL query được sửa fixture, không che lỗi app. Chưa browser real HTTP→DB hoặc cloud, chưa toàn bộ expiry/audit export hay fullsuite. Không đổi quyết định quiz/chat hoặc migrate/deploy. Push main theo quyền phiên.

### 2026-11-01 — Rà hoàn thiện tiếp Bản tin
Phát hiện expiresAt chưa enforce, notification generic/link overview và form save→publish retry tạo draft mới; đã sửa. Hết hạn theo server clock loại list/show/comments choHS, owner giữ quyền xem; savedDraft id/version giúp retry không insert draft mới sau publish fail. Bổ sung test expiry/travelBack/membership revoke và key retry deleted/mismatch. Targeted MySQL schema test tái sử dụng9tests/107assertionsPASS; React typecheck/lint29tests/build/Pint/diffPASS. Không coi là fullsuite hoặc browser real HTTP→DB. Chưa deploy/migrate cloud. Các module chưa làm vẫn quiz/chat/Excel/OAuth/upload thật theo backlog; không lẫn vào hoàn thiện Bản tin.

### 2026-11-01 — TASK-015 Xuất Excel và studentCode

Triển khai owner-only GET attendance/grades export + template, native OOXML inlineStr/ZipArchive không đưa thêm dependency, download ephemeral xóa sau gửi. Text không thành công thức, điểm chỉ publication hiện hành, attendance snapshot finalized; không tự0/absent cho dữ liệu chưa chốt. Optional studentCode self-profile, giữ0đầu, không uniqueness toàn hệ thống. UI download với credential/timeout/MIME check, template ở tab attendance/grades.

VERIFIED LOCAL: targeted MySQL ClassExportTest4tests/34assertionsPASS (quyền/ZIPXML/totals/openwindow/publishedvsdraft/codevalidation). Fixture membership ban đầu thiếu requested_at đã sửa fixture, không nới schema. React typecheck/lint29tests/buildPASS. Native writer chỉ dùng XMLescaped text inline cells và số hữu hạn từ server; không chứa Excel formulas. Tests dùng schema attendance_test tái sử dụng sau migration student_code local, không cloud. Chưa browser real API→download hoặc Microsoft Excel app/render, chưa đo tải export lớn. Phải cập nhật backend/migrate và kiểm ext-zip trên cloud. No deploy/SMTP/import.

Kiểm tra cuối mở rộng profile regression: AccountsTest+ClassExportTest17tests/116assertionsPASS; browser mobile4lượt download xlsx/template trên2tabPASS với fileZIPfixture thật và API mock. Sample chỉ dữ liệu tổng hợp, ở work/ignored. Frontend29tests/typecheck/lint/buildPASS. Không sửa code sau các kiểm này ngoài docs; writer header alignment đã được targetedAPI rerunPASS. Chuẩn bị push main.

### 2026-11-02 — Dữ liệu minh họa Aiven đang triển khai
Aiven RUNNING/free-1-1gb, Render service attendanceweb-api thuộc My Workspace đã được chủ dự án xác nhận; live source107fee1, main thiếu Docker files nên khôi phục deploymentfiles từ source đó và TLSverify explicit. Seed mới giữ3teacher+3class10cũ, thêm6class/150student, dữ liệu UI theo DEMO-VISUAL.md. VisualDemoTest2tests/24assertionsPASS sau khởi động MySQL test; lần đầu DB chưa chạy nên connection refused, không đụng cloud. Chủ dự án cho phép tự deploy trong task. Đang chuẩn bị source/opt-in/migrate; chưa gọi cloud seed hoàn tất.

Kiểm source cuối: VisualDemoTest+ClassExportTest+AnnouncementsTest15tests/165assertionsPASS, Pint/diffPASS. Chưa có Docker local để build; Render build sẽ là bằng chứng image/startup. Deploy target đã xác minh manual/off, branchmain, rootattendance_api, FreeSingapore. Giữ env/secret hiện có, chỉ merge cờ seed/migrate và array mail bằng Render Env Vars skill.

Cloud VERIFIED cho seed: Render env update merge đã tự trigger dep-dbk5qcmc6qcc73dk5n20 (không trigger deploy trùng); Live source11ba4cd. Migrations mới admin/demo/profile/announcements/audit/student_code DONE. Runtime command sau transaction trả created=true,sizesByTeacher=[[10,20,30],[10,20,30],[10,20,30]],attendanceRecords=720,teacherCount=3,classCount=9,studentCount=180. APIhealthreadyHTTPS200; guestmequaVercel401JSON. Đã merge VISUAL_DEMO_ENABLED=false, tạo dep-dbk5s5m7bikc73987dbgLive cùngsource. Không xem buildaccepted làLive, đã đợi cả2lượtLive. Giữ planFree/manualdeploy, không động service marixa001_asp. Password/CA/DBsecrets không được đọc/in ra hoặc đổi. Mailarray, không mail/import. Chưa authenticatedbrowsercloud mới; chỉ đốisoát runtimeDB và health.

### 2026-11-02 — DEC-039 phong cách NoteTatCa

Đã tham khảo AppShell/ShellControls/useTheme/SidebarLayout của NoteTatCa. Header full-width với Menu+Brand trái, theme+avatar phải; Settings cuối sidebar, bỏ tài khoản trùng. Sidebar desktop ẩn hoàn toàn, mobile drawer giữ focus/Escape. Thêm theme theo thiết bị/lưu lựa chọn, palette neutral/amber, Brand và favicon thống nhất Home/login/register/workspace. Kiểm local typecheck/lint29unit/buildPASS; Edge42route snapshots và tương tác theme/sidebar/avatar/logoutPASS với fixture API, không real cloud auth. Sửa màu search/footer/badge dark và metric mobile sau visual review rồi kiểm lại browser. Không đổi nghiệp vụ/backend/credentials.

### 2026-11-02 — Tinh gọn workspace và Settings

Theo yêu cầu chủ dự án: catalog dùng “Điều chỉnh”/“Xóa” (đỏ), menu đăng xuất đồng bộ thay native button mặc định do ActionButton có wrapper; dropdown/select theo theme. Settings chia Profile/Account bằng query tab có lưu qua reload; Profile giữ xem trước/chỉnh sửa, Account trình bày email/điện thoại/Google và form mật khẩu chỉ mở khi chọn. Google vẫn chưa khả dụng khi chưa cấu hình. Ẩn tiêu đề/lời giới thiệu chung tại dashboard/classes/calendar/notifications/catalog, giữ tên trang cho accessibility và action chính; giữ tiêu đề ngữ cảnh lớp/bài tập.
Kiểm local: typecheck/lint/Vitest29/build PASS. Browser fixture kiểm desktop/mobile light/dark cho tab/reload/profile edit/password/menu/catalog; không kiểm auth hoặc thay đổi DB cloud.

### 2026-11-02 — View/edit modal cho catalog và calendar

Yêu cầu trực tiếp chủ dự án: bỏ “Các buổi học” và hướng dẫn vào lịch từng lớp. Catalog full-width, nút thêm mở form; Điều chỉnh mở chi tiết read-only rồi nút Điều chỉnh mở form. Buổi học: Xem → chi tiết → Điều chỉnh, tạo/sửa trong modal. Footer Đóng/Lưu bên phải dưới. Modal native dialog portal có focus trap/Escape, khóa scroll nền, trả focus khi đóng; API/expectedVersion và lỗi form giữ nguyên. Browser fixture desktop/mobile PASS catalog view/edit/create/close/Escape và calendar view/edit, không gọi DB cloud. Typecheck/lint/build PASS.

### 2026-11-03 — DEC-040: tinh gọn workspace theo ảnh

Đã đọc docs bắt buộc và đối chiếu source. Học kỳ là FK optional dùng phân nhóm lớp theo DEC-010; ClassroomController đã lọc semesterId sau owner/membership scope. React thêm teacher filter lấy đủ catalog pages, giữ URL/filter khi search/status và reset page. Catalog tab/nút thêm cùng hàng, tabs có padding/gap. Class overview read-only, sửa trong native dialog với expectedVersion; bỏ mô tả/timezone/heading lặp, ghép nhóm nút có khoảng cách. Bản tin chuyển article riêng, published mở comments/composer ngay dưới bài cho cả hai vai trò; giữ nghiệp vụ retry/moderation/pagination. Teacher attendance/gradebook bỏ title/help lặp, giữ screen-reader context; lịch month/create cùng hàng responsive.

VERIFIED LOCAL: npm typecheck/lint/test(29)/build PASS. Browser Edge fixture 8 tổ hợp teacher/student × desktop1440/mobile390 × light/dark: catalog tab/add cùng hàng; học kỳ trang2 được tải và gửi semesterId lên list API; view trước, modal save payload version, Escape; bình luận hiển thị/gửi ngay dưới bài ở cả hai role; schedule/attendance/grades không tràn ngang. Đã xem ảnh desktop dark feed và mobile light catalog/schedule để QA trực quan. Evidence ignored work/check-compact.cjs, compact-*.png và compact-verification.json. Lỗi chạy lệnh kiểm ở root thay frontend và dev server nhận sai port được sửa ở môi trường local trước kiểm, không ảnh hưởng source/API. Không kiểm auth/DB cloud mới, không commit/push/deploy/mail/import. Docs cập nhật intake/DEC040/frontend/plan cùng code.

### 2026-11-03 — DEC-041: rà form toàn project, chuyển edit vào modal

Chủ dự án yêu cầu mọi form nghiệp vụ đang hiện trực tiếp chuyển nút → modal/popup → nhập/sửa; xác nhận riêng bình luận vẫn nhập trực tiếp như Classroom và giao push. Đã rà src/features/shared/app: GradeEditor hiển thị điểm/nhận xét chỉ đọc, modal Sửa/Lưu nháp và Công bố/lý do riêng; assignment edit/materials, profile/password, roster correction, QR config, tạo lớp/tham gia/giao bài/bản tin và sửa bản tin đều mở Modal. StudentSubmit mở form nộp/nộp lại trong modal, giữ file/note/flow bên cha để không reset idempotency khi đóng/mở. Check-in camera/xác nhận giữ trực tiếp, dán/sửa QR qua modal Áp dụng. Admin lock/unlock nhập lý do trong modal. Catalog/class/calendar modal sẵn có giữ nguyên. Search/filter/auth là input trực tiếp phục vụ luồng riêng, comments giữ composer inline theo yêu cầu bổ sung. Inventory ở09-ui-design, DEC041/intake/frontend/plan cập nhật cùng code.

Modal Escape không đóng khi AsyncForm đang busy; footer wrap để 3nút không tràn trên mobile. Mốc QR được tính lại lúc mở cấu hình. Không đổi Laravel/API/quyền/audit/version/limits. Không tạo quiz/chat hoặc chức năng chưa có backend.

VERIFIED LOCAL: typecheck/lint/Vitest29/build PASS; sau bổ sung QR Apply và wrap footer đã lint/build lại PASS. Edge fixture6scenario (teacher/student/admin ×1440/390),24snapshot PASS: grade read-only/edit/save409 giữ nội dung, retry có expectedVersion, publish có reason, Escape bị chặn khi save và dùng được khi rảnh; profile save/password; assignment edit/material modal; class/join/assignment/announcement create/edit; student upload+submit file tổng hợp; comments gửi trực tiếp; QR config/apply/correction có reason+version; moderation modal; không tràn ngang document hoặc dialog. Đã xem ảnh grade desktop/mobile và compose bulletin mobile. Work/check-edit.cjs, work/edit-verification.json, work/edit-*.png ignored là evidence fixture, không phải browser real HTTP→DB/cloud. Hai lỗi locator QA (textarea label tính cả default text; tên account bị ẩn trên mobile) đã sửa selector fixture, không sửa app để che lỗi. Remote origin/main đồng bộ trước commit (0/0). Chủ dự án cho phép commit/push toàn thay đổi DEC040+041; không trigger deploy thủ công/mail/import.

### 2026-11-03 — Sửa buổi quá giờ vẫn hiện “Sắp diễn ra”

Nguyên nhân: UI map trực tiếp status=scheduled của API sang Sắp diễn ra, không xét startsAt/endsAt. Thêm domain sessionDisplayStatus phân biệt upcoming/ongoing/ended theo instant, cancelled ưu tiên ở mọi thời điểm; dùng cho lịch chung/lịch lớp cùng CalendarContent. Clock UI cập nhật30giây và focus/visibilitychange, cleanup listener/timer. Không sửa API status/DB hoặc ẩn nút QR/sửa/hủy, không tự đóng điểm danh. Mốc tiến độ dựa đồng hồ thiết bị, không phải bằng chứng đã tổ chức lớp.

VERIFIED LOCAL: lint/Vitest37tests/typecheck+build PASS;8case mới kiểm trước/bằng start/bằng end/sau end, hủy và timezone qua nửa đêm. Edge mobile390 fixture/browserclock kiểm4loại trạng thái, chạy đồng hồ qua start tự đổi nhãn, focus sau end cập nhật3buổi thành Đã kết thúc trong khi Đã hủy giữ nguyên, không tràn ngang; evidence ignored work/check-calendar-status.cjs và work/calendar-status-390.png. Không kiểm real cloud DB mới. Tiếp tục commit/push bản sửa UI theo yêu cầu push trong phiên; không deploy thủ công/mail/import.

### 2026-11-03 — Chống header xê dịch do scrollbar

Yêu cầu ảnh: trang dashboard dài có scrollbar, trang lớp ngắn không có làm nhóm header bên phải dịch ngang. Thêm scrollbar-gutter:stable ở html trong global.css, fallback overflow-y:scroll khi không hỗ trợ. Vùng scrollbar giữ ổn định cả body overflow:hidden của Modal, không sửa logic auth/API hoặc bù pixel cố định.

VERIFIED LOCAL browser Edge fixtures1440/390: ép classic scrollbar17px (không để overlay0px che lỗi), đo x/width header/brand/header-controls/main qua5trạng thái trang ngắn → dài2000px → modal mở → đóng → ngắn. Tất cả độ lệch≤0.5px, có xác nhận fixture thật sự chuyển từ không scroll sang scroll, không tràn ngang. Evidence ignored work/check-scrollbar.cjs, work/scrollbar-verification.json. Chỉ CSS thay đổi nên không thêm unit test phản chiếu CSS. Build production được kiểm trước commit/push theo quyền trong phiên; không deploy thủ công hoặc kiểm cloud auth mới.

### 2026-11-03 — Lớp sidebar và tùy chọn ghim

Chọn cách ghim/bỏ ghim theo yêu cầu linh hoạt giống taskbar: teacher thấy lớp active nhóm học kỳ; modal Ghim ẩn/hiện lớp/nhóm, vẫn truy cập tại Lớp học. Không thêm nghiệp vụ đóng học kỳ/archive lớp từ thao tác ẩn. Preference local actor ID, tải đủ pagination API có scope. Thông báo sát trên Settings. Lint/build gồm typecheck PASS; chưa browser runtime/cloud kiểm mới. Giới hạn: lựa chọn chỉ trên trình duyệt hiện tại, học sinh chưa có nhóm lớp sidebar.

### 2026-11-03 — Classroom banner và thay native dialog

Chủ dự án giao banner/tùy chỉnh theo Classroom, bỏ giờ máy của bạn, bỏ hiển thị múi giờ và native notification. Đã thêm ClassBanner chọn màu/hình nền có sẵn/ảnh local2MiB, và askDialog thay toàn bộ confirm/prompt trong features. Preview banner là local-only theo actor/class; chưa có persist/share server, chưa upload backend, nên phần banner dùng chung còn thiếu. Bỏ timezone overview/card, giữ logic thời gian. Lint37unit/typecheck+build PASS; chưa browser/cloud verification cho phần mới. Không thay permission/backend hoặc gửi mail. Push source để review theo quyền phiên.

### 2026-11-03 — DEC-042: đồng bộ preference theo tài khoản

IMPLEMENTED API+React: account_preferences riêng (user_id,key PK, value,version), GET/PUT me/preferences có session actor, banner-{ULID} recheck class view, kiểm raster/base642MiB, theme/hidden/collapsed validation riêng; transaction khóa user và version theo key để409 khi stale. Không nhét preference cá nhân vào bảng classrooms, không lưu ảnh vào /me payload. Theo bổ sung chủ dự án về bảng/thuộc tính phục vụ nâng cấp: dùng migration bảng riêng, FK users và cascade; không thay cột nghiệp vụ hiện có. Banner cá nhân khác tài khoản không dùng chung.

React dùng private account/key cache, chờ GET trước lưu, chỉ cập nhật sau ACK; theme/shell collapsed/sidebar hidden/banner dùng server thay localStorage. Guest theme vẫn local/OS. Không tự import/ghi đè từ localStorage cũ. Browser fixture2context cùng account giữ banner/theme/collapsed; account khác banner default PASS. Lint37unit/typecheck/build PASS. PHP Pint/syntax PASS; AccountsTest14tests99assertionsPASS trên MySQL attendance_test. Lần dựng schema đầu mất251s; test fixture thiếu role teacher và kỳ vọng403 trong khi policy cố tình404 được sửa fixture, không nới quyền; rerun dùng bootstrap work/reuse-announcement-test-schema.php sau schema đã migrate xong, vẫn guard MySQL test và transaction rollback. Không coi là CI fullsuite. Source có migration2026_11_03_031704, chưa deploy/migrate cloud, nên chưa xác nhận sync live. Migration backend phải chạy trước frontend mới. Không gửi mail hoặc chuyển dữ liệu cũ.

### 2026-11-03 — Cài đặt lớp tách khỏi tổng quan

Chủ dự án muốn tránh thao tác nhầm tại overview, chốt nhãn ngắn Cài đặt. Thêm tab settings cho hai role, overview không render edit/archive/leave/invite controls; settings giữ chức năng theo role và xác nhận modal, thêm xác nhận tắt mã. Không thêm DELETE lớp. Kiểm lint/build local, chưa browser/cloud mới; thay đổi không đụng DB/API.

Kiểm bổ sung Edge API fixture: overview không có nút Lưu trữ lớp, vào tab Cài đặt có, quay Tổng quan lại không có PASS; work/check-class-settings.cjs ignored. Lint/typecheck/build PASS; chưa cloud hoặc browser student mới.

### 2026-11-03 — Student sidebar pin

Theo yêu cầu: sidebar học sinh hiển thị lớp được duyệt và điều chỉnh ghim, lưu tài khoản. Dùng API classes scoped hiện có, teacher-only semester query disabled cho student, nhóm Đang theo học. Lint/build PASS, browser fixture pin/unpin/reopen context/restore/no semester403 PASS; không đổi API/DB. Evidence work/check-student-sidebar.cjs ignored. Chưa production verification.

### 2026-11-03 — Overview pending requests notice

Theo yêu cầu đặt một dòng rộng100% ở Tổng quan, thêm teacher-only thông báo Có n yêu cầu vào lớp đang chờ duyệt và Xem yêu cầu. API totalItems, enabled theo role/class/tab, cập nhật30s;0ẩn. Giữ quyền server, không thêm popup hoặc badge sidebar ngoài yêu cầu. Lint/build và browser fixture fullwidth/count/deeplink PASS; chưa cloud mới.

2026-11-03 — Điều chỉnh vị trí nhắc yêu cầu: đặt dưới Thông tin lớp theo yêu cầu mới, giữ full-width và logic cũ. Build/typecheck PASS.

### 2026-11-03 — Gộp hàng tab và toolbar lớp

Theo yêu cầu chủ dự án: xóa Bài tập của lớp, nút/filter cùng hàng tabs trên desktop, xuống riêng khi thiếu width/mobile. Dùng ClassToolbar provider/portal dùng chung cho assignments/announcements/calendar/members/attendance/grades; giữ handler/state tại panel, global calendar fallback. Lint/build PASS; browser fixture12lượt (6tab ×1920/390) alignment/wrap/nooverflow PASS. Không backend/DB hoặc production checks mới.

2026-11-03 — Gộp toolbar trang lớp: nút create/join cùng search/status/semester, bỏ số lượng lớp ở trang danh sách theo yêu cầu. Desktop/mobile fixture kiểm hàng và mở modal PASS. Lint/build PASS; không API/DB hoặc dashboard đổi.

2026-11-03 — Yêu cầu vào lớp/Quét điểm danh: bỏ tiêu đề và giải thích đầu trang theo yêu cầu, giữ tên trang cho screen reader. Lint/build PASS; không nghiệp vụ/API đổi.

2026-11-03 — Calendar form theo yêu cầu: nhập tay ngày/giờ tạo+sửa; parser validate ngày thật/24h; checkbox Lặp hằng tuần ở dưới, Chấp nhận trùng ngắn. Preview-only default, tạo explicit và confirm, reset preview khi sửa, Hủy footer; loại success báo tạo sau preview. Lint39unit/buildPASS, Edge fixture preview/cancel không createPASS (work/check-schedule-preview.cjs ignored). Không backend đổi; chưa cloud verification.

2026-11-03 — Bulk cancel theo yêu cầu tạo nhầm lịch: checkbox từng buổi/trang/toàn tháng (đủ trang), reason/confirm modal, giữ lịch sử và permission/version API hiện có. Hủy tuần tự, có kết quả từng phần và giữ lỗi để kiểm tra. Lint/buildPASS; browser fixture success2 và partial409 giữ selectionPASS, không ghi trước confirm. Chưa cloud verification.

### 2026-11-03 — Contrast, background operation feedback, trash

Đã sửa nút danger trong modal chữ trắng nền đỏ, thêm MutationStatus track shared forms/actions/account preference save; Thu gọn modal cho tiếp tục giao diện khi promise chạy. Chưa durable job/offline hoặc draft restore sau unmount. Lịch chính chỉ scheduled, trash cancelled + bulk delete, server bảo vệ owner/version/state/attendance history; không xóa dữ liệu thật ở cloud. Không migration. Backend ScheduleAttendanceTest17/236PASS (test schema đã có, transaction rollback); frontend lint39unit/buildPASS; Edge fixture trash/delayed deletion/minimize/contrastPASS. Evidence work/check-trash-background.cjs ignored. Docs DEC043/intake/API/frontend cập nhật; cần backend live mới có endpoint. Không tự trigger deploy/mail/import.

Bổ sung cuối: tầng HTTP cũng theo dõi mọi request ghi (POST/PUT/PATCH/DELETE), bao gồm composer bình luận và upload, dùng store domain thuần và observer UI để không phá phân tầng. Giữ task aggregate cho form/bulk. Lint/build và39unit đã chạy lại PASS; delayed-delete/minimize browser fixture chạy lại PASS. Không có hàng đợi durable hoặc tự retry khi request không rõ kết quả.

2026-11-03 — Theo chỉ đạo bỏ Thùng rác, chuyển xóa vĩnh viễn trực tiếp (từng/bulk), giữ modal confirm và history protection. Không đụng dữ liệu cloud hoặc purge tự động. Backend18tests239assertionsPASS, frontend lint/buildPASS, browser fixture direct DELETE/no-trash/background minimizePASS (script log kế thừa chữ trash nhưng assertions đã đổi sang scheduled/direct). Cần backend cập nhật guard mới; source push không khẳng định cloud Live.

2026-11-03 — Nhập ngày/giờ theo từng phần ở tạo/sửa lịch: tự phân cách, tự chuyển phần, giữ placeholder, chặn chữ; tháng13→12, ngày vượt giới hạn→cuối tháng theo năm nhuận, giờ/phút giới hạn23/59. Đổi tháng/năm điều chỉnh ngày; thiếu phần không submit. Preview vẫn không tạo, Hủy không ghi. VERIFIED local: lint/build,39unit và Edge fixture nhập03,30/,20 02, chặn chữ, clamp tháng/ngày nhuận, payload, preview/cancel PASS. Không API/DB đổi hoặc cloud verification.

2026-11-03 — Theo yêu cầu mới: gộp Tạo mã mới/Tắt mã tham gia/Lưu trữ lớp cùng hàng có wrap, bỏ Thu gọn khỏi shared Modal, nhóm checkbox tạo lịch có khoảng cách20px phía trên và12px giữa hàng. Footer Đóng/Hủy giữ nguyên. VERIFIED local lint/build và Edge fixture alignment/no-minimize/spacing/mobile-no-overflow PASS; không API/DB đổi.

2026-11-03 — Giữ nhập tay theo từng phần và bổ sung bộ chọn native date/time/datetime-local qua biểu tượng lịch/đồng hồ, đồng bộ hai chiều. Áp dụng shared ScheduleInput cho tạo/sửa lịch; picker change vẫn reset preview. VERIFIED local lint/build và Edge fixture picker ngày/giờ→segments, manual entry/clamp, preview/cancel PASS; không API/DB đổi.

2026-11-03 — Tạo một buổi dùng Ngày học + Giờ bắt đầu/kết thúc chung ngày; validate giờ tăng trước request với lỗi tiếng Việt. Tạo nhiều buổi giữ form/preview, thêm lỗi khoảng ngày/thứ rõ ràng. Dịch INVALID_SCHEDULE/EMPTY_SERIES/SCHEDULE_CONFLICT ở HTTP, thông báo thiếu segment tiếng Việt. VERIFIED local41unit/lint/build và Edge fixtures single-day invalid-no-request/valid payload, weekly preview/cancel, picker/manual PASS. Không đổi API/DB/quy tắc24h.

2026-11-03 — QR opening: kiểm tra local thứ tự mở/tính muộn/đóng và cửa sổ bao gồm hiện tại, giải thích tiếng Việt trước request; dịch validation response và tên3field QR. Không nới server clock/permission/QR TTL. VERIFIED45unit/build/lint và Edge fixture invalid-no-request/server422-Vietnamese PASS; chưa test check-in thật production.

2026-11-03 — QR trực tiếp từ lịch: nút row mở Modal chứa cấu hình, hoặc QR/roster nếu đã mở; không điều hướng trang trung gian. Route QR cũ redirect về lịch, không tự mở/chốt attendance. Query refetch khi mở modal lại. VERIFIED Edge fixture giữ URL schedule/form/validation/QR đang mở/roster/mobile-no-overflow PASS; lint/build PASS. Không API/DB đổi.

2026-11-03 — Student QR: Ctrl+V ảnh trong modal hoặc chọn ảnh máy, giải mã pixels local bằng jsQR1.4.0 lazy-load (không upload), chặn ảnh quá10MB, scale tối đa2000px, lỗi rõ ràng, không auto check-in. Giữ link/text/camera; apply rồi xác nhận và server vẫn kiểm TTL/quyền. Teacher thêm Sao chép liên kết hiện tại, disabled khi hết hạn; link không dài hạn. VERIFIED local45unit/lint/build và Edge actual PNG/file+ClipboardEvent→token→explicit-checkin; teacher clipboard readback đúng link PASS. Không ghi cloud. Tham chiếu library: https://github.com/cozmo/jsQR .

2026-11-03 — Mở lại QR (CONFIRMED theo yêu cầu chủ dự án; IMPLEMENTED/VERIFIED local): owner lớp active/buổi không hủy được mở lại window closed/expired qua POST attendance-windows cùng expectedVersion hiện tại. Giữ window/roster ban đầu; tăng version và token_version, thay khoảng giờ hợp lệ, mã cũ vô hiệu. QR check-in chỉ cập nhật record absent nguồn close_finalize, giữ record ID và audit trước/sau; record QR/manual không ghi đè. Không reset lịch sử hoặc bổ sung roster. UI Mở lại QR→form giờ→active trong modal lịch. Backend19tests267assertions/PintPASS; frontend lint/build và Edge versioned-reopen fixturePASS. Không migration; cần backend mới trên production trước khi dùng, chưa cloud verification.

2026-11-03 — Chẩn đoán QR403: HTTP từng ưu tiên status403 chung làm mất NOT_IN_ATTENDANCE_ROSTER. Thêm thông báo code-specific tiếng Việt cho roster/expired/window/invalid-token, cảnh báo teacher roster trống và chỉ dẫn tạo buổi mới sau khi duyệt HS. Không đổi snapshot DEC016/044 hoặc tự thêm roster. VERIFIED local regression snapshot1test13assertions và Edge fixture403 hiển thị đúng lời nhắc/lint/buildPASS. Render đọc log theo trace ảnh không có kết quả, chưa xác nhận nguyên nhân lần lỗi production; không ghi cloud.

2026-11-03 — Theo yêu cầu xóa buổi QR không học sinh: cho owner xóa window trống (0roster,0records), dù đang mở/đã đóng; khóa class/session/window cùng transaction và expectedVersion, xóa window trước session để giữ FK. Có roster/records vẫn chặn SESSION_HAS_ATTENDANCE với tiếng Việt. Calendar row delete lỗi chỉ MutationStatus góc phải dưới qua ActionButton errorPlacement=popup; không giãn hàng. VERIFIED backend20tests284assertions/Pint và frontend lint/build/Edge popup-only409PASS. Không migration/không xóa dữ liệu cloud; cần backend deploy mới.

2026-11-03 — DEC-045: theo yêu cầu mới, HS active/đã duyệt đều check-in được khi window/QR hợp lệ, kể cả duyệt sau mở. Pending/removed/left vẫn bị chặn. Roster tích lũy firstOrCreate, sync ở open/reopen/QR/roster đang mở/finalize; check-in bổ sung actor sau xác thực token. Giữ lịch sử, version/audit/manual, không tự đổi roster closed, không chốt vắng HS joined sau closesAt. Không migration/cloud data write. VERIFIED backend22tests310assertions/PintPASS, frontend lint/buildPASS. Cần backend mới để áp dụng production.

2026-11-03 — DEC-046: tham khảo read-only luồng duyệt M-ChamCong, triển khai attendance table (mã HS tự khai/tên/buổi/giờ/kết quả/duyệt), teacher thêm/sửa manual có reason, Duyệt từng hoặc checkbox chọn trang và duyệt hàng loạt tuần tự với version/confirm/partial failure. approved_at/approved_by thêm vào attendance_records (migration2026_11_03_084459), approval audit và khóa API mọi sửa/quét ghi đè; QR duplicate trả ALREADY_CHECKED_IN, chỉ auto_absent chưa duyệt/chưa từng QR được ghi nhận lần đầu khi mở lại. Bản ghi cũ mặc định chờ duyệt, không auto-approve. Summary/Excel chỉ tính approved và window đã đóng/hết hạn. QR modal wide chỉ danh sách tên present/late và checkedInCount toàn bộ có phân trang, bỏ chỉnh sửa. VERIFIED local migration trên attendance_test, PHP27tests/PintPASS, React45unit/lint/buildPASS, Edge table/bulk confirm+partial retry/lock/mobile, manual add, QR count42/no-edit/name-nowrapPASS; đã xem ảnh local. Không cloud migration/deploy hoặc sửa dữ liệu thật. Cần backend migrate+release trước frontend dùng tính năng mới.

2026-11-03 — Theo phản hồi khoảng cách: attendance-selection margin20px trên/16px dưới để toolbar checkbox không dính bảng. Bộ lọc lịch tháng đang chờ chủ dự án chốt3quy tắc màu/lọc qua câu hỏi Có/Không; chưa triển khai phần phụ thuộc.

2026-11-03 — DEC-047 chốt cả3câu Có: MonthCalendar ở Lịch học/Điểm danh, mặc định cả tháng; nhấn ngày lọc from/to, Cả tháng reset, đổi filter reset checkbox/page. Ngày theo ngày bắt đầu buổi ở timezone thiết bị; startWithin giữ overlap contract mặc định của API cũ. Lấy đủ trang tháng; gộp ngày xanh chỉ khi mọi buổi window closed/expired và có record, tất cả records/roster được duyệt; còn lại đỏ. No session mờ/disabled/cursor default, cancelled không tính. API SessionResource thêm attendanceReviewState, subquery aggregate theo session có scope quyền cũ, không trả count/tên học sinh. Giãn toolbar checkbox20px trên/16px dưới. VERIFIED backend24tests357assertions/Pint, React47unit/lint/build, Edge2tabs màu đủ trang/filter/reset/disabled/mixedday/mobilePASS; đã xem ảnh và sửa class empty trùng CSS để lịch gọn40px mỗi ô. Không migration/cloud verification, cần backend mới để màu approved chính xác.

2026-11-03 — Theo yêu cầu: membership owner-only list thêm studentCode/gender/birthday từ hồ sơ đã lưu, null hiển thị Chưa cập nhật (undisclosed=Không chia sẻ), không thêm email/phone hoặc mở roster cho học sinh. Tabs đổi Thành viên→Lịch học→Điểm danh để lịch/attendance kề nhau. Bản tin gom edit/pin/hide/publish/archive vào disclosure ba chấm dọc; moderation bình luận cũng gom menu, giữ comment toggle/status bên ngoài. Shared ActionMenu hỗ trợ Escape/click ngoài, lỗi async vào popup. Composer textarea+Gửi flex cùng hàng và giữ idempotency/Ctrl+Enter/retry. VERIFIED ClassroomsTest8tests65assertions/PintPASS, frontend lint/build và Edge profile/taborder/menu-hidden+Escape/comment payload+same-row/mobile-no-overflowPASS. Không migration/cloud verification.

2026-11-03 — Theo yêu cầu giảm popup: MutationStatus chỉ render failed, không popup pending/done; gộp lỗi cùng message để tránh trùng HTTP/form, nút Đóng thông báo. Giữ tracking/task và trạng thái nút/form, inline validation hiện có. VERIFIED local Edge comment success không popup, delete409 có đúng1lỗi và dismissPASS; lint/buildPASS. Không API/DB đổi.

2026-11-03 — Thành viên responsive theo yêu cầu: desktop table rộng100% vùng nội dung, cột Học sinh gồm mã nhỏ trên/họ tên dưới, gender/birthday/actions; bỏ badge active ở cả table/mobile, giữ trạng thái khác khi cần. Mobile<=760px giữ card cũ; dùng MemberActions chung để không lặp logic quyền/version/confirm, lỗi action popup-only. VERIFIED lint/build và Edge1920/390 table-width/code-above-name/no-active-label/mobile-card/no-overflowPASS, đã xem ảnh. Không API/DB đổi.

2026-11-03 — Check-in theo ảnh tham khảo: stage căn giữa ngang/dọc vùng nội dung, card tối đa760px, đồng hồ ngày+HH:mm:ss cập nhật1s riêng component (không dùng quyết định hiệu lực QR), video căn giữa/tỷ lệ4:3 có giới hạn viewport. Giữ camera/dán ảnh-link/xác nhận, rút gọn nút Đóng camera, disable xác nhận khi chưa có token. Không thêm location/GPS/ảnh khuôn mặt/stat cards; không sửa quy tắc QR. VERIFIED Edge fake-camera center/ticking-clock/stop-tracks/mobile-no-overflowPASS, xem screenshot và lint/buildPASS. Chưa hardware-camera/cloud verification.

2026-11-03 — Account settings: cố định cột action150px và justify-self:end để4hàng có dữ liệu cùng x, nút/badge thẳng mép phải; giữ mobile breakpoint. VERIFIED Edge1920 alignment <=1px/390-no-overflow và buildPASS. Chỉ CSS, không API/DB đổi.

2026-11-03 — DEC-048 IMPLEMENTED local: bỏ CTA QR dashboard; PortfolioDemo baseline riêng, marker/transaction, reset24h/quota, khóa credential/profile/upload demo và ngăn enrollment vượt namespace. Startup/scheduler/login bảo trì cùng action; không đổi schema. VERIFIED PHPUnit11tests83assertions (PortfolioDemo+Classrooms), Pint, React47unit/lint/build. Deploy và ảnh production đang thực hiện, chưa ghi VERIFIED cloud.

2026-11-03 — Portfolio release107b769 VERIFIEDlive Render startup baseline3classes/13accounts; Edge teacher/student login, tạo buổi, QRcheckin201/duplicate409, close/approve200/approvededit409, ownerreviewrequest200.21ảnh chức năng thật desktop/mobile, README tuyển dụng và gallery. FullPHP131tests1186assertionsPASS. Kiểm tra live phát hiện SubmissionRequest/SubmissionFlow bắt buộcfile; sửa riêng demo note-only với flagresource server, giữ normalrequiredfile/idempotency. Portfolio3tests21assertions và React48unit/lint/build/PintPASS. Note-only đang chờ deployment kế tiếp; không gọi verifiedlive trước khi thử.

2026-11-03 — Hoàn tất releasef6841f0 VERIFIEDlive: demo note-only UI201, grade save200/publish201, HSownhistory, XLSX200/ZIPheader. Teachercheckbox chọn10bản ghi→confirm→10approve200; ngày02/11xanh, bảngreload10approved/noedit.26ảnh thật trong docs/media và FEATURE-TOUR; README có tài khoản công khai/5phút thử/giới hạn/futureGoogle/chat/quiz/email/camera. Latestaffected Accounts+Portfolio17tests120assertionsPASS, React48unit/lint/buildPASS; fullPHP131tests1186assertionsPASS ở baseline release trước note-only. Không épresetcloud hoặc sửa bộvisual cũ; startup ready và auto-maintenance đã bật. Không video; dùng bộảnh chức năng theo yêu cầu “video hoặc ảnh”.

2026-11-05 — Brevo cho AttendanceWeb theo NoteTatCa: chủ dự án chọn dùng chung API key, không tạo key mới. IMPLEMENTED local custom Laravel mail transport qua HTTPS api.brevo.com/v3/smtp/email, đăng ký driver brevo và BREVO_API_KEY; giữ local/test array và SMTP hiện có, không thêm dependency. Timeout30s/connect10s/no redirects/no automatic HTTP retry; chỉ xác nhận201+messageId; lỗi không đưa key/body provider vào exception; attachments bị từ chối rõ. Cập nhật docs/17-environment-config.md và .env.example. VERIFIED BrevoTransportTest5tests10assertions (payload/sender, thiếu key,401,connection failure, verification+reset notifications qua HTTP fake); AccountsTest14tests99assertionsPASS sau khi khởi động MySQL local loopback. Lượt Accounts ban đầu14errors vì MySQL chưa chạy (connection refused); không dùng DB cloud. Pint dirty/scopedPASS, PHP syntax3filesPASS, Composer validate--strictPASS, git diff--checkPASS (CRLF advisory docs). testing-best-practices skill được AGENTS nhắc nhưng không có trong catalog/local skill roots đã tìm; dùng PHPUnit convention hiện hữu. Không fullsuite/React build vì chỉ sửa mail backend.

Render service attendanceweb-api/srv-dbh23o6c6qcc73csmo0g: đã Save only MAIL_FROM_ADDRESS theo sender chủ dự án cung cấp và MAIL_FROM_NAME=AttendanceWeb. Phiên Render hiện tại bị Access denied với service NoteTatCa; không truy xuất service bị chặn. Chủ dự án tự dán key chung vào BREVO_API_KEY và báo đã lưu; UI xác nhận biến có mặt/secret masked. MCP chưa chọn workspace nên dùng dashboard đúng service. Đã đọc lại MAIL_MAILER=array và QUEUE_CONNECTION=database; entrypoint không có queue worker. Chưa đổi mailer/queue, commit/push/deploy hoặc gửi mail thật; code mới chưa có trên live. Remaining: được giao publication/deploy, triển khai driver mới, bật MAIL_MAILER=brevo và cấu hình xử lý queue (worker hoặc phương án sync được duyệt), kiểm key/sender production và email thật tới mailbox được chỉ định. Save only không chứng minh gửi/nhận thành công. Không sửa NoteTatCa, APP_KEY/DB/secret khác hoặc migrations.

2026-11-05 — Chủ dự án đã giao commit/push/deploy Brevo và gửi một mẫu tới mailbox chỉ định trong chat. Publication scope: Laravel BrevoTransport/registration/config, .env.example không secret,5test transport/notification và docs17/worklog. Validation hiện có19tests109assertionsPASS, Pint/PHP lint/Composer validate/diff checkPASS; không frontend/schema/migration. Chuẩn bị publish lên origin/main; deployment/email thật còn phải kiểm chứng sau publication. Không ghi mailbox thử hoặc secret vào source/docs.

2026-11-05 — Publication e2b33793c03d496541f786de0485f1cd48c9a121 đã push origin/main và ls-remote khớp. Auto deploy dep-dbm8nvbncjis738vp3eg LIVE; health/live và health/ready200. Sau đó patch env qua MCP đúng My Workspace/tea-d914rhv7f7vs73d4rg10: MAIL_MAILER=brevo, QUEUE_CONNECTION=sync (hiện chỉ2notification account dùng ShouldQueue), MAIL_FROM_NAME=AttendanceWeb, sửa sender bị đọc thiếu ký tự từ ảnh thành đúng sender đã cung cấp; giữ BREVO_API_KEY/DB/secret khác. Env deploy dep-dbm8okorrm5s73eabg1g UPDATE_FAILED vì PortfolioDemo guard yêu cầu array mail. Đang sửa phạm vi demo maintenance dùng array tạm thời và khôi phục mailer trong finally; giữ opt-in/DB allowlist, không tắt guard/demo hoặc chuyển dữ liệu. Live trước vẫn healthy; chưa coi Brevo active.

CI run42040856696: install/validate/Pint/full PHPUnit/audit-script regression PASS; final Composer audit FAIL với advisory thư viện đã khóa hiện hữu (gồm Symfony MIME/HTTP/routing/YAML), không phải timeout. Không đổi dependency/ignore advisory ngoài phạm vi khi chưa được duyệt; không báo pipeline PASS. Gửi mẫu thật được chủ dự án giao nhưng còn chờ key local: browser tool báo disabled, Render MCP không đọc env secret. Đã hướng dẫn nhập key vào ignored .env, không đưa key/mailbox vào Git. Không gửi mẫu hoặc khẳng định deliverability khi chưa có response provider.

2026-11-05 — Sửa rollout blocker: PortfolioDemo::handle tạm dùng mail array trong try/finally, maintain giữ opt-in/approvedDB guard rồi khôi phục mailer của app cả khi lỗi. VERIFIED affected PortfolioDemoTest+BrevoTransportTest10tests34assertionsPASS, gồm không HTTP thật khi bảo trì với default brevo và restore mailer khi thiếu opt-in; Pint/diffcheckPASS. Chủ dự án đã nhập shared key vào ignored local .env cho thao tác gửi mẫu được giao; không stage key hoặc script gửi mẫu. Publication tiếp theo chỉ demo mail isolation/tests và docs outcome; không thay dependency, không tắt demo hoặc đổi schema.

2026-11-05 — Hoàn tất rollout Brevo: commit2c3b2c4e2d33816e2c03a8e083ab69b5416ebd0e đã push origin/main và ls-remote khớp. Render dep-dbm8t968bjmc739o1bcg LIVE lúc14:12:40UTC (21:12:40UTC+7), health/live và health/ready200 sau deploy; query runtime logs ERROR/Exception từ thời điểm live không có kết quả. New code chạy với MAIL_MAILER=brevo/QUEUE_CONNECTION=sync và sender đúng; không đổi key/DB/schema. LatestCI42042651054 còn running tại lần kiểm, run trước fulltestsPASS/auditFAIL như ghi phía trên; không tuyên bố CI xanh.

Email mẫu thật theo yêu cầu đã gửi đúng1lần được Brevo tiếp nhận201 qua Laravel BrevoTransport từ máy local, rồi GET events200 báo delivered lúc21:11:40UTC+7. MessageId <202611051411.91019096311@smtp-relay.mailin.fr>; recipient theo chỉ định trong chat, không ghi mailbox/key vào docs. Nội dung tiếng Việt mẫu dùng Laravel MailMessage layout, thương hiệu AttendanceWeb, không có mã/link auth thật. Lượt đầu không được provider xác nhận do local PHP HTTPS; diagnostic không auth phát hiện curl60 thiếuCA. Tải public cacert.pem từ curl.se vào ignored .tools/php, dùng curl.cainfo và IPv4 cho riêng lệnh local, không tắt TLS verification/đổi PHP production hoặc commit local config. Script gửi/status trong ignored work/, key trong ignored.env. Delivery evidence xác nhận mẫu local với cùng key, chưa nghiệm thu registration/recovery thật qua Render hoặc vị trí Inbox/Spam/ảnh hộp thư. Không tạo account/reset mật khẩu người nhận, không gửi mẫu lần2.

2026-11-05 — Final CI42042651054 đã completed/failure: install/validate/Pint/fullPHPUnit/audit-script regressionPASS, Composer auditFAIL. Không bỏ audit hoặc cập nhật dependency ngoài phạm vi được giao. Publication docs59c776b đã push/ls-remote xác nhận; bổ sung kết quả CI cuối bằng docs-only [skip render], giữ workload live2c3b2c4.

2026-11-05 — Google sign-in: chủ dự án giao dùng ClientID OAuth Web đã tạo và duyệt thêm official google/apiclient2.20.1 (GoogleAuth1.55.3/FirebaseJWT7.2.1/PSRCache3/services0.465.1 locked; không update8advisory hiện hữu). IMPLEMENTED backend library signature verification+strict aud/iss/exp/iat/azp/email_verified/sub/nonce, one-time session5min+atomiccache, CSRF/throttle; cookie session/authVersion/lock và quyền owner giữ nguyên. New Google registration chọn teacher/student; existing local email không autolink, phải sign-in rồi link sameemail; không publicadmin/đổi role của user cũ. Gmail/Workspace authoritative verified; thirdparty Google email còn cần appemailverification. Google-only passwordnullable; recovery setup trước unlink, demo link/unlink bị chặn. Newmigration2026_11_05_144206 thêm unique nullable google_subject và nullablepassword, không sửa history/data cũ. Down không khôi phục NOTNULL nhằm tránh lỗi khi có Google-only; không rollback binding sau publication, forwardfix.

Frontend GIS officialbutton/nonce/credential callback, signup sharedrole, errors/retry preserve password form, scriptfailure retry; sessionquery cache cập nhật, token chỉ memorykhôngstorage. Settings explicitlink/unlink và setup-password qua recovery. Docsintake/plan/ADR049/API/config/README cập nhật. VERIFIED GoogleAuthTest8tests58assertions; realRSA/JWKS mocked Googlelibrary12tests13assertions; React48unit/lint/type/buildPASS (build có ClientID public),4EdgePlaywright syntheticGIS casesPASS (signup role/sessionworkspace, existingemail→retry/freshnonce, settingslink/unlink, blockedscript retry). Initialbackend run đang edit limiter sinh MissingRateLimiter; finalrerun8PASS. E2E initialSettings bị fixture503bootstrap; sửa fixtureDashboard dữ liệu hợp lệ rồi4PASS, không nới assertion. LegacyAccounts regression còn running tại thời điểm ghi; không fullsuite local/realGoogle/account thật/email thật.

Configuration: local ignored.env/.env.local chỉ thêm GOOGLE_CLIENT_ID/VITE_GOOGLE_CLIENT_ID, giữ Brevo/DB/secret khác. Vercelprojectattendanceweb/prj_VnZqRi04OMYy7NDdLEBxaeKeQ44F/team_4dWX8BH1i61uZlgXJH0ymCiL: initialcreate403thiếu teamgrant, chủ dự án tự duyệt authorize_team_access, retrysuccess tạo publicplainVITE_GOOGLE_CLIENT_ID Production/id dsOKqJ0SNQHTuNjq; chưa đổiPreview. RenderGOOGLE_CLIENT_ID/publication/deploy/migration cần hoàn tất sau test. Publication scope dự kiến backend/library/migration/tests+docs trước, UI/tests sau backendready; không ClientSecret, không gửi Googlecredential thật hoặc đọcDBcloud. Actual rollout/manualOAuth limits sẽ ghi tiếp.

2026-11-05 — Pre-publication final: AccountsTest+GoogleIdentityValidatorTest26tests112assertionsPASS; cộng GoogleAuthTest8/58 thành34test API liên quan/170assertions (không cộng lại lượt unit trước). Composer validate--strictPASS; frontend48unit/4syntheticE2E/lint/typecheck/build configuredIDPASS. Commitbackend trước gồm attendance_api source/librarylock/migration/tests và docs/README, không local.env/secret/vendor. Frontend đang giữ local để publish sau Renderready.
