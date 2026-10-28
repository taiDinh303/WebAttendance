import { useSession } from "../../../application/sessionContext";
import { usePage, useResource } from "../../../application/queries";
import { attendanceApi } from "../../../infrastructure/attendanceApi";
import { QueryState, Badge, Empty, Pagination } from "../../../shared/ui";
import { dateTime } from "../../../domain/format";
import { AttendanceAuditTimeline } from "./AttendanceAuditTimeline";
export function AttendanceHistory({ classId }: { classId: string }) {
  const { user } = useSession();
  const { page, setPage } = usePage();
  const query = useResource(["attendance", classId, page], (s) =>
    user?.role === "teacher"
      ? attendanceApi.records(classId, page, s)
      : attendanceApi.mine(classId, page, s),
  );
  return (
    <>
      <h2>Lịch sử điểm danh</h2>
      <p className="muted">
        {user?.role === "teacher"
          ? "Mở buổi học trong tab Lịch học để xem roster và điều chỉnh có lý do."
          : "Kết quả điểm danh của riêng bạn trong lớp."}
      </p>
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      <div className="panel">
        {query.data?.items.map((r) => (
          <article key={r.id} className="list-row">
            <div>
              <h3>
                {user?.role === "teacher"
                  ? (r.studentName ?? "Học sinh · " + r.studentId.slice(-8))
                  : "Kết quả buổi học"}
              </h3>
              <small>
                {r.sessionTitle || "Buổi học"}
                {r.sessionStartsAt ? " · " + dateTime(r.sessionStartsAt) : ""}
              </small>
              <p>
                {r.checkedInAt
                  ? "Quét lúc " + dateTime(r.checkedInAt)
                  : "Ghi nhận bởi giáo viên / hệ thống"}
              </p>
              {user?.role === "teacher" ? (
                <AttendanceAuditTimeline classId={classId} recordId={r.id} />
              ) : null}
            </div>
            <Badge value={r.status} />
          </article>
        ))}
        {query.isSuccess && !query.data.items.length ? (
          <Empty
            title="Chưa có điểm danh"
            description="Kết quả sẽ xuất hiện sau khi học sinh check-in hoặc giáo viên chốt điểm danh."
          />
        ) : null}
      </div>
      <Pagination data={query.data} onPage={setPage} />
    </>
  );
}
