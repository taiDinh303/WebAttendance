# 15 — Hợp đồng REST API dự thảo

> 2026-10-26 | TARGET CONTRACT + CURRENT SUBSET | Đã có controller/OpenAPI cho core. Các nhóm chưa implement vẫn TARGET; inventory hiện hành ở API-CURRENT, không gọi mọi route dưới đây là đang hoạt động.

**Cập nhật đợt API đầu:** một phần contract đã có runtime. Xem [API-CURRENT](API-CURRENT.md) và OpenAPI/export inventory liên kết ở đó cho route/payload thực; bảng toàn bộ sản phẩm dưới đây vẫn TARGET, không khẳng định QR/quiz/chat đã có. Course/semester PATCH đợt đầu yêu cầu đủ name và hai ngày (semester); delete nhận expectedVersion JSON. Create class trả `{class,inviteCode}`. Rejoin có owner action allow-rejoin; HS không được xem roster trong đợt này. Inbox chỉ membership events, chưa realtime/read-all. Idempotency-Key tổng quát chưa triển khai cho create lớp/catalog; duplicate join/review và read dùng state/unique constraints riêng, client chưa được auto-retry create lớp.

**Phase2 ngày2026-10-27:** Schedule/QR đã có, gồm manual correction theo DEC-017. Contract current đầy đủ ở [SCHEDULE-ATTENDANCE](SCHEDULE-ATTENDANCE.md); OpenAPI/route-current đã export lại. Nhóm learning/chat bên dưới vẫn TARGET. Các ghi chú QR-only và Q-011/023/024 chưa chốt của phase1 đã được supersede.

## Quy ước toàn cục

Prefix `/api/v1`; JSON camelCase, ULID string, datetime ISO8601 UTC `2026-10-26T03:00:00Z`, enum string. Auth cookie Sanctum cho first-party SPA, request credentialed và CSRF; không đặt token phiên vào query. TLS bắt buộc production. Resource ID không thay quyền. Gọi auth CSRF bootstrap ngoài prefix ở `/sanctum/csrf-cookie` theo cấu hình Sanctum. Không tự expose API token admin.

```json
{"code":"SUCCESS","message":"OK","data":{"id":"01EXAMPLE"},"traceId":"request-example"}
```

Ví dụ ID chỉ minh họa shape, fixture triển khai phải dùng ULID hợp lệ. Success200/201, create có Location; delete204 không body. API list thường:

```json
{"code":"SUCCESS","data":{"items":[],"pageNumber":1,"pageSize":20,"totalItems":0,"totalPages":0,"hasNextPage":false,"hasPreviousPage":false},"traceId":"request-example"}
```

`pageNumber≥1`, `pageSize1–100`, mặc định20; page vượt cuối trả items[] với total thật. Sort allowlist + id tie-breaker; q trimmed, giới hạn đề xuất200 ký tự, không nhận SQL field bất kỳ. Collection filter scope trước total/pagination. Không có endpoint all không giới hạn. Calendar bắt buộc from/to và giới hạn đề xuất93 ngày; pickers cũng phân trang. Chat history dùng cursor opaque; message order theo sequenceNo, không clock client.

| HTTP/code | Ý nghĩa và client |
| --- | --- |
| 400 BAD_REQUEST | JSON/query shape không hợp lệ |
| 401 UNAUTHENTICATED | Chưa/hết phiên; đăng nhập, không refresh JWT giả định |
| 403 FORBIDDEN / ACCOUNT_LOCKED | Đúng resource nhưng không được làm action, hoặc tài khoản khóa |
| 404 NOT_FOUND | Resource không có hoặc ngoài scope đọc, tránh enumeration |
| 409 CONFLICT / VERSION_CONFLICT / STATE_CONFLICT | Version/state/idempotency payload không khớp; giữ draft và yêu cầu giải quyết |
| 410 QR_EXPIRED | Token/window hợp lệ từng tồn tại nhưng hết hạn; chỉ trả sau kiểm actor phù hợp |
| 413 PAYLOAD_TOO_LARGE | Upload/request quá limit ở proxy hoặc app |
| 419 CSRF_MISMATCH | Sanctum/Laravel CSRF; bootstrap lại CSRF, không tự replay mutation không an toàn |
| 422 VALIDATION_ERROR | errors theo field, không lộ stack; deadline/policy có code cụ thể |
| 429 RATE_LIMITED | Có Retry-After; không retry dồn |
| 500/503 INTERNAL_ERROR / SERVICE_UNAVAILABLE | traceId, không raw exception/SQL/token |

