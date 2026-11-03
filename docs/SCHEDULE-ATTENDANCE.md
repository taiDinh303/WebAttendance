# Lịch học và điểm danh — API phase2

> 2026-10-27 | CURRENT IMPLEMENTATION | DEC-015/016/017. Frontend atd_react vẫn rỗng; không có màn hình/camera/ảnh QR được dựng trong đợt này.

## Quyết định người dùng

- Lịch theo giờ thực, hỗ trợ lặp tuần, sửa/hủy một buổi không đổi những buổi còn lại.
- Teacher chọn giờ mở/đóng/mốc muộn; QR đổi30giây, hiệu lực tối đa60giây; roster tự bổ sung học sinh đã duyệt khi cửa sổ đang mở (DEC-045). Hết window mới tạo absent.
- Teacher owner được sửa tay; quyết định này thay giới hạn QR-only cũ. Student vẫn chỉ tự check-in bằng QR. Không GPS/IP/nhận diện mặt.

Các giới hạn366ngày/104occurrences, duration24h, reason/version và cách từ chối giờ DST mơ hồ là lựa chọn triển khai kỹ thuật, không giả thành câu trả lời phỏng vấn của người dùng.

## Dữ liệu hiện hành

Migration `2026_10_26_171007_create_schedule_and_attendance_tables.php` (tên theo UTC lúc generate, ngày công việc tại Asia/Bangkok là27/10) thêm6 bảng nghiệp vụ; migration `2026_10_26_173338_create_schedule_owner_locks_table.php` thêm mutex kỹ thuật cho lịch:

| Bảng | Ý nghĩa/ràng buộc |
| --- | --- |
| session_series | Definition lặp tuần + timezone lớp tại lúc tạo; các occurrence là class_sessions riêng |
| class_sessions | class_id, series_id?, title/room?, starts_at/ends_at UTC, scheduled/cancelled, cancel_reason, version |
| attendance_windows | Unique session_id, opened_by, opens_at/late_after?/closes_at, state, token_version, version |
| attendance_rosters | Danh sách tích lũy student_id theo window, unique(window,student); bổ sung thành viên active khi mở/đang mở, giữ lịch sử |
| attendance_records | Unique(session,student), window_id, status, checked_in_at?, recorded_source, version |
| attendance_audits | Append-only qua API: record_id, actor_id nullable cho system, action/reason, before_data/after_data, timestamp |
| schedule_owner_locks | Mutex row theo teacher, serialize schedule writes giữa các lớp cùng owner; tách khỏi users row để không tạo chu kỳ lock với FK audit/membership |

Không tạo bảng qr_challenges: token là payload ký HMAC, gồm windowId/token_version/issuedAt/expiresAt; không chứa tên/email/điểm. Không ghi token vào DB/audit. APP_KEY phải giữ secret; thay key làm token cũ không còn hợp lệ.

## API lịch

| Method/path dưới /api/v1 | Quyền và body/query |
| --- | --- |
| GET `/calendar` | Teacher own classes; student active memberships; from/to ISO có offset bắt buộc, classId?/status?/pageNumber/pageSize |
| GET `/classes/{classroom}/sessions` | Owner/active member; cùng range/pagination như calendar |
| GET `/classes/{classroom}/sessions/{sessionId}` | Owner/active; luôn kiểm parent class–session |
| POST `/classes/{classroom}/sessions` | Owner: startsAt,endsAt,title?,room?,allowConflicts? |
| PATCH `/classes/{classroom}/sessions/{sessionId}` | Owner: startsAt,endsAt,expectedVersion,title?,room?,allowConflicts? |
| POST `…/sessions/{sessionId}/cancel` | Owner: expectedVersion,reason bắt buộc |
| POST `/classes/{classroom}/session-series/preview` | Owner: startDate,endDate,weekdays,startTime,endTime,title?,room?,allowConflicts? |
| POST `/classes/{classroom}/session-series` | Owner: cùng definition; trả seriesId và sessionCount, sau đó lấy list có phân trang |

Ví dụ definition tuần:

```json
{"startDate":"2026-11-03","endDate":"2026-11-24","weekdays":[2,4],"startTime":"09:00","endTime":"10:30","title":"Toán","room":"A1"}
```

