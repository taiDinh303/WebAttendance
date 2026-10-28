# 14 — Quyết định sản phẩm và kiến trúc

> 2026-10-26 | Nguồn CONFIRMED: lời chủ dự án trong chat. Không có quyết định APPROVED khác từ hai repo tham khảo.

## Quyết định đã xác nhận

| DEC | Quyết định | Nguồn / hệ quả |
| --- | --- | --- |
| DEC-001 | Nhiều giáo viên tạo lớp độc lập | Q-001; teacher-owned isolation, chưa school tenant |
| DEC-002 | PHP Laravel REST API + React TypeScript/Vite riêng | Q-002 và lời đồng ý sau tư vấn frontend; không .NET/Blade/Inertia làm kiến trúc chính |
| DEC-003 | Tạm thời chỉ QR attendance | Q-003; không GPS/IP/manual attendance V1; chức năng thủ công cũ được đánh deferred |
| DEC-004 | Vào lớp bằng mã, giáo viên xem và duyệt | Q-004; pending không có quyền lớp |
| DEC-005 | Có bài tập nộp file, chấm điểm, trắc nghiệm | Q-005; chi tiết score/file/quiz chưa chốt |
| DEC-006 | Chat GV–HS và notification hoạt động lớp | Q-006; hình thức chat/kênh notification cần phỏng vấn tiếp |
| DEC-007 | Phân trang đầy đủ; dữ liệu lớp/điểm riêng theo quyền | Q-007; scope server cả list/count/export/file/channel |
| DEC-008 | Soạn docs trước, biết scope/data/progress, tham khảo hai repo | Q-008 và yêu cầu tiếp tục; chưa được giao app code/deploy |
| DEC-009 | Một role/tài khoản teacher hoặc student, teacher không cần admin duyệt sau xác minh email | Q-009 trả lời 2026-10-26; role column trên users, không user_roles nhiều role trong đợt đầu |
| DEC-010 | Teacher tạo nhiều lớp riêng, tự quản môn/học kỳ; hai FK tùy chọn | Hai trả lời Q-010 2026-10-26; không catalog global/co-teacher |
| DEC-011 | MySQL | Q-018 2026-10-26; dev/test engine giống nhau, cloud Q-026 mở |
| DEC-012 | Left/rejected được request lại; removed cần teacher allow-rejoin, sau đó vẫn phải request/duyệt | Q-022 2026-10-26; không tự active khi allow |
| DEC-013 | Rời/bị loại mất quyền lớp ngay, giữ lịch sử server cho teacher | Q-020 2026-10-26; retention/xóa tài khoản Q-025 mở |
| DEC-014 | Bắt đầu code API; frontend tạo riêng atd_react và để rỗng | Yêu cầu trực tiếp 2026-10-26; tiếp nối phase docs DEC-008, chưa deploy/push |
| DEC-015 | Lịch dùng giờ thực, lặp tuần và sửa/hủy từng buổi | Q-023 trả lời 2026-10-27; recurrence dùng timezone lớp, UTC storage |
| DEC-016 | QR đổi30s/TTL60s, teacher chọn mở/đóng/mốc muộn; roster chốt lúc mở, vắng sau đóng | Q-011 trả lời 2026-10-27; newly approved sau snapshot không tự thêm roster, active membership vẫn kiểm mỗi check-in |
| DEC-017 | Teacher owner được sửa điểm danh thủ công | Q-024 trả lời 2026-10-27; supersedes phần cấm sửa tay của DEC-003, không thêm GPS/IP/face recognition. Implementation: reason bắt buộc, optimistic version và immutable before/after audit |
| DEC-018 | Tất cả bài thang10, có điểm trung bình | Q-012 trả lời 2026-10-27; thay đề xuất maxScore tùy bài/chưa tính trung bình. Đây là TARGET cho module learning chưa code |
| DEC-019 | Trung bình số học các điểm đã công bố, trọng số bằng nhau; chưa chấm/chưa nộp không tự tính0 | Q-027 trả lời 2026-10-27; không lộ draft grade qua average; không còn published grades thì average=null |
| DEC-020 | Tối đa5file×20MB mỗi revision, định dạng theo đề xuất, teacher chọn late/resubmit, revision history | Q-013 trả lời 2026-10-27; implementation extensions PDF/DOCX/XLSX/PPTX/JPG/JPEG/PNG/TXT, cap20MiB, private storage/authorization |

### ADR-001 — Nền tảng đợt API đầu (IMPLEMENTED, evidence ở worklog)

