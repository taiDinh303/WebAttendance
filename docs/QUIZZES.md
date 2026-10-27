# Trắc nghiệm — đặc tả trước triển khai

Ngày cập nhật: 2026-10-27. DEC-021 CONFIRMED; module NOT IMPLEMENTED. Tài liệu này không phải inventory API đã chạy.

## Quy tắc đã chốt

- Giáo viên owner tạo quiz trong lớp riêng. Học sinh chỉ tham gia khi membership active; rời/bị loại mất quyền ngay.
- Câu một đáp án hoặc nhiều đáp án. Câu nhiều đáp án so sánh tập ID lựa chọn: đúng đủ mới được điểm; chọn thiếu hoặc thừa được0.
- Giáo viên chọn thời gian và số lượt làm; server kiểm deadline và số lượt, không tin thời gian/điểm từ client.
- Thang10; điểm trung bình chỉ dùng điểm đã công bố, trọng số bài bằng nhau; chưa chấm/chưa làm không tự tính0.
- Chỉ công bố đáp án khi quiz đã đóng. Response đề bài, lưu nháp và receipt không được chứa correct flags, giải thích đáp án hoặc khóa chấm trước thời điểm này.

## Luồng thiết kế PROPOSED

1. Owner tạo draft, câu hỏi và lựa chọn. Publish tạo snapshot đề/chấm; không sửa snapshot đang có attempt.
2. Student yêu cầu bắt đầu với idempotency key. Transaction khóa bản ghi quota theo quiz/student, kiểm cửa sổ và cấp attempt; request lặp trả cùng attempt, không tiêu hao thêm lượt.
3. Student lưu đáp án theo version. Server ACK là bằng chứng đã lưu; trình duyệt có thể retry sau mất mạng. Chưa có ACK không coi là đã nộp.
4. Nộp bài idempotent. Transaction chốt câu trả lời, thời điểm server và kết quả chấm nội bộ. Worker đóng attempt quá hạn; endpoint đọc/ghi cũng kiểm deadline để không phụ thuộc worker chạy đúng phút.
5. Công bố điểm dùng snapshot bất biến và audit, giữ cùng nguyên tắc bài tập. Đóng quiz mới cho đọc đáp án, vẫn phải kiểm membership và quyền từng người.

## Cần chốt trước engine

- Nhiều lượt: điểm cao nhất, mới nhất hay giáo viên chọn? Chưa được quyết định.
- Trọng số câu: bằng nhau hay giáo viên tự chọn? Chưa được quyết định.
- Thời lượng mỗi lượt có riêng với thời gian mở/đóng quiz không? Đề xuất có; deadline thực tế là mốc sớm hơn, chưa chốt.
- Có cho xem điểm ngay sau nộp trước lúc đóng không? Đáp án chắc chắn phải ẩn; thời điểm công bố điểm chưa chốt.

## Cổng kiểm thử bắt buộc

Ownership, đáp án không lộ trong JSON/error/OpenAPI ví dụ, bắt đầu đồng thời không vượt lượt, retry không tạo hai attempt, sát hạn theo giờ server, mất mạng lưu/nộp lại, đóng lớp/loại thành viên, chấm nhiều đáp án thiếu/thừa/đúng đủ, điểm thang10 và average không cộng một quiz nhiều lần.