weekdays ISO1=thứHai…7=ChủNhật. start/endTime cùng ngày, end>start; lịch qua đêm chỉ tạo buổi riêng với timestamps đầy đủ trong phase này. Timezone lấy từ lớp, giữ giờ địa phương qua DST; gap/fold mơ hồ bị422 AMBIGUOUS_LOCAL_TIME. Không tự đoán giờ lặp khi DST thay đổi. Range lặp≤366ngày và≤104buổi, preview hữu hạn không có DB writes. Calendar range≤93ngày, phân trang1–100/default20, order starts_at+id. Một buổi dài≤24h; UTC timestamps chuẩn hóa giây theo precision MySQL.

Overlap dùng khoảng [start,end), kiểm các buổi scheduled của teacher kể cả lớp khác; mặc định409 SCHEDULE_CONFLICT. Preview nêu hasConflict; tạo/sửa có allowConflicts=true là xác nhận cho phép trùng. Mutex schedule_owner_locks serialize các thay đổi lịch cùng teacher, không lock users row rồi chờ class. Create series atomic, không thêm một phần khi có lỗi; chưa có Idempotency-Key chung cho create, không auto-retry khi timeout.

Sửa/hủy từng occurrence không sửa definition hoặc các occurrence khác. Không đổi lịch buổi đã mở attendance window (kể cả chưa ai quét) để giữ lịch sử. Hủy giữ record, đóng window/vô hiệu token, loại buổi khỏi tổng hợp; không hard-delete lịch sử. Class archived không nhận thay đổi lịch. Archive class đang có window còn hiệu lực trả409, teacher cần đóng trước.

## API điểm danh

| Method/path dưới /api/v1 | Quyền / input |
| --- | --- |
| POST `/classes/{classroom}/sessions/{sessionId}/attendance-windows` | Owner: opensAt,closesAt,lateAfter? |
| GET `…/sessions/{sessionId}/attendance-window` | Owner, trạng thái cửa sổ; finalize overdue trước đọc |
| GET `/attendance-windows/{windowId}/qr` | Owner: qrToken,validUntil,rotatesAt,serverTime; no-store/private |
| GET `/attendance-windows/{windowId}/roster` | Owner, danh sách cập nhật khi đang mở, paginated; studentId/name/windowId/recordId/status/recordVersion; q tìm tên |
| POST `/attendance-windows/{windowId}/close` | Owner: expectedVersion; lặp close không nhân bản absences/audit |
| POST `/attendance/check-ins` | Student: qrToken, không nhận studentId/status/thời gian từ client |
| GET `/classes/{classroom}/attendance` | Owner paginated; sessionId?,studentId?,status?,from/to? |
| GET `/me/attendance` | Student self, chỉ lớp còn active membership; classId?/sessionId?/status?/from/to?; không đọc HS khác |
| PUT `/classes/{classroom}/sessions/{sessionId}/attendance/{studentId}` | Owner: status,reason,expectedVersion; chỉ HS trong danh sách hiện hành của window |
| GET `/classes/{classroom}/attendance/{recordId}/audit` | Owner, audit paginated; student không được đọc audit nội bộ |
| GET `/classes/{classroom}/attendance-summary` | Owner, tổng hợp paginated theo HS; studentId?/sessionId?/from/to? |

Open window phải chứa clock server: opensAt≤now<closesAt, duration≤24h, opensAt≤lateAfter≤closesAt nếu có. Danh sách lấy active membership và account active trong transaction cùng lock class. Thành viên active được check-in với QR hợp lệ dù chưa có roster; server bổ sung row sau khi kiểm token. Mở/mở lại, lấy QR, xem roster đang mở và finalize bổ sung active members. Member pending/removed/left vẫn bị chặn theo membership hiện tại. Finalize không thêm HS tham gia sau closesAt; window đã đóng không tự đổi lịch sử. DEC-045 thay snapshot cố định DEC-016/044.

Một window mỗi buổi; POST mở lại với cùng cấu hình đang open trả window cũ; đổi cấu hình/mở sau đóng trả409. Chưa reopen QR; teacher có thể sửa tay cho ngoại lệ theo lựa chọn mới. Rời trang teacher không đóng window. QR GET trả token để frontend mã hóa thành QR sau này, chưa trả ảnh PNG/SVG hoặc làm scanner UI.

