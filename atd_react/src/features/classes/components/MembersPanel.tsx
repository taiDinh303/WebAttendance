import { useState } from "react";
import { usePage, useResource, useRefresh } from "../../../application/queries";
import { classApi } from "../../../infrastructure/classApi";
import {
  QueryState,
  Empty,
  Badge,
  Pagination,
  ActionButton,
} from "../../../shared/ui";
import { initials } from "../../../domain/format";
export function MembersPanel({ classId }: { classId: string }) {
  const [kind, setKind] = useState<"members" | "join-requests">("members");
  const { page, setPage } = usePage();
  const refresh = useRefresh();
  const query = useResource(["members", classId, kind, page], (s) =>
    classApi.members(classId, kind, page, s),
  );
  return (
    <>
      <div className="section-heading">
        <h2>Thành viên lớp</h2>
        <div className="segmented">
          <button
            className={kind === "members" ? "selected" : ""}
            onClick={() => {
              setKind("members");
              setPage(1);
            }}
          >
            Danh sách
          </button>
          <button
            className={kind === "join-requests" ? "selected" : ""}
            onClick={() => {
              setKind("join-requests");
              setPage(1);
            }}
          >
            Yêu cầu vào lớp
          </button>
        </div>
      </div>
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      <div className="panel">
        {query.data?.items.map((m) => (
          <div className="list-row" key={m.id}>
            <div className="person">
              <span className="avatar">{initials(m.studentName ?? "HS")}</span>
              <div>
                <h3>{m.studentName ?? m.studentId}</h3>
                <Badge value={m.status} />
              </div>
            </div>
            <div className="inline-actions">
              {(m.status === "pending"
                ? ["approve", "reject"]
                : m.status === "active"
                  ? ["remove"]
                  : m.status === "removed"
                    ? ["allow-rejoin"]
                    : []
              ).map((action) => (
                <ActionButton
                  key={action}
                  className={
                    "button small " + (action === "approve" ? "" : "secondary")
                  }
                  action={async () => {
                    if (
                      action === "remove" &&
                      !window.confirm(
                        "Loại học sinh khỏi lớp? Quyền truy cập sẽ dừng ngay.",
                      )
                    )
                      return;
                    await classApi.membership(classId, m, action);
                    await refresh();
                  }}
                >
                  {
                    {
                      approve: "Duyệt",
                      reject: "Từ chối",
                      remove: "Loại khỏi lớp",
                      "allow-rejoin": "Cho xin vào lại",
                    }[action]
                  }
                </ActionButton>
              ))}
            </div>
          </div>
        ))}
        {query.isSuccess && !query.data.items.length ? (
          <Empty
            title={
              kind === "members" ? "Chưa có thành viên" : "Chưa có yêu cầu"
            }
            description="Mời học sinh tham gia bằng mã lớp trong tab Tổng quan."
          />
        ) : null}
      </div>
      <Pagination data={query.data} onPage={setPage} />
    </>
  );
}