Mutable resource trả `version`; PATCH/approve/grade/publish có expectedVersion body. Tạo mutation có nguy cơ retry nhận `Idempotency-Key` UUID: scope actor+operation+key, hash payload, cùng key/payload trả cùng resource; khác payload409. Đề xuất giữ24h, action tự có unique DB làm lớp bảo vệ lâu dài; key lookup/replay luôn kiểm quyền hiện tại. PATCH không tự merge khi version sai. Tất cả limits chính xác chốt trước code.

## API-001 — Auth và self

| Method/path | Payload/query chính | Quyền / kết quả |
| --- | --- | --- |
| POST `/auth/register` | email,password,passwordConfirmation,displayName,role:teacher/student | Public + rate/CSRF phù hợp;201 account pending verification đề xuất |
| POST `/auth/login` | email,password,rememberMe? | Public + CSRF;200 profile summary, session regenerate |
| POST `/auth/logout` | Không | Auth;204 invalidate phiên |
| POST `/auth/forgot-password` | email | Public, generic202, rate limit |
| POST `/auth/reset-password` | email,token,password,passwordConfirmation | Proof token;204 + revoke sessions theo policy |
| POST `/auth/verification/resend` | Không | Auth unverified, rate;202 |
| GET `/auth/verify-email/{id}/{hash}` | signed URL + expires | Signed proof/auth theo implementation; không log query; xác minh một lần |
| GET `/me` | Không | Self profile, roles, status, capabilities |
| PATCH `/me/profile` | displayName,phone?,locale?,timezone?,avatarFileId?,expectedVersion | Self, không role/userId |
| POST `/me/change-password` | currentPassword,password,passwordConfirmation | Self + proof;204 |

Email change, multi-role và account delete chưa chốt, không tự mở endpoint destructive. Admin provisioning không qua register.

## API-002 — Catalog và classes

`GET/POST /semesters`, `GET/PATCH/DELETE /semesters/{id}`; tương tự `/courses` và `/course-types`. Owner teacher; HS chỉ đọc catalog tối thiểu qua lớp active nếu cần. List `q,status,pageNumber,pageSize,sort`. Body semester `{name,startDate,endDate,isActive}`; course `{code?,name,courseTypeId?}`; type `{name,credits?,theoryPeriods?,practicePeriods?}`. Delete đang được tham chiếu409; scope Q-010.

| Method/path | Body/query | Quyền |
| --- | --- | --- |
| GET `/classes` | q,semesterId?,courseId?,status,pageNumber,pageSize,sort | Teacher own / student active; response không tự chứa mã mời |
| POST `/classes` | name,description?,courseId?,semesterId?,timezone? | Teacher, server gán owner |
| GET `/classes/{classId}` | Không | Owner/active member, fields theo role |
| PATCH `/classes/{classId}` | name?,description?,courseId?,semesterId?,expectedVersion | Owner; FK cùng scope |
| POST `/classes/{classId}/archive` | expectedVersion | Owner;409 nếu policy chặn hoạt động đang mở |
| POST `/classes/{classId}/invite-code/rotate` | expiresAt?,expectedVersion | Owner; code plaintext chỉ trả lúc tạo/rotate; hash DB |
| POST `/classes/{classId}/invite-code/disable` | expectedVersion | Owner;204 |

