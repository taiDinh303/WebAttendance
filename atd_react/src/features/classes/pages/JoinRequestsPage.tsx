import { usePage, useResource, useRefresh } from "../../../application/queries";
import { classApi } from "../../../infrastructure/classApi";
import {
  PageHeader,
  QueryState,
  Empty,
  Badge,
  Pagination,
  ActionButton,
} from "../../../shared/ui";
export default function JoinRequestsPage() {
  const { page, setPage } = usePage();
  const refresh = useRefresh();
  const query = useResource(["join-requests", page], (s) =>
    classApi.myRequests(page, s),
  );
  return (
    <>
      <PageHeader
        title="Yêu cầu vào lớp"
        description="Theo dõi trạng thái phê duyệt từ giáo viên."
      />
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      <section className="panel">
        {query.data?.items.map((m) => (
          <article className="list-row" key={m.id}>
            <div>
              <h3>{m.className || "Yêu cầu tham gia lớp"}</h3>
              {m.reviewNote ? <p>{m.reviewNote}</p> : null}
              <Badge value={m.status} />
            </div>
            {m.status === "pending" ? (
              <ActionButton
                className="button secondary small"
                action={async () => {
                  await classApi.cancel(m);
                  await refresh();
                }}
              >
                Hủy yêu cầu
              </ActionButton>
            ) : null}
          </article>
        ))}
        {query.isSuccess && !query.data.items.length ? (
          <Empty
            title="Chưa có yêu cầu"
            description="Nhập mã lớp tại trang Lớp học để bắt đầu."
          />
        ) : null}
      </section>
      <Pagination data={query.data} onPage={setPage} />
    </>
  );
}
