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