Mã không đọc lại từ hash; owner cần hiển thị lại có thể rotate hoặc thiết kế encrypted secret riêng sau quyết định. Không trả hash cho client.

## API-003 — Join/membership

| Method/path | Body/query | Quyền / kết quả |
| --- | --- | --- |
| POST `/join-requests` | inviteCode | Student;201 pending, duplicate200; thông tin lớp tối thiểu |
| GET `/me/join-requests` | status,pageNumber,pageSize | Student self |
| POST `/join-requests/{membershipId}/cancel` | expectedVersion | Requester pending |
| GET `/classes/{classId}/join-requests` | status,q,pageNumber,pageSize | Owner |
| POST `/classes/{classId}/join-requests/{membershipId}/approve` | expectedVersion | Owner, relation đúng class, active + event |
| POST `/classes/{classId}/join-requests/{membershipId}/reject` | reason?,expectedVersion | Owner, pending |
| GET `/classes/{classId}/members` | q,status,pageNumber,pageSize,sort | Owner full safe profile; HS chỉ tên nếu policy cho phép |
| POST `/classes/{classId}/members/{studentId}/remove` | reason?,expectedVersion | Owner, không xóa lịch sử |
| POST `/classes/{classId}/leave` | expectedVersion | Student self active |

Không có public lookup code trả roster. Rejoin rejected/removed theo Q-022. Teacher thêm/invite HS trực tiếp cần flow đã chốt, chưa tạo bypass approve route.

## API-004 — Schedule và QR

| Method/path | Payload/query | Quyền |
| --- | --- | --- |
| GET `/calendar` | from,to,classId?,pageNumber,pageSize | Actor scoped; khoảng bounded |
| GET/POST `/classes/{classId}/sessions` | List from/to,page; create title?,startsAt,endsAt,room? | Read owner/active, create owner |
| PATCH `/classes/{classId}/sessions/{sessionId}` | startsAt?,endsAt?,room?,expectedVersion | Owner, không sửa lịch sử đã chốt |
| POST `…/sessions/{sessionId}/cancel` | reason,expectedVersion | Owner |
| POST `…/sessions/{sessionId}/attendance-windows` | opensAt,lateAfter?,closesAt | Owner;201 window |
| GET `/attendance-windows/{windowId}/qr` | Không | Owner; token/validUntil/serverTime, no-store |
| POST `/attendance-windows/{windowId}/close` | expectedVersion | Owner, idempotent |
| POST `/attendance/check-ins` | qrToken | Student self;201 record hoặc200 record đã có |
| GET `/classes/{classId}/attendance` | sessionId?,studentId?,status?,pageNumber,pageSize | Owner lớp; studentId không mở rộng quyền |
| GET `/me/attendance` | classId?,from?,to?,pageNumber,pageSize | Student self |

Response check-in current `{id,sessionId,studentId,windowId,status,checkedInAt,recordedSource,version}`; serverTime ở QR/window response. Không nhận status/studentId/checkedInAt client làm nguồn chuẩn. No-store với QR/JSON private. Có POST session-series/preview và session-series, GET window/roster/summary/audit, PUT `classes/{classroom}/sessions/{sessionId}/attendance/{studentId}` cho owner sửa tay `{status,reason,expectedVersion}`; chi tiết/limits xem contract phase2.

## API-005 — Bản tin/comments

`GET/POST /classes/{classId}/announcements`; `GET/PATCH /classes/{classId}/announcements/{id}`; `POST …/{id}/publish`, `POST …/{id}/archive`. Create/update `{title,content,expectedVersion?}`; owner tạo/sửa, active member đọc published; owner thấy draft. List `q,state,pageNumber,pageSize,sort`.

`GET/POST …/announcements/{id}/comments`, `PATCH/DELETE …/comments/{commentId}`. Body `{content,expectedVersion?}`; active member viết, self sửa/xóa nếu bật, owner moderation. Limit content đề xuất title200/content20.000/comment2.000 ký tự, chốt trước implementation; sanitize cả server/render.

