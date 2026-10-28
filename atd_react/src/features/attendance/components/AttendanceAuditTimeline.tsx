import { useState } from "react";
import { useResource } from "../../../application/queries";
import { attendanceApi } from "../../../infrastructure/attendanceApi";
import { dateTime } from "../../../domain/format";
import { Badge, Empty, Pagination, QueryState } from "../../../shared/ui";

const actions: Record<string, string> = {
  qr_check_in: "Học sinh quét mã QR",
  manual_correction: "Giáo viên điều chỉnh",
  auto_absent: "Hệ thống chốt vắng",
};
export function AttendanceAuditTimeline({
  classId,
  recordId,
}: {
  classId: string;
  recordId: string;
}) {
  const [open, setOpen] = useState(false);
  const [page, setPage] = useState(1);
  const query = useResource(
    ["attendance-audit", classId, recordId, page],
    (signal) => attendanceApi.audit(classId, recordId, page, signal),
    { enabled: open },
  );
  const contentId = "audit-" + recordId;
  return (
    <div>
      <button
        type="button"
        className="button secondary small"
        aria-expanded={open}
        aria-controls={contentId}
        onClick={() => setOpen(!open)}
      >
        {open ? "Ẩn lịch sử" : "Lịch sử thay đổi"}
      </button>
      {open ? (
        <section id={contentId} aria-label="Lịch sử thay đổi điểm danh">
          <QueryState
            pending={query.isPending}
            error={query.error}
            retry={query.refetch}
          />
          <ol className="audit-timeline">
            {query.data?.items.map((audit) => (
              <li key={audit.id}>
                <strong>{actions[audit.action] ?? "Cập nhật điểm danh"}</strong>
                <p>
                  {audit.before ? (
                    <>
                      <Badge value={audit.before.status} /> →{" "}
                    </>
                  ) : (
                    "Ghi nhận mới → "
                  )}
                  <Badge value={audit.after.status} />
                </p>
                {audit.reason ? <p>Lý do: {audit.reason}</p> : null}
                <small className="muted">
                  {dateTime(audit.createdAt)} ·{" "}
                  {audit.actorId
                    ? "Tài khoản " + audit.actorId.slice(-8)
                    : "Hệ thống"}{" "}
                  · Phiên bản {audit.after.version}
                </small>
              </li>
            ))}
          </ol>
          {query.isSuccess && !query.data.items.length ? (
            <Empty title="Chưa có lịch sử thay đổi" />
          ) : null}
          <Pagination data={query.data} onPage={setPage} />
        </section>
      ) : null}
    </div>
  );
}
