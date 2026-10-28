import { Link } from "react-router-dom";
import { usePage, useResource } from "../../../application/queries";
import { adminApi } from "../../../infrastructure/adminApi";
import { dateTime } from "../../../domain/format";
import {
  Badge,
  Empty,
  PageHeader,
  Pagination,
  QueryState,
} from "../../../shared/ui";

export default function AdminAuditPage() {
  const { page, search, setSearch, setPage } = usePage();
  const subjectId = search.get("subjectId") ?? "",
    operation = search.get("operation") ?? "";
  const query = useResource(
    ["admin-audit", page, subjectId, operation],
    (signal) =>
      adminApi.events({ pageNumber: page, subjectId, operation }, signal),
  );
  return (
    <>
      <PageHeader
        eyebrow="QUẢN TRỊ / LỊCH SỬ"
        title="Nhật ký quản trị"
        description="Đối chiếu người thực hiện, lý do và trạng thái trước/sau. Nhật ký chỉ được đọc tại đây."
      />
      <div className="filter-bar">
        <select
          aria-label="Thao tác quản trị"
          value={operation}
          onChange={(event) =>
            setSearch({ subjectId, operation: event.target.value, page: "1" })
          }
        >
          <option value="">Tất cả thao tác</option>
          <option value="lock">Khóa tài khoản</option>
          <option value="unlock">Mở khóa tài khoản</option>
        </select>
        {subjectId ? (
          <Link className="button secondary small" to="/admin/audit">
            Bỏ lọc tài khoản
          </Link>
        ) : null}
        <span className="muted">{query.data?.totalItems ?? "—"} sự kiện</span>
      </div>
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      <section className="panel editor-panel">
        <ol className="audit-timeline admin-audit">
          {query.data?.items.map((event) => (
            <li key={event.id}>
              <strong>
                {event.operation === "lock"
                  ? "Khóa tài khoản"
                  : "Mở khóa tài khoản"}{" "}
                · {event.subjectName}
              </strong>
              <p>Người thực hiện: {event.actorName}</p>
              <p>
                <Badge value={event.before.accountStatus} /> →{" "}
                <Badge value={event.after.accountStatus} />
              </p>
              <p>Lý do: {event.reason}</p>
              <small className="muted">
                {dateTime(event.createdAt)} · Phiên bản {event.before.version} →{" "}
                {event.after.version}
              </small>
            </li>
          ))}
        </ol>
        {query.isSuccess && !query.data.items.length ? (
          <Empty
            title="Chưa có thao tác quản trị"
            description="Các thao tác khóa/mở khóa sẽ được ghi lại tại đây."
          />
        ) : null}
      </section>
      <Pagination data={query.data} onPage={setPage} />
    </>
  );
}
