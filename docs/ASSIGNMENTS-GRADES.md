# Bài tập, file và điểm — trạng thái triển khai

Cập nhật 2026-10-27. DEC-018/019/020 CONFIRMED. IMPLEMENTED / VERIFIED LOCAL: full suite51tests/574assertions và HTTP36checks PASS; xem [evidence](evidence/learning-validation.md). Quiz theo [QUIZZES](QUIZZES.md) chưa có runtime.

## Dữ liệu và luồng

Các bảng: assignments, stored_files, assignment_files, submissions, submission_revisions, submission_files, grades, grade_publications, grade_events. Tất cả thuộc lớp owner; student chỉ đọc lớp đã duyệt và bài nộp/điểm của mình. Lịch sử vẫn giữ cho giáo viên khi học sinh rời/bị loại.

1. Teacher POST /classes/{classId}/assignments với title, instructions?, dueAt? (ISO có offset), allowLate và allowResubmit. Bài ở draft, maxScore luôn10.
2. Upload từng file multipart POST /classes/{classId}/files: assignmentId, purpose=assignment_material, file. File ở staged và private. Sau đó PATCH assignment với fileIds và expectedVersion; title/allowLate/allowResubmit vẫn bắt buộc. Tối đa5file tài liệu. Tạo bài trước khi upload vì file phải gắn assignmentId.
3. POST assignment/{id}/publish với expectedVersion. Student mới đọc được bài và các tài liệu đang được gắn. Bài có trạng thái draft → published → closed; không mở lại trong phiên bản này.
4. Student upload file với purpose=submission rồi POST assignment/{id}/submissions với fileIds (1–5), note? và header Idempotency-Key UUID. Nộp lại tạo revision bất biến. Cùng key/cùng payload trả receipt cũ, khác payload409; không tạo thông báo trùng. Replay vẫn kiểm quyền lớp hiện tại.
5. Teacher PUT /submission-revisions/{revisionId}/grade với score0–10, tối đa2chữ số thập phân, feedback? và expectedVersion (0 khi chưa có grade). Chỉ chấm revision mới nhất. Điểm nháp chỉ teacher owner xem được.
6. POST /grades/{gradeId}/publish với expectedVersion. Tạo publication bất biến, cập nhật con trỏ điểm hiện hành của submission và thông báo student. Đổi điểm đã công bố cần reason ít nhất3ký tự và ghi audit trước/sau. Request lặp không tạo publication thứ hai.
7. Sửa nháp sau công bố vẫn giữ điểm cũ cho học sinh và average. Nộp revision mới cũng giữ điểm đã công bố trước cho đến khi công bố điểm thay thế. Mỗi assignment/student chỉ có một publication hiện hành được tính.

Prefix mọi route: /api/v1. Sanctum cookie + CSRF, email verified và account active theo contract hiện tại.

## File

Giới hạn mỗi file20MiB, tối đa5file/revision. Cho PDF, DOCX, XLSX, PPTX, JPG/JPEG, PNG, UTF-8 TXT; chưa hỗ trợ DOC/XLS/PPT kiểu cũ, GIF, ZIP hoặc executable. Backend kiểm nội dung/MIME, chữ ký PDF, ảnh và cấu trúc Office; chặn đường dẫn traversal, macro VBA và ZIP expansion quá lớn. Đây không thay thế antivirus.

FILE_SCANNER=local_validation chỉ chạy local/testing/http-smoke, ghi validated_local. ClamAV adapter dùng INSTREAM và chỉ ghi clean khi scanner xác nhận. Production không được dùng local_validation, scanner không sẵn sàng trả503. Chưa cài hoặc nghiệm thu ClamAV daemon. Upload local không được mô tả là đã quét virus.

Download /files/{id}/download kiểm quyền mỗi request, trả attachment + nosniff + private/no-store; không public storage URL. Teacher owner đọc bài nộp trong lớp; student không đọc file của người khác. DELETE /files/{id} chỉ xóa staged do chính mình upload; attached409 để giữ lịch sử. Xóa tombstone ngừng truy cập ngay; xử lý retry khi xóa vật lý lỗi và purge/quota chưa triển khai.

PHP local upload_max_filesize20M/post_max_size24M cho từng request một file. Server đang chạy cần restart để lấy ini mới. Giới hạn reverse proxy production chưa cấu hình.

## Pagination và điểm trung bình

GET assignment/{id}/submissions cho teacher; GET /me/submissions cho student; GET /submissions/{id} trả revision history có phân trang, kèm metadata file. GET /classes/{id}/grades trả các publication hiện hành theo quyền. GET /classes/{id}/gradebook phân trang học sinh cho owner; GET /classes/{id}/grade-average trả riêng student đang đăng nhập.

Average = tổng điểm publication hiện hành / số publication hiện hành, làm tròn2chữ số. Chưa nộp/chưa chấm/chỉ nháp không tự thành0. Không có điểm trả average=null, gradedCount=0. Điểm0 đã công bố vẫn được tính. Quiz chưa tích hợp average vì chưa có engine.

## Giới hạn nghiệm thu hiện tại

Chưa có UI; atd_react vẫn rỗng. Chưa có quiz, chat, export Excel, antivirus daemon, quota/purge, worker notification dispatcher hoặc kiểm thử tải/concurrency production. Evidence phase2 chỉ chứng minh core/lịch/QR. Learning có [bằng chứng riêng](evidence/learning-validation.md), full suite51tests/574assertions và HTTP36checksPASS.