## API-006 — File/bài tập/nộp/chấm

| Method/path | Payload/query | Quyền / kết quả |
| --- | --- | --- |
| POST `/classes/{classId}/files` | multipart file, purpose, assignmentId? | Owner/HS tùy purpose;201 staged file metadata/scanState |
| GET `/files/{fileId}/download` | Không | Quan hệ hiện tại; stream private hoặc signed URL ngắn theo ADR |
| DELETE `/files/{fileId}` | expectedVersion | Chỉ staged own chưa attach, hoặc workflow owner hợp lệ; file revision không xóa trực tiếp |
| GET/POST `/classes/{classId}/assignments` | List state,q,page; create title,instructions,dueAt?,maxScore,allowLate,allowResubmit,fileIds | Owner tạo; HS đọc published |
| GET/PATCH `…/assignments/{assignmentId}` | Partial create fields + expectedVersion | Owner sửa; HS GET published |
| POST `…/assignments/{assignmentId}/publish` | expectedVersion | Owner; validate file clean/config |
| POST `…/assignments/{assignmentId}/close` | expectedVersion | Owner |
| POST `…/assignments/{assignmentId}/submissions` | fileIds,note? + Idempotency-Key | HS active self; revisionNo/server timestamp |
| GET `…/assignments/{assignmentId}/submissions` | studentId?,status,pageNumber,pageSize,sort | Owner; HS dùng `/me/submissions` |
| GET `/me/submissions` | classId?,assignmentId?,pageNumber,pageSize | Self |
| GET `/submissions/{submissionId}` | revisions pageNumber/pageSize | Owner hoặc submission owner; điểm draft redacted |
| PUT `/submission-revisions/{revisionId}/grade` | score,feedback?,expectedVersion? | Owner lớp; draft grade |
| POST `/grades/{gradeId}/publish` | expectedVersion,reason? | Owner; event HS |
| GET `/classes/{classId}/grades` | assignmentId?,studentId?,pageNumber,pageSize,sort | Owner bảng lớp; HS chỉ self published |

File response không trả storageKey public/đường dẫn máy chủ. File purpose allowlist: assignment_material, submission, avatar, roster_import; avatar/roster flow cần actor-specific endpoint hoặc binding đúng scope khi triển khai. Q-013 chốt file limits; API shape không tự cấp quyền upload tùy purpose.

## API-007 — Quiz/attempt

| Method/path | Payload/query | Quyền |
| --- | --- | --- |
| GET/POST `/classes/{classId}/quizzes` | List page/state; create title,instructions,opensAt?,closesAt?,durationSeconds?,maxAttempts,questions | Owner authoring; HS published metadata |
| GET/PATCH `/quizzes/{quizId}` | Draft body + expectedVersion | Owner authoring; HS summary không key |
| POST `/quizzes/{quizId}/publish` | expectedVersion | Owner, snapshot validated |
| POST `/quizzes/{quizId}/close` | expectedVersion | Owner |
| POST `/quizzes/{quizId}/attempts` | Idempotency-Key, body trống | HS active, server thời hạn/lượt |
| GET `/attempts/{attemptId}` | Không | Self attempt hoặc owner review theo policy; student redacted questions/options |
| PUT `/attempts/{attemptId}/answers/{questionId}` | selectedOptionKeys,expectedVersion | Self in_progress, trước expiresAt |
| POST `/attempts/{attemptId}/submit` | expectedVersion + Idempotency-Key | Self; không nhận score client |
| GET `/quizzes/{quizId}/attempts` | studentId?,state,pageNumber,pageSize | Owner scoped |
| GET `/attempts/{attemptId}/result` | Không | Self sau publish policy hoặc owner |

