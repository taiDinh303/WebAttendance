# 05 — Luồng nghiệp vụ và trạng thái

> 2026-10-26 | TARGET DESIGN | Các giá trị TTL/giới hạn là PROPOSED tại intake. Actor lấy từ phiên server; kiểm quyền lại tại mỗi bước ghi. Thời gian dùng clock server.

## FLOW-001 — Tài khoản

1. Khách chọn teacher/student, đăng ký email/password/displayName; không được gửi admin hoặc ownerId.
2. Server validate/unique, hash password, tạo account và verification challenge đề xuất. Email gửi queue; retry gửi không tạo thêm account.
3. Xác minh một lần, login regenerate session, client gọi `/me` để lấy profile/quyền.
4. Profile chỉ self; đổi password cần current password, reset cần token có expiry, revoke sessions theo chính sách đã chốt.
5. Logout invalidate phiên và CSRF state phù hợp; tài khoản bị khóa bị chặn REST và realtime lần kiểm tiếp theo.

Ngoại lệ: đăng ký email trùng không lộ dữ liệu hồ sơ; forgot trả thông báo chung; gửi mail lỗi cho retry có giới hạn. DEC-009 chọn một role; chưa Google OAuth. CURRENT cho unverified login để đọc me/resend/logout, nhưng chặn toàn workspace đến khi verify. Mail local array không gửi email thật.

## FLOW-002 — Danh mục và lớp

Teacher tạo học kỳ/môn thuộc scope đã chốt → tạo lớp → server gán owner từ phiên → cấp mã mời riêng ID → hiển thị mã cho owner. Owner có thể tắt/đổi mã; mã cũ hết hiệu lực. Lớp archive không nhận yêu cầu mới/ghi hoạt động mới; lịch sử hiển thị theo quyền và Q-020. Đổi owner/co-teacher chưa thuộc V1. Xóa danh mục đang dùng trả conflict; archive thay xóa dữ liệu có lịch sử.

## FLOW-003 — Vào lớp và duyệt

| Từ | Hành động/actor | Đến | Tác động |
| --- | --- | --- | --- |
| Chưa membership | HS nhập mã hợp lệ của lớp active | pending | Tạo request + event + notification owner đúng một lần |
| pending | HS gửi cùng mã lại | pending | Trả record cũ, không notification lặp |
| pending | Owner approve | active | Set reviewed_by/joined_at; notification HS |
| pending | Owner reject + ghi chú | rejected | Notification HS với thông tin tối thiểu |
| pending | HS cancel request | left | Không có quyền nội dung |
| active | HS leave | left | Ngừng quyền hoạt động mới, dữ liệu giữ theo retention |
| active | Owner remove | removed | Audit + thu hồi quyền và channel |
| rejected/left | HS gửi mã hợp lệ lại | pending | Cùng membershipId, version tăng, teacher vẫn phải duyệt |
| removed | Owner allow-rejoin có version | left | Sau đó HS mới request mã lại; không tự active |

Approval transaction lock membership, kiểm owner hiện tại, class active, state pending; hai lần duyệt cùng ý trả trạng thái hiện tại, approve/reject đối nghịch trả409. HS pending chỉ thấy tên lớp tối thiểu sau mã hợp lệ và trạng thái self; không roster/bản tin/điểm. Import/thêm trực tiếp được thiết kế là chuẩn bị roster/invitation, không tự bypass quy tắc chủ dự án đã chốt.

## FLOW-004 — Lịch và buổi học

Owner chọn lớp/ngày/giờ thực → preview recurrence/conflict → xác nhận → tạo sessions trong transaction. DEC-015 đã chốt. Lặp theo timezone lớp, range≤366ngày/≤104buổi; calendar paginated range≤93ngày. Overlap409 trừ khi gửi allowConflicts=true. Sửa/hủy từng occurrence riêng; chưa sửa hàng loạt cả series. Không reschedule buổi đã có attendance window. Hủy giữ history, đóng QR và loại mẫu số report; notification lịch là phần sau, chưa broadcast. Create chưa Idempotency-Key chung, không tự retry sau timeout; policy/limits xem SCHEDULE-ATTENDANCE.

