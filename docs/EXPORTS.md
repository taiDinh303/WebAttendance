# Xuất Excel lớp học

## Trạng thái

2026-11-01 — TASK-015 IMPLEMENTED LOCAL. Hai báo cáo .xlsx tải trực tiếp qua session Laravel; chỉ giáo viên owner. Chưa triển khai cloud. Mã sinh viên là trường tự khai không bắt buộc của học sinh, không dùng ID hệ thống thay thế, không áp đặt duy nhất toàn hệ thống.

## Cột chung

STT, mã sinh viên (text, giữ số0đầu), họ tên, giới tính, ngày sinh dd/mm/yyyy, trạng thái thành viên (đang học/đã rời/bị loại). Không xuất email, số điện thoại hoặc nội dung audit mặc định. Profile chưa có dữ liệu thì để trống/không chia sẻ, không suy diễn.

## Điểm danh

Một cột mỗi buổi theo giờ lớp, không gồm buổi hủy. P=có mặt, L=muộn, A=vắng, ?=roster nhưng chưa chốt, —=không có roster/chưa mở. Chỉ kết quả buổi đã chốt tính tổng Có mặt/Muộn/Vắng/Buổi đã chốt. Tỷ lệ=(P+L)/(P+L+A)×100; không có mẫu số thì ô trống. Finalize các window đã quá giờ trước chụp dữ liệu, cùng quy tắc summary hiện hành; không tự tính buổi tương lai thành vắng.

## Bảng điểm

Một cột mỗi bài không phải draft; chỉ lấy snapshot được trỏ bởi submissions.published_grade_publication_id. Điểm nháp không xuất; chưa có điểm để trống, không tính0. Cuối bảng: số bài có điểm và trung bình thang10, trọng số bằng nhau. Quiz chưa triển khai nên chưa có điểm Quiz trong file. Trung bình là giá trị snapshot tại lúc xuất, không phải công thức cập nhật khi sửa ô Excel.

## Thông tin file và mẫu

Sheet chính có header màu, filter và freeze6cột thông tin + dòng tiêu đề. Sheet Thong tin có tên lớp/giáo viên/môn/học kỳ/múi giờ/thời điểm xuất và ký hiệu. Nút Mẫu trống trả workbook chỉ có header/placeholder cột động và sheet hướng dẫn, không có học sinh. Mẫu không phải chức năng import.

## API và vận hành

- GET /api/v1/classes/{classroom}/attendance/export
- GET /api/v1/classes/{classroom}/grades/export
- Query template=1 lấy mẫu trống; không query lấy dữ liệu cả lớp, không giới hạn theo pagination UI.
- Check Gate update khi bắt đầu và sau khóa lớp trong transaction. Student/teacher khác bị chặn; không cấp link tải lâu dài hoặc endpoint không xác thực.
- Response private/no-store, XLSX MIME và attachment. Temporary file server được xóa sau gửi. Không log nội dung report.
- Text viết dưới dạng OOXML inlineStr; không có công thức hoặc external link từ dữ liệu người dùng, gồm cả text bắt đầu = + - @.
- Export đồng bộ có giới hạn5000học sinh/500cột động/100000ô; vượt giới hạn trả422 trước nạp dữ liệu chi tiết. Chưa triển khai background export job vì scope thử nghiệm nhỏ; khi mở rộng phải thiết kế job có recheck quyền tải.
- Cần PHP ext-zip; thiếu extension trả503, không giả CSV thành .xlsx.
- Migration2026_11_01_120000_add_student_code_to_users cần áp dụng khi cập nhật backend. Học sinh sửa studentCode qua PATCH /me/profile với expectedVersion; teacher/admin không gán mã qua endpoint này.

## Kiểm chứng

Xem [worklog](worklog.md) và [kiểm thử](06-testing.md). Feature kiểm fixture MySQL và ZIP/XML; chưa xác nhận mở bằng Microsoft Excel thật hoặc flow browser nối API thật trên cloud. Không dùng các kiểm tra này làm bằng chứng capacity production.

2026-11-03 — DEC-046: tham khảo read-only luồng duyệt M-ChamCong, triển khai attendance table (mã HS tự khai/tên/buổi/giờ/kết quả/duyệt), teacher thêm/sửa manual có reason, Duyệt từng hoặc checkbox chọn trang và duyệt hàng loạt tuần tự với version/confirm/partial failure. approved_at/approved_by thêm vào attendance_records (migration2026_11_03_084459), approval audit và khóa API mọi sửa/quét ghi đè; QR duplicate trả ALREADY_CHECKED_IN, chỉ auto_absent chưa duyệt/chưa từng QR được ghi nhận lần đầu khi mở lại. Bản ghi cũ mặc định chờ duyệt, không auto-approve. Summary/Excel chỉ tính approved và window đã đóng/hết hạn. QR modal wide chỉ danh sách tên present/late và checkedInCount toàn bộ có phân trang, bỏ chỉnh sửa. VERIFIED local migration trên attendance_test, PHP27tests/PintPASS, React45unit/lint/buildPASS, Edge table/bulk confirm+partial retry/lock/mobile, manual add, QR count42/no-edit/name-nowrapPASS; đã xem ảnh local. Không cloud migration/deploy hoặc sửa dữ liệu thật. Cần backend migrate+release trước frontend dùng tính năng mới.
