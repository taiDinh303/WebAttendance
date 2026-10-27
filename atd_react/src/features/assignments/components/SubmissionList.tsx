import { Link } from "react-router-dom";
import { usePage, useResource } from "../../../application/queries";
import { useSession } from "../../../application/sessionContext";
import { learningApi } from "../../../infrastructure/learningApi";
import { QueryState, Empty, Pagination } from "../../../shared/ui";
export function SubmissionList({
  classId,
  assignmentId,
}: {
  classId: string;
  assignmentId: string;
}) {
  const { user } = useSession();
  const { page, setPage } = usePage();
  const query = useResource(["submissions", assignmentId, page], (s) =>
    user?.role === "teacher"
      ? learningApi.submissions(classId, assignmentId, page, s)
      : learningApi.mine(page, s, assignmentId),
  );
  return (
    <section>
      <div className="section-heading">
        <h2>
          {user?.role === "teacher"
            ? "Bài nộp của học sinh"
            : "Bài nộp của bạn"}
        </h2>
      </div>
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      <div className="panel">
        {query.data?.items.map((sub) => (
          <Link
            className="list-row"
            key={sub.id}
            to={"/app/submissions/" + sub.id}
          >
            <div>
              <h3>
                {user?.role === "teacher"
                  ? (sub.studentName ?? "Học sinh · " + sub.studentId.slice(-8))
                  : "Xem lịch sử bài nộp"}
              </h3>
              <small>
                {sub.publishedGrade
                  ? "Điểm đã công bố: " + sub.publishedGrade.score + "/10"
                  : "Chưa có điểm công bố"}
              </small>
            </div>
            <span className="text-link">
              {user?.role === "teacher" ? "Mở để chấm" : "Xem chi tiết"} →
            </span>
          </Link>
        ))}
        {query.isSuccess && !query.data.items.length ? (
          <Empty title="Chưa có bài nộp" />
        ) : null}
      </div>
      <Pagination data={query.data} onPage={setPage} />
    </section>
  );
}