Áp dụng ADR-P01 và phần local/auth của ADR-P02 trong phạm vi được giao triển khai: Laravel13 + Sanctum4; route JSON first-party chạy web session/CSRF middleware và auth:sanctum, không cấp personal access tokens; MySQL8.4 portable local, separate dev/test users/schema. Role lưu một cột users theo DEC-009; profile cơ bản cùng row users để cập nhật version atomically. Học kỳ/môn có Policy cùng owner; lớp optional FK. Tách `.tools`/`work` khỏi Git; không thay PATH toàn máy.

ADR-P03–P06 vẫn thiết kế cho module tương lai; không ghi Reverb/outbox dispatcher/scanner đã chạy. Inbox membership lưu cùng transaction với audit/outbox, chưa realtime dispatch. MySQL local không giải quyết hosting production. Các quyết định đã chốt ở trên thay thế phần dự đoán tương ứng trong ADR-P.

## ADR đề xuất — chưa biến thành câu trả lời chủ dự án

### ADR-P01 — Một modular monolith Laravel

Controller/FormRequest/Policy/Action/Resource theo nghiệp vụ; Eloquent + transaction, không thêm microservice hoặc generic repository/UoW chỉ để giống .NET. Dễ trace flow và test. Xem lại nếu có module độc lập cần scaling/team riêng.

### ADR-P02 — MySQL và phiên SPA Sanctum cookie

MySQL tận dụng nền cũ, same engine dev/test/prod. Cookie HttpOnly với same-site app/API, CSRF/stateful CORS; tránh tự xây JWT refresh. Cần Q-018 và test browser hosting trước chốt. PostgreSQL vẫn là lựa chọn hợp lệ nếu hosting phù hợp; chưa tự chọn Supabase từ repo tham khảo.

### ADR-P03 — QR challenge có TTL, state trên server

Token opaque luân phiên, hash DB, server kiểm window/membership; unique buổi–HS. Đóng tab không đóng phiên. Giới hạn chống chia sẻ QR phải nói thật. TTL/mốc muộn/roster snapshot Q-011; thay đổi không có hiệu lực hồi tố cho records đã chốt.

### ADR-P04 — File private và revision nộp bài

File scan/staged/attached, revision bất biến, grade gắn revision và publish riêng. Đề xuất proxy download kiểm quyền mỗi lần cho nội dung nhạy cảm; signed URL nếu cần tải lớn phải ngắn hạn và chấp nhận không thu hồi tức thời link đã cấp. Limits/storage/retention chưa chốt Q-013/018/020.

### ADR-P05 — Quiz snapshot và server clock

Không sửa đề/answer key của attempt đang làm. Deadline server, transaction submit/scoring idempotent. Single/multi choice tất cả-đúng chỉ là đề xuất Q-014. Không đánh giá chống gian lận như thi giám sát.

### ADR-P06 — Durable events, private realtime

Outbox cùng transaction, worker at-least-once + dedup; DB nguồn chuẩn, Reverb/Echo kênh cập nhật; reconnect REST theo cursor. Thread 1–1 theo class là giả định Q-015, chưa group chat. Server thu hồi quyền channel khi membership/account đổi; không chỉ kiểm lúc subscribe.

### ADR-P07 — UI/query và version

React TS/Vite đã chốt; Tailwind/shadcn, Router và TanStack Query là đề xuất. Query key actor scoped, pagination server; optimistic UI chỉ cho thao tác có rollback, điểm/approval/quiz submission đợi ACK. Mutable resource dùng version để không ghi đè. Chốt thư viện/version khi scaffold và ghi evidence.

## Quy trình thay đổi

Decision mới có ID/ngày/nguồn/lý do/hệ quả/file bị ảnh hưởng, liên kết Q và TC; giữ lịch sử quyết định superseded. Không ghi ACCEPTED cho ADR-P khi chưa có câu trả lời hay quyền chọn implementation rõ ràng ở nhiệm vụ code tiếp theo.

### DEC-021 — Quy tắc trắc nghiệm (CONFIRMED 2026-10-27)

Nguồn: chủ dự án trả lời Q-014 “Theo đề xuất”. Hỗ trợ câu một đáp án và nhiều đáp án; giáo viên chọn thời gian và số lượt làm. Câu nhiều đáp án chỉ có điểm khi tập đáp án chọn đúng đủ, không cộng điểm một phần. Đáp án chỉ được công bố sau khi đóng bài. Áp dụng thang10 và trung bình điểm đã công bố theo DEC-018/019.

Trạng thái: CONFIRMED nghiệp vụ, quiz engine chưa IMPLEMENTED/VERIFIED. Quy tắc chọn điểm giữa nhiều lượt, trọng số câu và khôi phục attempt khi mất mạng cần đặc tả trước khi thực hiện; không mặc định một lượt cố định thay cho quyền chọn của giáo viên.

