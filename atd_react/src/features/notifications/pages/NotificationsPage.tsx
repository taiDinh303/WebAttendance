import { Link } from "react-router-dom";
import { Bell } from "lucide-react";
import { usePage, useResource, useRefresh } from "../../../application/queries";
import { classApi } from "../../../infrastructure/classApi";
import {
  PageHeader,
  QueryState,
  Empty,
  ActionButton,
  Pagination,
} from "../../../shared/ui";
import { dateTime } from "../../../domain/format";
const labels: Record<string, string> = {
  "assignment.published": "Lớp có bài tập mới",
  "submission.created": "Có bài nộp mới",
  "grade.published": "Điểm đã được công bố",
  "enrollment.requested": "Có yêu cầu tham gia lớp",
  "enrollment.approved": "Yêu cầu vào lớp đã được duyệt",
  "enrollment.rejected": "Yêu cầu vào lớp đã bị từ chối",
  "enrollment.removed": "Thành viên đã bị loại",
  "enrollment.left": "Có học sinh rời lớp",
};
export default function NotificationsPage() {
  const { page, setPage } = usePage();
  const refresh = useRefresh();
  const query = useResource(
    ["notifications", page],
    (s) => classApi.notices(page, s),
    { refetchInterval: 60000 },
  );
  return (
    <>
      <PageHeader
        eyebrow="LUÔN GIỮ KẾT NỐI"
        title="Thông báo"
        description="Các cập nhật mới từ lớp học của bạn."
      />
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      <section className="panel">
        {query.data?.items.map((n) => (
          <article
            className={"list-row notice " + (!n.readAt ? "unread" : "")}
            key={n.id}
          >
            <span className="stat-icon blue">
              <Bell size={19} />
            </span>
            <div className="grow">
              <h3>{labels[n.type] ?? "Cập nhật từ lớp học"}</h3>
              <p>{dateTime(n.createdAt)}</p>
              {n.data.classId ? (
                <Link
                  to={
                    "/app/classes/" +
                    n.data.classId +
                    (n.data.assignmentId
                      ? "/assignments/" + n.data.assignmentId
                      : "")
                  }
                >
                  Mở lớp học →
                </Link>
              ) : null}
            </div>
            {!n.readAt ? (
              <ActionButton
                className="button secondary small"
                action={async () => {
                  await classApi.readNotice(n.id);
                  await refresh();
                }}
              >
                Đánh dấu đã đọc
              </ActionButton>
            ) : (
              <small className="muted">Đã đọc</small>
            )}
          </article>
        ))}
        {query.isSuccess && !query.data.items.length ? (
          <Empty
            title="Bạn đã xem hết rồi"
            description="Thông báo mới sẽ được hiển thị ở đây."
          />
        ) : null}
      </section>
      <Pagination data={query.data} onPage={setPage} />
    </>
  );
}
