import { useSession } from "../../../application/sessionContext";
import { usePage, useResource } from "../../../application/queries";
import { learningApi } from "../../../infrastructure/learningApi";
import { QueryState, Pagination, Empty } from "../../../shared/ui";
import { dateTime } from "../../../domain/format";
export function GradesPanel({ classId }: { classId: string }) {
  const { user } = useSession();
  return user?.role === "teacher" ? (
    <TeacherGrades classId={classId} />
  ) : (
    <StudentGrades classId={classId} />
  );
}
function TeacherGrades({ classId }: { classId: string }) {
  const { page, setPage } = usePage();
  const query = useResource(["gradebook", classId, page], (s) =>
    learningApi.gradebook(classId, page, s),
  );
  return (
    <>
      <h2>Bảng điểm lớp</h2>
      <p className="muted">
        Trung bình các điểm đã công bố, trọng số bằng nhau. Chưa có điểm hiển
        thị “—”.
      </p>
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      <div className="panel table-scroll">
        <table>
          <thead>
            <tr>
              <th>Học sinh</th>
              <th>Số bài đã có điểm</th>
              <th>Trung bình / 10</th>
            </tr>
          </thead>
          <tbody>
            {query.data?.items.map((r) => (
              <tr key={r.studentId}>
                <td>
                  <strong>{r.studentName}</strong>
                </td>
                <td>{r.gradedCount}</td>
                <td>
                  <span className="score">{r.average ?? "—"}</span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {query.isSuccess && !query.data.items.length ? (
          <Empty title="Chưa có dữ liệu điểm" />
        ) : null}
      </div>
      <Pagination data={query.data} onPage={setPage} />
    </>
  );
}
function StudentGrades({ classId }: { classId: string }) {
  const { page, setPage } = usePage();
  const average = useResource(["average", classId], (s) =>
    learningApi.average(classId, s),
  );
  const grades = useResource(["grades", classId, page], (s) =>
    learningApi.grades(classId, page, s),
  );
  return (
    <>
      <div className="section-heading">
        <div>
          <h2>Điểm của bạn</h2>
          <p className="muted">Chỉ tính các điểm giáo viên đã công bố.</p>
        </div>
        <div className="average-box">
          <small>TRUNG BÌNH</small>
          <strong>
            {average.data?.average ?? "—"}
            <small> / 10</small>
          </strong>
        </div>
      </div>
      <QueryState
        pending={average.isPending || grades.isPending}
        error={average.error || grades.error}
        retry={() => {
          void average.refetch();
          void grades.refetch();
        }}
      />
      <div className="panel">
        {grades.data?.items.map((g) => (
          <article key={g.id} className="list-row">
            <div>
              <h3>Điểm đã công bố</h3>
              <p>{g.feedback || "Chưa có nhận xét"}</p>
              <small>{dateTime(g.publishedAt)}</small>
            </div>
            <strong className="score">{g.score} / 10</strong>
          </article>
        ))}
        {grades.isSuccess && !grades.data.items.length ? (
          <Empty
            title="Chưa có điểm công bố"
            description="Điểm nháp hoặc bài chưa chấm không được tính thành 0."
          />
        ) : null}
      </div>
      <Pagination data={grades.data} onPage={setPage} />
    </>
  );
}