### DEC-022 — Bắt đầu triển khai React (CONFIRMED 2026-10-27)

Nguồn: chủ dự án yêu cầu “D:\My Projects\AttendanceWeb tiếp tục làm react đi”. Thay giới hạn folder rỗng của DEC-014 từ thời điểm này; giữ React TypeScript/Vite + Laravel API và không deploy. Kết nối các module backend đã có, không tạo UI giả cho quiz/chat/backend chưa triển khai.

### DEC-023 — Phân tầng tham khảo NTC, Marixa, Badminton (CONFIRMED 2026-10-27)

Nguồn: “làm và phân tầng như các dự NTC, marixa, badminton ... D:\My Projects”. Đã khảo sát package/source tree và ví dụ infrastructure/application của ba frontend. Cấu trúc áp dụng app/domain/infrastructure/application/features/shared; module feature có pages/components. Không đổi PHP Laravel sang .NET, không copy auth/deploy/secrets. Chi tiết FRONTEND-IMPLEMENTATION.

### DEC-024 — Publish bản nâng cấp, giữ lịch sử Git (CONFIRMED 2026-10-27)

Chủ dự án giao push lên taiDinh303/WebAttendance và giữ commit cũ; chấp nhận nhánh nâng cấp rồi merge main. Nhánh feat/attendanceweb-laravel-react có baseline02917e3 (181commit gốc), không force-push/rewrite history. Publish nhánh và PR để review trước khi merge main. Credentials/runtime/DB thật không đưa vào Git; không suy quyền deploy từ quyền push.

### DEC-025 — AttendanceWeb 2.0 (CONFIRMED 2026-10-28)

Nguồn: sau đề xuất phỏng vấn4câu, chủ dự án giao “Làm cái phần bạn đề xuất đi” và nêu quy mô. Chấp nhận hướng dùng thử thực tế, ưu tiên2.0: trải nghiệm lớp học mới, dashboard riêng từng vai trò, giao diện tiếng Việt/nền sáng/xanh chủ đạo và hoàn thiện thao tác UI hiện có. Giữ Laravel/React/MySQL, teacher ownership và các quy tắc đã chốt. Tên thương hiệu chính thức/dark mode chưa được chốt riêng; không suy thêm quyền deploy/gửi mail/import dữ liệu cũ. Lộ trình2.1/2.2 giữ các Q chặn quiz/chat/notification trước khi thực hiện module.

### DEC-026 — Quy mô mục tiêu sử dụng thử (CONFIRMED 2026-10-28)

Nguồn trực tiếp: khoảng3giáo viên,90học sinh,30người điểm danh đồng thời. Đây là workload mục tiêu để tạo fixture và kiểm thử, không phải kết quả capacity đã VERIFIED hoặc trần giới hạn tài khoản. Hosting/domain/ngân sách/thời hạn trong Q-026 vẫn OPEN; không tự chọn SLA hoặc phát hành production.

### DEC-027 — Admin quản trị tài khoản và thống kê (CONFIRMED 2026-10-28)

Chủ dự án giao làm admin/trang admin và trả lời Q-021: “Theo đề xuất: quản trị tài khoản và thống kê, giữ riêng nội dung lớp”. Admin có thống kê tổng hợp, tìm/lọc tài khoản, khóa/mở khóa có lý do/version/audit và xem audit quản trị. Không có Gate::before bypass hoặc quyền mặc định đọc lớp/điểm/file/chat; không tự đăng ký admin, không role editor/xóa tài khoản/impersonation. Không khóa chính mình hoặc làm mất admin active cuối; khóa/mở thu hồi session. Q-021 đã chốt scope hiện tại.

### DEC-028 — Bộ dữ liệu demo bền vững (CONFIRMED 2026-10-28)

Chủ dự án giao dữ liệu giả1admin/3teacher/90student và30check-in đồng thời. Dùng schema localdemo riêng, namespace email .test,3lớp×30student, dữ liệu minh họa lịch/điểm danh/bài tập/điểm. Seed có guard environment/database/mail/opt-in, idempotent và giữ thay đổi tài khoản; không xóa dữ liệu cũ hoặc đổi mật khẩu khi seed lại. Secret chỉ từ env/ignoredwork, không literalpassword trong docs/seed/log. Workload30đồng thời kiểm bằng fixture riêng; quy mô không phải trần user hệ thống.

DEC-027/028 IMPLEMENTED và VERIFIED LOCAL tại TASK-024 (2026-10-28): admin5API/3trang, seedattendance_demo1/3/90,101PHP/29frontendtests, browseradmin và HTTP30/replay/restore; chi tiết06-testing. Scope CONFIRMED không thay đổi, production chưa VERIFIED.
