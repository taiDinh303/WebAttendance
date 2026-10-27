# 02 — Phân tích nghiệp vụ và đặc tả yêu cầu

> 2026-10-26 | TARGET | Phạm vi REQ được xác nhận theo intake; chi tiết RULE ghi PROPOSED là thiết kế chờ chốt.

DEC-009–014 đã chốt và bắt đầu core backend: một role/tài khoản, teacher tự quản catalog/lớp optional môn/học kỳ; MySQL; left/rejected request lại, removed phải teacher cho phép; rời/bị loại ngừng truy cập ngay. Scope CURRENT ở API-CURRENT, các phần còn lại dưới đây vẫn TARGET.

## Vai trò và quyền

Khách chỉ xem landing/auth. Teacher sở hữu lớp. Student là thành viên lớp sau duyệt; cùng học sinh có thể tham gia nhiều lớp. Admin quản lý nền tảng; phạm vi đọc nội dung chờ Q-021. Một tài khoản có nhiều role hay không chờ Q-009.

| Tài nguyên/hành động | Teacher owner | Teacher khác | Student active | Pending/removed | Admin đề xuất |
| --- | --- | --- | --- | --- | --- |
| Lớp/roster/lịch | CRUD phạm vi lớp, archive | Không | Xem lớp/lịch; roster tên tối thiểu nếu cho phép | Chỉ yêu cầu của mình | Metadata hỗ trợ; nội dung chờ Q |
| Yêu cầu vào lớp | Xem/duyệt/từ chối | Không | Gửi yêu cầu bản thân, xem trạng thái | Xem/hủy yêu cầu bản thân | Không tự duyệt thay owner |
| Điểm danh | Mở/đóng QR, xem cả lớp | Không | Ghi self bằng QR hợp lệ; xem self | Không | Không tự sửa công |
| Bản tin/bình luận | CRUD bản tin; moderation lớp | Không | Đọc/bình luận; chỉ sửa/xóa comment self nếu bật | Không | Theo Q-021 |
| Bài tập và bài nộp | Quản lý bài, xem/chấm bài lớp | Không | Xem bài published; nộp/xem revision self | Không | Không mặc định đọc file |
| Điểm/quiz answers | Chấm, công bố, xem lớp | Không | Chỉ điểm published self; không thấy đáp án sớm | Không | Không mặc định đọc |
| Chat | Chỉ thread có mình và HS lớp mình | Không | Chỉ thread có mình | Không gửi mới; đọc cũ chờ Q-020 | Không mặc định đọc |
| Notification | Inbox self | Inbox self | Inbox self | Inbox self với payload tối thiểu | Inbox self |

Mọi quyền kiểm trên server; đổi ID/filter/URL/channel không làm tăng quyền. Permission kiểm cả quan hệ lớp→buổi/bài/attempt/file, không chỉ owner trực tiếp.

## Danh mục yêu cầu

| REQ | Nội dung và tiêu chí chấp nhận cốt lõi | FLOW / TC |
| --- | --- | --- |
| REQ-001 | Đăng ký teacher/student; đăng nhập/đăng xuất; xác minh email/quên/đổi mật khẩu đề xuất. Không tự cấp admin. Phiên bị thu hồi có hiệu lực | FLOW-001; TC-001/002 |
| REQ-002 | Hồ sơ riêng, avatar; đổi email phải xác minh đề xuất; không cập nhật role/owner từ mass assignment | FLOW-001; TC-003 |
| REQ-003 | Học kỳ, môn, loại môn và dữ liệu tín chỉ/tiết nếu dùng; tách quyền teacher. Scope danh mục chờ Q-010 | FLOW-002; TC-004 |
| REQ-004 | Tạo/xem/sửa/archive lớp, tìm/lọc học kỳ, mã mời bật/tắt/đổi. Lớp độc lập teacher | FLOW-002; TC-004/020 |
| REQ-005 | Nhập mã tạo yêu cầu pending; teacher xem queue và duyệt/từ chối; chỉ approved active mới truy cập lớp. Thêm/import roster cần consent/onboarding, không bypass duyệt một cách ngầm | FLOW-003; TC-005/006 |
| REQ-006 | Lịch giáo viên và HS theo lớp active, buổi học, học bù/hủy và cảnh báo chồng giờ. Lặp tuần đề xuất | FLOW-004; TC-014 |
| REQ-007 | Student self check-in QR; teacher mở/đóng phiên, sửa tay có lý do/audit. Chống trùng/hết hạn, snapshot roster, lịch sử self và bảng owner | FLOW-005; TC-007/008/009, DEC-016/017 |
| REQ-008 | Bản tin/thông báo lớp, sửa/xóa, bình luận và moderation; không nhầm bản tin lớp với inbox notification | FLOW-006; TC-016 |
| REQ-009 | Bài tập nháp/published/closed; file đề bài; HS nộp file, lịch sử lần nộp, xác nhận server. Chính sách muộn/nộp lại Q-013 | FLOW-007; TC-010/017 |
| REQ-010 | Teacher chấm/feedback/công bố trên thang10; HS chỉ điểm published self; gradebook theo lớp và trung bình điểm published trọng số bằng nhau, chưa chấm/chưa nộp không tự0 | FLOW-008; TC-011, DEC-018/019 |
| REQ-011 | Thống kê attendance/điểm; export Excel và import roster có preview lỗi; bản export giữ đúng filter/quyền | FLOW-011; TC-009/020 |
| REQ-012 | Quản trị tài khoản, khóa/mở và audit. Không tự áp quyền đọc nội dung lớp/chat | FLOW-001; TC-003/019 |
| REQ-013 | Tạo quiz/câu/lựa chọn, xuất bản, lượt làm, lưu đáp án, nộp/chấm, trả kết quả theo lịch | FLOW-009; TC-012/013 |
| REQ-014 | Chat GV–HS; scope thread rõ, lịch sử phân trang, unread, gửi lại không nhân đôi. Hình thức nhóm/file chưa chốt | FLOW-010; TC-015/019 |
| REQ-015 | Inbox thông báo riêng: join request, approved/rejected, bài mới, nộp bài, điểm công bố, chat; đọc/chưa đọc, liên kết đúng quyền | FLOW-012; TC-018 |
| REQ-016 | Tất cả collection có pagination, sort/filter ổn định, trạng thái rỗng/lỗi; responsive và điều hướng theo role | Mọi FLOW; TC-020/021 |