Token thay theo slot30giây; expires=min(slotStart+60s,closesAt), token cũ còn dùng trong overlap đến expiry. Chỉ owner lấy được token; server kiểm HMAC, actor, class/session/window, membership hiện tại, trạng thái và thời gian. now≥expires hoặc closesAt trả410; window đóng trước hạn trả409 WINDOW_CLOSED. Không tin clock/status/studentId client. Unique buổi–HS và parent-row locks chống duplicate. QR lặp còn hiệu lực trả record cũ200, không ghi đè status/timestamp hoặc record đã sửa tay. Check-in đầu trả201; checkedInAt lưu chuẩn hóa giây, response đầu và replay khớp DB.

Rate limiter check-in30request/min theoactor,600/min theoIP để không chia quota30cho cả lớp cùngWi-Fi; IP chỉ chống abuse, không dùng quyết định có mặt/vị trí. Chưa có cam kết capacity từ test31HS tuần tự cùngIP.

## Sửa tay và audit

```json
{"status":"present","reason":"Thiết bị học sinh gặp lỗi","expectedVersion":1}
```

Nếu chưa có record, expectedVersion=0; nếu đã có dùng version gần nhất. reason3–500ký tự; status present/late/absent. Server lock class→session→window→record, chỉ owner lớp active/buổi không cancelled/HS snapshot được sửa. Version cũ409. Audit cùng transaction gồm actor/time/reason và before/after (status/source/checkedInAt/version). Không sửa/xóa audit qua API.

Sửa tay tạo mới không bịa thời gian quét (checkedInAt=null). Nếu record đã có mốc QR thì giữ mốc thật, dù teacher đổi status thành absent; audit giải thích vì sao status khác. Source chuyển manual; QR replay/finalizer không ghi đè. Teacher vẫn sửa được sau đóng window, kể cả HS đã rời/bị loại nhưng thuộc roster snapshot; HS mất quyền đọc lớp ngay theo DEC-013.

## Chốt vắng và thống kê

`php artisan attendance:finalize` xử lý window hết hạn theo batch100, lặp đến hết; schedule đăng ký everyMinute/withoutOverlapping. Cần chạy `php artisan schedule:work` local hoặc scheduler production phù hợp. Không có worker/scheduler process mới nào tự được cài thành Windows service. Endpoint window/roster/attendance/summary có fallback finalize overdue đúng scope; drain hết backlog, không trả totals thiếu khi có hơn100window quá hạn.

Chỉ tạo absent cho roster chưa có record, không tạo trước đóng/hết hạn; skip record QR/manual đã có. Auto audit actorId=null/action auto_absent, không giả thành teacher vừa sửa. Gọi lặp không tăng record/audit. Session cancelled không tạo vắng mới và loại khỏi summary, giữ records cũ để teacher xem history.

Summary chỉ cửa sổ đã đóng/hết hạn và buổi không cancelled. P/L/A từ record hiện tại sau correction; total=P+L+A; attendanceRate=(P+L)/total (0…1), không phải phần trăm100. from/to phải cùng có, range≤366ngày, lọc theo startsAt buổi; status filter bị422 vì làm sai nghĩa mẫu số. Paginated records không dùng số row page làm mẫu số. HS xem lịch sử self, chưa có self-summary riêng hoặc Excel ở phase này.

## Kiểm chứng và phần còn lại

Feature tests và HTTP fixture chạy trên MySQL synthetic riêng; kết quả cuối ở [evidence/schedule-attendance-validation.md](evidence/schedule-attendance-validation.md). Đã kiểm clock boundary/TTL/late/snapshot/owner/self/version/audit/DST/cancel/report/backlog; không tuyên bố đã stress concurrency hoặc camera/browser thật. Reverb/attendance notifications realtime, Excel, co-teacher, bulk corrections, xin nghỉ/excused và reopen window chưa triển khai. Các module bài tập/quiz/chat vẫn cần câu trả lời riêng, không suy ra từ quyết định lịch/QR.

