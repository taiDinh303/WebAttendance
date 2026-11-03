import { ClassToolbar } from "../../../shared/components/ClassToolbar";
import { askDialog } from "../../../shared/components/askDialog";
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
import type { Membership } from "../../../domain/models";
import { initials } from "../../../domain/format";
export function MembersPanel({ classId }: { classId: string }) {
  const { page, setPage, search } = usePage();
  const [kind, setKind] = useState<"members" | "join-requests">(() =>
    search.get("queue") === "pending" ? "join-requests" : "members",
  );
  const refresh = useRefresh();
  const query = useResource(["members", classId, kind, page], (s) =>
    classApi.members(classId, kind, page, s),
  );
  return (
    <>
      <ClassToolbar>
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
      </ClassToolbar>
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      <div className="panel member-desktop-table">
        <table
          aria-label={
            kind === "members" ? "Danh sách học sinh" : "Yêu cầu vào lớp"
          }
        >
          <colgroup>
            <col style={{ width: "40%" }} />
            <col style={{ width: "16%" }} />
            <col style={{ width: "20%" }} />
            <col style={{ width: "24%" }} />
          </colgroup>
          <thead>
            <tr>
              <th>Học sinh</th>
              <th>Giới tính</th>
              <th>Ngày sinh</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            {query.data?.items.map((m) => (
              <tr key={m.id}>
                <td>
                  <small className="member-code">
                    {m.studentCode || "Chưa có mã sinh viên"}
                  </small>
                  <strong className="member-name">
                    {m.studentName || m.studentId}
                  </strong>
                  {m.status !== "active" ? <Badge value={m.status} /> : null}
                </td>
                <td>
                  {{
                    male: "Nam",
                    female: "Nữ",
                    other: "Khác",
                    undisclosed: "Không chia sẻ",
                  }[m.gender ?? ""] || "Chưa cập nhật"}
                </td>
                <td>
                  {m.birthday
                    ? new Date(m.birthday + "T00:00:00").toLocaleDateString(
                        "vi-VN",
                      )
                    : "Chưa cập nhật"}
                </td>
                <td>
                  <MemberActions
                    member={m}
                    classId={classId}
                    refresh={refresh}
                  />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {query.isSuccess && !query.data.items.length ? (
          <Empty
            title={
              kind === "members" ? "Chưa có thành viên" : "Chưa có yêu cầu"
            }
          />
        ) : null}
      </div>
      <div className="panel member-mobile-cards">
        {query.data?.items.map((m) => (
          <div className="list-row member-row" key={m.id}>
            <div className="person">
              <span className="avatar">{initials(m.studentName ?? "HS")}</span>
              <div>
                <h3>{m.studentName ?? m.studentId}</h3>
                {m.status !== "active" ? <Badge value={m.status} /> : null}
                <dl className="member-basics">
                  <div>
                    <dt>Mã sinh viên</dt>
                    <dd>{m.studentCode || "Chưa cập nhật"}</dd>
                  </div>
                  <div>
                    <dt>Giới tính</dt>
                    <dd>
                      {{
                        male: "Nam",
                        female: "Nữ",
                        other: "Khác",
                        undisclosed: "Không chia sẻ",
                      }[m.gender ?? ""] || "Chưa cập nhật"}
                    </dd>
                  </div>
                  <div>
                    <dt>Ngày sinh</dt>
                    <dd>
                      {m.birthday
                        ? new Date(m.birthday + "T00:00:00").toLocaleDateString(
                            "vi-VN",
                          )
                        : "Chưa cập nhật"}
                    </dd>
                  </div>
                </dl>
              </div>
            </div>
            <MemberActions member={m} classId={classId} refresh={refresh} />
          </div>
        ))}
        {query.isSuccess && !query.data.items.length ? (
          <Empty
            title={
              kind === "members" ? "Chưa có thành viên" : "Chưa có yêu cầu"
            }
            description="Mời học sinh tham gia bằng mã lớp trong tab Cài đặt."
          />
        ) : null}
      </div>
      <Pagination data={query.data} onPage={setPage} />
    </>
  );
}

function MemberActions({
  member,
  classId,
  refresh,
}: {
  member: Membership;
  classId: string;
  refresh: () => Promise<void>;
}) {
  return (
    <div className="inline-actions">
      {(member.status === "pending"
        ? ["approve", "reject"]
        : member.status === "active"
          ? ["remove"]
          : member.status === "removed"
            ? ["allow-rejoin"]
            : []
      ).map((action) => (
        <ActionButton
          errorPlacement="popup"
          key={action}
          className={
            "button small " + (action === "approve" ? "" : "secondary")
          }
          action={async () => {
            if (
              action === "remove" &&
              !(await askDialog(
                "Loại học sinh khỏi lớp? Quyền truy cập sẽ dừng ngay.",
              ))
            )
              return;
            await classApi.membership(classId, member, action);
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
  );
}