## FLOW-005 — QR attendance

1. Owner mở buổi hợp lệ, chọn opensAt/lateAfter/closesAt. API kiểm owner, thời gian, session không cancelled và không có cửa sổ open khác.
2. Server tạo window và challenge nonce ngẫu nhiên/hash; QR chỉ chứa token opaque/URL HTTPS, không credentials. Token luân phiên theo TTL đề xuất; không một-token-một-học-sinh vì QR dùng chung cả lớp.
3. HS quét bằng camera hoặc mở URL QR trên thiết bị đã login; nếu chưa login giữ đường dẫn an toàn để trở lại, không ghi attendance trước auth.
4. API resolve token và lock window: kiểm token/version/expiry, window open và clock trong khoảng, membership active, session thuộc lớp.
5. Unique `(session,student)`; check-in lần đầu tạo present/late theo giờ server + outbox. Lần lặp trả cùng record, không đẩy present thành late khi gửi lại.
6. Owner đóng hoặc đến closesAt: request ghi tiếp bị chặn ngay cả worker chưa chạy. Finalize tạo absent cho thành viên thuộc snapshot roster hợp lệ của buổi theo Q-011; late/present đã ghi không đổi. Worker idempotent.

DEC-016 chốt roster lúc mở; HS approve sau đó không tự vào roster của window hiện tại. Rời/bị loại vẫn giữ record lịch sử, nhưng mất quyền read/check-in ngay. Race close/check-in dùng chung parent/window locks và clock server; sau close không nhận check-in dù camera quét trước. Phase2 chưa reopen QR, teacher dùng manual correction cho ngoại lệ theo DEC-017.

Lỗi: token expired → quét mã hiện tại; rotation30s vẫn có overlap của token cũ đến expiry60s. Không active/snapshot → không ghi; clock client sai không ảnh hưởng; gửi lại cùng token còn hiệu lực trả record cũ, không đổi thời gian/trạng thái. Đóng tab giáo viên không tự đóng window. Không GPS/IP. QR có thể bị chia sẻ trong TTL, không cam kết chống điểm danh hộ tuyệt đối.

**FLOW-005M — Sửa tay theo DEC-017:** owner chọn HS trong snapshot của buổi và status present/late/absent, gửi reason≥3 ký tự và expectedVersion (0 khi chưa có record). Server lock class→session→window→record, kiểm scope/state/version; lưu audit before/after + actor/time/reason trong cùng transaction. Không giả lập checkedInAt cho record tạo bằng tay; giữ mốc QR thật nếu record đã check-in. Student không được sửa hoặc đọc audit nội bộ. QR gửi lại và finalizer không ghi đè manual record. Teacher có thể sửa sau đóng; chưa có reopen QR trong phase này. Không sửa buổi cancelled/class archived.

## FLOW-006 — Bản tin và bình luận

Owner soạn draft → publish → tạo notification thành viên active. Sửa nội dung có version; comment của active member có giới hạn/sanitize; owner moderation trong lớp, HS chỉ comment self nếu cho phép. Archive bản tin không xóa notification nhưng mở deep link phải kiểm quyền/tồn tại và hiển thị đã gỡ. Notification không chứa toàn bộ nội dung nhạy cảm.

## FLOW-007 — Bài tập và nộp file

Owner tạo draft + instructions/maxScore/deadline/chính sách → upload file đề sạch → publish. HS active xem bài published, tải đề qua kiểm quyền. HS upload vào khu staged theo assignment → server xác minh MIME/size/quota/scan → submit danh sách fileId thuộc self và đúng assignment → transaction tạo revisionNo tăng, submittedAt/isLate theo server. UI chỉ báo đã nộp sau ACK có revisionId.

Draft file upload không đồng nghĩa đã nộp. Scan pending chưa submit; scan lỗi có retry an toàn hoặc yêu cầu thay file. Submit quá hạn bị chặn nếu allowLate=false; nếu cho phép đánh late. Nộp lại không ghi đè file revision cũ; chấm/feedback tham chiếu revision cụ thể. Khi nộp revision mới, điểm revision cũ vẫn gắn cũ, teacher thấy cần chấm lại, không tự gán điểm cũ. Mất mạng giữ nội dung form cục bộ phù hợp nhưng không ghi đã nộp; retry key chống trùng. Q-013 chốt limits/late/resubmit.