2026-11-03 — UI lịch: nhãn theo giờ bắt đầu/kết thúc (Sắp diễn ra/Đang diễn ra/Đã kết thúc), ưu tiên Đã hủy; auto refresh30giây và quay lại tab. Đây là nhãn hiển thị, không đổi trạng thái schedule DB hoặc tự chốt vắng/QR. Chi tiết FRONTEND-IMPLEMENTATION.

2026-11-03 — Mở lại QR (CONFIRMED theo yêu cầu chủ dự án; IMPLEMENTED/VERIFIED local): owner lớp active/buổi không hủy được mở lại window closed/expired qua POST attendance-windows cùng expectedVersion hiện tại. Giữ window/roster ban đầu; tăng version và token_version, thay khoảng giờ hợp lệ, mã cũ vô hiệu. QR check-in chỉ cập nhật record absent nguồn close_finalize, giữ record ID và audit trước/sau; record QR/manual không ghi đè. Không reset lịch sử hoặc bổ sung roster. UI Mở lại QR→form giờ→active trong modal lịch. Backend19tests267assertions/PintPASS; frontend lint/build và Edge versioned-reopen fixturePASS. Không migration; cần backend mới trên production trước khi dùng, chưa cloud verification.

2026-11-03 — Theo yêu cầu xóa buổi QR không học sinh: cho owner xóa window trống (0roster,0records), dù đang mở/đã đóng; khóa class/session/window cùng transaction và expectedVersion, xóa window trước session để giữ FK. Có roster/records vẫn chặn SESSION_HAS_ATTENDANCE với tiếng Việt. Calendar row delete lỗi chỉ MutationStatus góc phải dưới qua ActionButton errorPlacement=popup; không giãn hàng. VERIFIED backend20tests284assertions/Pint và frontend lint/build/Edge popup-only409PASS. Không migration/không xóa dữ liệu cloud; cần backend deploy mới.

2026-11-03 — DEC-046: tham khảo read-only luồng duyệt M-ChamCong, triển khai attendance table (mã HS tự khai/tên/buổi/giờ/kết quả/duyệt), teacher thêm/sửa manual có reason, Duyệt từng hoặc checkbox chọn trang và duyệt hàng loạt tuần tự với version/confirm/partial failure. approved_at/approved_by thêm vào attendance_records (migration2026_11_03_084459), approval audit và khóa API mọi sửa/quét ghi đè; QR duplicate trả ALREADY_CHECKED_IN, chỉ auto_absent chưa duyệt/chưa từng QR được ghi nhận lần đầu khi mở lại. Bản ghi cũ mặc định chờ duyệt, không auto-approve. Summary/Excel chỉ tính approved và window đã đóng/hết hạn. QR modal wide chỉ danh sách tên present/late và checkedInCount toàn bộ có phân trang, bỏ chỉnh sửa. VERIFIED local migration trên attendance_test, PHP27tests/PintPASS, React45unit/lint/buildPASS, Edge table/bulk confirm+partial retry/lock/mobile, manual add, QR count42/no-edit/name-nowrapPASS; đã xem ảnh local. Không cloud migration/deploy hoặc sửa dữ liệu thật. Cần backend migrate+release trước frontend dùng tính năng mới.

2026-11-03 — DEC-047 chốt cả3câu Có: MonthCalendar ở Lịch học/Điểm danh, mặc định cả tháng; nhấn ngày lọc from/to, Cả tháng reset, đổi filter reset checkbox/page. Ngày theo ngày bắt đầu buổi ở timezone thiết bị; startWithin giữ overlap contract mặc định của API cũ. Lấy đủ trang tháng; gộp ngày xanh chỉ khi mọi buổi window closed/expired và có record, tất cả records/roster được duyệt; còn lại đỏ. No session mờ/disabled/cursor default, cancelled không tính. API SessionResource thêm attendanceReviewState, subquery aggregate theo session có scope quyền cũ, không trả count/tên học sinh. Giãn toolbar checkbox20px trên/16px dưới. VERIFIED backend24tests357assertions/Pint, React47unit/lint/build, Edge2tabs màu đủ trang/filter/reset/disabled/mixedday/mobilePASS; đã xem ảnh và sửa class empty trùng CSS để lịch gọn40px mỗi ô. Không migration/cloud verification, cần backend mới để màu approved chính xác.
