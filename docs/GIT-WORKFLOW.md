# Git và bằng chứng nâng cấp AttendanceWeb

## Baseline và cách giữ lịch sử

Chủ dự án yêu cầu ngày2026-10-27 đẩy bản nâng cấp lên taiDinh303/WebAttendance và giữ commit trước đó. Repository gốc có181commit, main ở02917e3d65f8659bc09560f40591c1f75be21e57 (fix optimize).

Thư mục AttendanceWeb được khởi tạo Git và fetch đầy đủ lịch sử từ chính repository này; nhánh feat/attendanceweb-laravel-react bắt đầu từ main gốc. Không tạo một lịch sử root mới rồi đè remote. Mã PHP cũ được thay bằng cấu trúc attendance_api/ và atd_react/ trên nhánh nâng cấp; các file cũ và vendor cũ vẫn xem được ở baseline/các commit cha. Thư mục D:\My Projects\WebAttendance cũ không bị sửa.

Dùng author Đinh Văn Tài và GitHub noreply đã xác nhận theo tài khoản taiDinh303; không đưa email cá nhân hoặc credentials Git vào source. Không backdate commit: các commit mới thể hiện thời điểm đóng gói bản nâng cấp, không giả là được tạo ở những lần làm việc trước.

## Xem bằng chứng

```powershell
cd "D:\My Projects\AttendanceWeb"
git remote -v
git status
git log --graph --decorate --oneline --all -20
git merge-base --is-ancestor 02917e3d65f8659bc09560f40591c1f75be21e57 HEAD
# exit code0: baseline vẫn là tổ tiên của bản nâng cấp
git show 02917e3:Teacher/index.php
```

Nhánh được tách commit theo docs/baseline, Laravel API và React, rồi ghi kết quả publish nếu cần. Evidence tests và docs là các tài liệu hỗ trợ; Git history là bằng chứng diff/cha-con thực tế, không thay thế thời gian làm việc lịch sử.

## Push các thay đổi tiếp theo

```powershell
git switch feat/attendanceweb-laravel-react
git status
# Review diff trước khi stage; không git add các file .env/runtime đã ignore
git add <cac-file-source-da-review>
git diff --cached --stat
git commit -m "fix: describe the actual change"
git push origin feat/attendanceweb-laravel-react
```

Lệnh push lần đầu: git push -u origin feat/attendanceweb-laravel-react. Không force-push và không sửa/xóa commit cũ.

## Merge về main

Mở Pull Request từ feat/attendanceweb-laravel-react → main, xem Files changed và CI. Khi đã sẵn sàng, chuyển draft sang ready rồi chọn Create a merge commit / Merge pull request để có mốc merge rõ ràng. Main giữ phiên bản cũ cho đến khi PR được merge; không tự coi push nhánh là đã cập nhật main.

Sau khi merge, cập nhật main local khi working tree sạch:

```powershell
git fetch origin
git switch main
git pull --ff-only origin main
```

Nếu muốn tự merge bằng Git local thay GitHub: kiểm main/remote còn đúng và nhánh không conflict; git merge --no-ff feat/attendanceweb-laravel-react rồi git push origin main. Không thực hiện đồng thời cả hai cách.

## File không publish

.tools, work, .env/.env.testing/.env.http-smoke, database local, uploads, session/cache/logs, vendor/node_modules, dist, raw browser trace/credentials đều ignore. Chỉ .env.example không secret được đưa lên. Các giá trị CI trong workflow là giá trị dùng riêng cho service test, không phải credentials dev/production.

PHP vendor trước đây đã từng được track trong main được bỏ khỏi bản nâng cấp; composer.lock/package-lock vẫn được commit, dependencies cài bằng composer install/npm ci. Chạy app theo 10-setup-local; không upload MySQL portable hoặc data directory.

Xem [worklog](worklog.md) cho kết quả publish thực tế, và evidence trước khi gọi module đã VERIFIED.