## Quy tắc có thể triển khai sau khi chốt chi tiết

| RULE | Quy tắc | Trạng thái |
| --- | --- | --- |
| RULE-001 | Identity server lấy actor, không tin userId/teacherId/studentId ở client; query luôn scoped trước phân trang/count | CONFIRMED theo yêu cầu riêng tư |
| RULE-002 | Unique membership `(class_id,student_id)`, duyệt đúng một lần; người pending chưa xem nội dung lớp | CONFIRMED về duyệt; constraint PROPOSED |
| RULE-003 | Mã mời ngẫu nhiên khác ID, lưu hash, rotate vô hiệu mã cũ, rate limit không lộ lớp qua brute force | PROPOSED |
| RULE-004 | QR chỉ ghi cho actor active trong roster snapshot đúng lớp/buổi; window open; token đổi30s/TTL60s chưa hết hạn; unique buổi–HS | CONFIRMED DEC-016; HMAC token là lựa chọn implementation |
| RULE-005 | present/late/absent; chưa ghi trước đóng là unmarked; now≥lateAfter là late, now≥closesAt không check-in. Buổi cancelled loại mẫu số. Sửa tay có lý do, expectedVersion và audit, không bịa thời gian check-in | CONFIRMED hành vi DEC-016/017; chi tiết boundary/audit triển khai theo nguyên tắc server |
| RULE-006 | Điểm0…10, unpublished không trả HS/không tính average; average=sum(published scores)/count, không có điểm thì null; chưa nộp/chưa chấm không tự0. Revision bất biến vẫn thiết kế chờ module nộp bài | CONFIRMED DEC-018/019 cho thang/average; revision/limits chờ Q-013 |
| RULE-007 | Quiz snapshot sau publish; attempt dùng clock server, client không tính điểm/không nhận isCorrect trước khi được phép | PROPOSED |
| RULE-008 | Rời/bị loại mất quyền read/write lớp ngay; lịch sử giữ server cho teacher, thời hạn lưu chờ Q-025 | CONFIRMED DEC-013; core đã test |
| RULE-009 | Notification/outbox cùng transaction nghiệp vụ, dedup khi retry; WebSocket chỉ là kênh cập nhật, DB nguồn chuẩn | PROPOSED |
| RULE-010 | File private, kiểm MIME/size/quota/quyền quan hệ, không URL công khai; export/scan job recheck quyền | PROPOSED |
| RULE-011 | Ghi thời gian UTC, lịch lớp có timezone IANA mặc định đề xuất Asia/Ho_Chi_Minh; hiển thị theo lớp, server quyết deadline | PROPOSED |
| RULE-012 | List thường pageNumber/pageSize (1–100, default20), chat cursor; không tải hết rồi phân trang browser. Search count không rò dữ liệu lớp khác | CONFIRMED phân trang; thông số PROPOSED |

## Phạm vi sau / chưa được yêu cầu

Chưa có thanh toán, phụ huynh, multi-school tenant, AI, mobile app native, họp video, chống gian lận bằng camera/GPS/IP, attendance offline, đồng giảng hoặc SCORM. Không đưa các phần này vào release mặc định. DEC-017 đã cho phép sửa tay bởi teacher owner, có audit; học sinh không có đường bypass QR.