Draft question `{key,kind,prompt,points,options:[{key,content,isCorrect}]}` chỉ owner. Student option `{key,content}`; version snapshot không gồm answer key trong response. Attempt response có serverTime/expiresAt/state/savedAnswers và version; không trả score/result khi chưa cho phép. Q-014 chốt scoring/result policy.

## API-008 — Chat/notification

| Method/path | Payload/query | Quyền |
| --- | --- | --- |
| GET `/conversations` | classId?,cursor?,limit1–100 | Participant self, class active theo policy |
| POST `/classes/{classId}/conversations` | studentId (teacher), không cần studentId khi HS | Resolve owner teacher + student hợp lệ; trả thread existing |
| GET `/conversations/{id}/messages` | beforeCursor? hoặc afterSequence?,limit1–100 | Participant, quan hệ lớp; không dùng cả before/after |
| POST `/conversations/{id}/messages` | clientMessageId UUID,body | Participant active, text only;201 hoặc200 replay |
| PUT `/conversations/{id}/read` | lastReadSequence | Participant, monotonic, không vượt last visible message |
| GET `/notifications` | read?,type?,pageNumber,pageSize | Recipient self |
| GET `/notifications/unread-count` | Không | Recipient self |
| PUT `/notifications/{id}/read` | Không | Self idempotent |
| POST `/notifications/read-all` | throughCreatedAt hoặc highWatermark | Self, không mark sự kiện mới hơn snapshot |

Message content đề xuất≤4.000 ký tự, rate limit theo actor/thread, chưa file/chat nhóm. WebSocket authorization endpoint theo Reverb/Sanctum; tên channel và event xem [27](27-realtime-jobs.md). Không cho subscribe dựa chỉ channelId truyền từ client.

## API-009 — Import/export/admin/health

`POST /classes/{id}/roster-imports` nhận fileId → preview batch; `GET /roster-imports/{batchId}` có paginated rows/errors; `POST /roster-imports/{batchId}/confirm` expectedVersion. Chỉ owner, apply idempotent, account mapping Q-017/022.

`POST /classes/{id}/exports` `{kind:attendance|grades|roster,filters}` trả202 exportId; `GET /exports/{id}` self requester kiểm quyền, ready có download action qua `/files/{fileId}/download`. HS nếu được export chỉ self, không filter studentId khác.

`GET /admin/users` page/q/status/role; `POST /admin/users/{id}/lock`, `/unlock` reason/expectedVersion; `GET /admin/audit` page/action/date bounded. Admin policy chờ Q-021, không arbitrary role update hoặc đọc nội dung bằng userId.

`GET /health/live` process200; `/health/ready` DB/schema cần thiết200 hoặc503, không public detail credentials. Health không chứng minh mail/worker/WebSocket hoạt động; có monitor riêng.

## Đồng bộ contract

Khi code: sinh OpenAPI phản ánh path/schema/status thật, test unknown fields/validation/redaction, export operation inventory và ghi commit/hash. Mỗi operation có Policy/FormRequest/Resource/Feature test; không coi bảng draft là hợp đồng đã kiểm chứng. Breaking change cập nhật version/ADR, không âm thầm đổi camelCase/enum/pagination giữa module.

## Learning contracts verified local

20operation assignment/file/submission/grade bổ sung trong source; payload và lifecycle tại [ASSIGNMENTS-GRADES](ASSIGNMENTS-GRADES.md). POST submission bắt buộc header Idempotency-Key UUID, không nhận identity hoặc score từ client. Upload là multipart từng file, các mutation khác JSON. Download trả stream attachment, không dùng JSON success envelope. OpenAPI0.3 mô tả route hiện có; khả năng suy luận schema động còn giới hạn, phải đối chiếu contract và evidence trước khi tích hợp.

## React contract extensions — 2026-10-27

GET /api/v1/me/submissions?assignmentId={ULID} lọc đúng assignment, vẫn bắt buộc actor hiện tại và membership active; invalid ULID422, assignment không thuộc quyền trả collection rỗng. List submissions thêm studentName khi eager-load, không trả email/password. Các route hiện có không đổi. Các API collections vẫn phân trang server; UI không fetch toàn bộ để lọc bài.

