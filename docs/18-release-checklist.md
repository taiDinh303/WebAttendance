# 18 — Điều kiện phát hành

> 2026-10-26 | NOT RUN | Không mục nào của ứng dụng đã hoàn thành chỉ nhờ docs. Người duyệt release: chủ dự án; người vận hành chưa phân công.

## Scope và đặc tả

- [ ] Q chặn release đã ANSWERED, DEC cập nhật, REQ/API/TC cùng version.
- [ ] Nêu rõ module được phát hành; không ghi đủ tính năng nếu chưa có chat/quiz/export.
- [ ] Data retention/admin access/role model/limits và timezone đã chốt.
- [ ] Student check-in QR và giới hạn chống chia sẻ được thể hiện trung thực; teacher manual correction theo DEC-017 có reason/version/audit, không cho student bypass.

## Code và kiểm chứng

- [ ] Clean checkout install từ lock, formatter/lint/static typing/build/test pass có evidence.
- [ ] TC-001–022 áp dụng release đã chạy; failures/limits ghi đầy đủ, không dùng mock thay API thật.
- [ ] Cross-teacher/student isolation qua REST, counts, export, files, jobs và channel.
- [ ] Pending/removed/locked thực sự mất quyền; draft grade/quiz key không có trong JSON HS.
- [ ] Race QR/approval/submit/timeout/grade, idempotency và optimistic version đã thử.
- [ ] Pagination mọi collection, cursor chat, loading/error/conflict/empty, mobile/keyboard đã kiểm.
- [ ] Upload MIME/scan/private download/quota; export formula injection; dependencies/secrets audit.

## Vận hành

- [ ] TLS/domain/CSRF/CORS/cookie browser staging thật, no debug/public env/private storage.
- [ ] Worker/scheduler/Reverb retry/restart/lag alert, outbox recovery và reconnect đã kiểm.
- [ ] Migration plan/backup/restore rehearsal, app rollback compatibility và owner incident.
- [ ] RPO/RTO/budget/tải/retention được chốt, không tự cam kết free host đáp ứng mọi nhu cầu.
- [ ] Smoke vertical flow và monitor sau release, release commit/tag và URL ở11.
- [ ] worklog ghi actual commands/results/evidence/limitations, user guide phản ánh UI thật nếu đã có.

User guide có ảnh thao tác chỉ soạn sau UI thực; hiện05/09 là đặc tả hành vi, không giả làm hướng dẫn ứng dụng đang chạy.

## File và điểm giai đoạn3

Trước release cần kiểm ClamAV daemon thật, storage cleanup retry/quota/retention, proxy upload limits và capacity; các phần này chưa VERIFIED. Không dùng kết quả local_validation để đánh dấu malware scan PASS. Learning Feature/HTTP evidence riêng với phase1/phase2; quiz/chat/frontend chưa được triển khai, không đánh hoàn tất sản phẩm.