## FLOW-008 — Chấm và công bố

Owner mở submission đúng lớp → chọn revision → nhập score0…10/feedback → lưu draft grade với version → publish → notification HS (TARGET learning, chưa code). HS không đọc draft score dù đoán ID/JSON/average. DEC-018/019: mọi bài thang10, trung bình số học các điểm published với trọng số bằng nhau; chưa chấm/chưa nộp chưa tự0, không có điểm thì average=null. Sửa published grade có reason/version/audit theo thiết kế; gradebook HS lọc self, teacher chỉ lớp mình. Không maxScore tùy bài hoặc trọng số khác nhau ở bản đã chốt.

## FLOW-009 — Trắc nghiệm

1. Owner soạn quiz, câu, lựa chọn và key riêng server; preview chế độ teacher; publish đóng snapshot/version.
2. HS active start khi opensAt≤now<closesAt, còn lượt. Transaction trả attempt cũ đang làm thay tạo lượt thứ hai do double click.
3. Server tính expiresAt=min(start+duration, closesAt), trả questions/options không key/isCorrect/giải thích làm lộ đáp án.
4. HS chọn đáp án; autosave có version/sequence, chỉ hiển thị Đã lưu khi server ACK. Lưu không được kéo dài expiry.
5. Submit hoặc timeout → lock attempt, chốt đúng một lần, chấm snapshot server. Worker timeout fallback; request sau expiry vẫn không sửa được dù worker chậm.
6. Điểm/đáp án hiển thị theo Q-014; chưa công bố thì Resource ẩn dữ liệu kết quả không chỉ nút UI.

Đề xuất một lượt; single-choice/multiple-choice tất cả-đúng, không điểm âm. Quiz đã published không sửa snapshot có attempt; tạo version mới cần chính sách lượt riêng. Refresh/reconnect phục hồi attempt cùng deadline. Mất mạng chỉ giữ bản nhập chưa ACK, không cam kết đã lưu; không nhận timestamp client làm bằng chứng đúng hạn.

## FLOW-010 — Chat

Teacher/HS active mở thread theo lớp; backend resolve đúng teacher owner và HS, không nhận danh sách participant tùy ý. Lịch sử cursor cũ hơn; gửi text với clientMessageId → transaction lưu message sequence + outbox → ACK → broadcast private participants. Client dedup ID khi ACK và event đến đảo thứ tự. Reconnect fetch sau sequence cuối; read marker chỉ tăng. Bị remove/lock thì chặn gửi ngay và thu hồi subscription; việc đọc lịch sử cũ chờ Q-020. Chưa group chat/file/edit/delete do Q-015 mở.

## FLOW-011 — Import/export

Owner upload roster template → validate header/dòng/duplicate/user mapping → preview valid/invalid → xác nhận batch → apply idempotent → báo kết quả từng dòng. Không dùng import để tự đổi role/mật khẩu hoặc gửi mail thật hàng loạt. Account chưa có xử lý invitation sau Q-009/022.

Owner yêu cầu export với filter snapshot → job kiểm quyền → tạo private file → notification ready → download kiểm quyền hiện tại và expiry. HS export chỉ self. Escape spreadsheet formula bắt đầu `= + - @` theo định dạng để tránh formula injection. Report total khớp tập dữ liệu lọc; không chỉ xuất page đang xem trừ chọn rõ.

## FLOW-012 — Notification

Join requested→owner; approved/rejected→HS; assignment/quiz published→active members; submission→owner; grade/result published→HS; chat→recipient theo thiết lập. Mỗi recipient/event_key duy nhất; queue retry không nhân đôi. In-app DB lưu trước event, unread count lấy server; đánh read là self idempotent. Deep link recheck quyền; WS bị mất không mất notification. Email/push/reminder schedule chờ Q-016.