## API-010 — Dashboard2.0 (IMPLEMENTED 2026-10-28)

`GET /api/v1/dashboard`: authenticated + active account + verified email. Không nhận userId/classId để đổi chủ thể; server scope lớp active do teacher sở hữu hoặc student có membership active. Envelope `data` gồm `role`, `serverTime` UTC, `timezone` tài khoản, `summary`, `classes` tối đa6 và `sessions` tối đa5 trong7ngày tới còn chưa kết thúc, loại buổi cancelled.

Summary chung: activeClasses/todaySessions/upcomingSessions/unreadCount. Teacher thêm pendingRequests/pendingReviews, preview requests (nhóm theo lớp, tối đa5lớp) và reviews (latest revision chưa chấm hoặc grade draft, tối đa5bài nộp). Student thêm pendingAssignments/publishedGrades, assignments published chưa có bài nộp (tối đa5; dueAt sớm trước, không hạn cuối), grades từ publication pointer của riêng actor (tối đa5). Không trả draft score/feedback hoặc teacher queues cho student. Counts tính toàn bộ scope, không lấy count từ preview. Hôm nay dựa timezone tài khoản; timestamp response UTC. Đây là bản tổng hợp đọc, có thể thay đổi giữa các lần query khi dữ liệu đang được cập nhật, không phải snapshot transaction toàn dashboard.

`GET /classes/{id}` thêm membershipVersion khi student được phép xem lớp, chỉ version của membership active hiện tại; dùng expectedVersion cho POST leave, không dùng version của class. Attendance list thêm studentName/sessionTitle/sessionStartsAt theo eager-loaded relations, không trả email hoặc dữ liệu hồ sơ khác; quyền owner/self giữ nguyên.

## API-011 — Quản trị tài khoản (DEC-027, IMPLEMENTED/VERIFIED LOCAL)

Tất cả route bên dưới cần cookie auth + active + verified + AdminUserPolicy viewAny. Teacher/student403, guest401 kể cả không có Accept JSON. Không thêm quyền admin vào Policy lớp/file/điểm.

| Method/path /api/v1 | Request | Response data |
| --- | --- | --- |
| GET admin/summary | Không chọn actor/class | users/admins/teachers/students/lockedAccounts/classes/activeClasses/assignments/submissions/attendanceRecords/openAttendanceWindows/adminEvents/serverTime; chỉ số tổng hợp |
| GET admin/users | q ≤200, role admin/teacher/student, status active/locked, sort name/-name/createdAt/-createdAt, pageNumber/pageSize | Page AdminUser |
| POST admin/users/{userId}/lock | reason3..500 sau trim, expectedVersion≥1 | AdminUser mới |
| POST admin/users/{userId}/unlock | như lock | AdminUser mới |
| GET admin/audit | subjectId/actorId ULID, operation lock/unlock, pagination | Page event actor/subject IDs+names, reason, before/after status+version, createdAt |

PageSize1..100, pageNumber1..1000000. AdminUser chỉ id/displayName/email/role/accountStatus/emailVerified/version/createdAt/isSelf. Không password/auth_version/remember_token/phone. Từ khóa escape wildcard SQL, sort whitelist. Mutation chặn fields role/password/accountStatus/email. Version cũ409 VERSION_CONFLICT; tự khóa409 CANNOT_LOCK_SELF; bảo vệ last active admin; lặp trạng thái hiện tại là no-op sau kiểm version, không audit hay revoke lần nữa. Lock/unlock thực đổi trạng thái đều revoke session và tăng version/auth_version, ghi audit cùng transaction. Audit giữ trạng thái lúc thao tác; tên actor/subject đọc từ tài khoản hiện tại. OpenAPI runtime85operations tại evidence/openapi-admin.json.
