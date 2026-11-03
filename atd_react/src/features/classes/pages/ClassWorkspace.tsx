import { ClassBanner } from "../components/ClassBanner";
import { askDialog } from "../../../shared/components/askDialog";
import {
  useLocation,
  useParams,
  NavLink,
  Link,
  useNavigate,
} from "react-router-dom";
import { useState } from "react";
import { Copy, UserPlus, ArrowRight } from "lucide-react";
import { useSession } from "../../../application/sessionContext";
import { useResource, useRefresh } from "../../../application/queries";
import { classApi } from "../../../infrastructure/classApi";
import { QueryState, AsyncForm, Field, ActionButton } from "../../../shared/ui";
import { text } from "../../../domain/format";
import { MembersPanel } from "../components/MembersPanel";
import { AssignmentsPanel } from "../../assignments/components/AssignmentsPanel";
import { GradesPanel } from "../../grades/components/GradesPanel";
import { CalendarContent } from "../../calendar/pages/CalendarPage";
import { AttendanceHistory } from "../../attendance/components/AttendanceHistory";
import { CatalogFields } from "../components/CatalogFields";
import { AnnouncementsPanel } from "../../announcements/components/AnnouncementsPanel";
import { Modal } from "../../../shared/components/Modal";
export default function ClassWorkspace() {
  const { classId } = useParams();
  return <ClassWorkspaceContent key={classId} />;
}
function ClassWorkspaceContent() {
  const { classId = "", tab = "overview" } = useParams();
  const { user } = useSession();
  const location = useLocation();
  const navigate = useNavigate();
  const [invite, setInvite] = useState<string>(
    (location.state as { inviteCode?: string } | null)?.inviteCode ?? "",
  );
  const [editing, setEditing] = useState(false);
  const refresh = useRefresh();
  const query = useResource(["class", classId], (s) =>
    classApi.get(classId, s),
  );
  const c = query.data;
  const teacher = user?.role === "teacher";
  const pending = useResource(
    ["members", classId, "join-requests", 1],
    (signal) => classApi.members(classId, "join-requests", 1, signal),
    {
      enabled: teacher && query.isSuccess && tab === "overview",
      refetchInterval: 30000,
    },
  );
  const tabs = [
    ["overview", "Tổng quan"],
    ["announcements", "Bản tin"],
    ["assignments", "Bài tập"],
    ["schedule", "Lịch học"],
    ...(teacher ? [["members", "Thành viên"]] : []),
    ["attendance", "Điểm danh"],
    ["grades", "Điểm số"],
    ["settings", "Cài đặt"],
  ];
  if (!c)
    return (
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
    );
  return (
    <>
      <ClassBanner
        id={c.id}
        name={c.name}
        description={c.description}
        editable={teacher}
        actorId={user?.id ?? ""}
      />
      <nav className="tabs">
        {tabs.map(([key, label]) => (
          <NavLink
            key={key}
            to={
              "/app/classes/" + classId + (key === "overview" ? "" : "/" + key)
            }
            end
          >
            {label}
          </NavLink>
        ))}
      </nav>
      {teacher && tab === "overview" ? (
        <>
          <QueryState
            pending={false}
            error={pending.error}
            retry={pending.refetch}
          />
          {pending.data && pending.data.totalItems > 0 ? (
            <Link
              className="panel class-request-notice"
              to={"/app/classes/" + classId + "/members?queue=pending&page=1"}
            >
              <UserPlus size={20} />
              <span>
                Có <strong>{pending.data.totalItems}</strong> yêu cầu vào lớp
                đang chờ duyệt
              </span>
              <span className="request-notice-action">
                Xem yêu cầu <ArrowRight size={16} />
              </span>
            </Link>
          ) : null}
        </>
      ) : null}
      {tab === "announcements" ? (
        <AnnouncementsPanel classId={classId} />
      ) : tab === "assignments" ? (
        <AssignmentsPanel classId={classId} />
      ) : tab === "schedule" ? (
        <CalendarContent classId={classId} />
      ) : tab === "members" && teacher ? (
        <MembersPanel classId={classId} />
      ) : tab === "grades" ? (
        <GradesPanel classId={classId} />
      ) : tab === "attendance" ? (
        <AttendanceHistory classId={classId} />
      ) : (
        <div className="detail-grid">
          <section className="panel editor-panel">
            <div className="section-heading">
              <h2>Thông tin lớp</h2>
              {teacher && tab === "settings" ? (
                <button
                  className="button secondary small"
                  onClick={() => setEditing(true)}
                >
                  Sửa
                </button>
              ) : null}
            </div>
            {c.description ? (
              <p className="class-description">{c.description}</p>
            ) : null}
            <dl className="class-facts">
              {teacher ? (
                <>
                  <ClassCatalogFact
                    kind="courses"
                    id={c.courseId}
                    label="Môn học"
                  />
                  <ClassCatalogFact
                    kind="semesters"
                    id={c.semesterId}
                    label="Học kỳ"
                  />
                </>
              ) : null}
            </dl>
            {teacher && editing ? (
              <Modal
                title="Sửa thông tin lớp"
                onClose={() => setEditing(false)}
              >
                <AsyncForm
                  key={c.id}
                  success="Thông tin lớp đã được cập nhật."
                  onSubmit={async (f) => {
                    await classApi.update(classId, {
                      name: text(f, "name"),
                      description: text(f, "description"),
                      expectedVersion: c.version,
                      courseId: text(f, "courseId") || null,
                      semesterId: text(f, "semesterId") || null,
                    });
                    await refresh();
                    setEditing(false);
                  }}
                >
                  <Field label="Tên lớp">
                    <input
                      name="name"
                      defaultValue={c.name}
                      required
                      maxLength={200}
                    />
                  </Field>
                  <Field label="Mô tả">
                    <textarea
                      name="description"
                      defaultValue={c.description ?? ""}
                      rows={4}
                    />
                  </Field>
                  <CatalogFields
                    key={c.id + ":" + c.courseId + ":" + c.semesterId}
                    courseId={c.courseId}
                    semesterId={c.semesterId}
                  />
                  <footer className="modal-actions">
                    <button
                      type="button"
                      className="button secondary"
                      onClick={() => setEditing(false)}
                    >
                      Đóng
                    </button>
                    <button className="button">Lưu</button>
                  </footer>
                </AsyncForm>
              </Modal>
            ) : null}
          </section>
          {tab === "settings" ? (
            teacher ? (
              <aside className="panel editor-panel">
                <h2>Mã tham gia lớp</h2>
                <p className="muted">Học sinh nhập mã và chờ duyệt.</p>
                {invite ? (
                  <div className="invite-code">
                    <code>{invite}</code>
                    <ActionButton
                      className="icon-button"
                      title="Sao chép mã lớp"
                      action={() => navigator.clipboard.writeText(invite)}
                    >
                      <Copy size={18} />
                    </ActionButton>
                  </div>
                ) : (
                  <p className="info">
                    Tạo mã mới nếu bạn không còn giữ mã cũ.
                  </p>
                )}
                <ActionButton
                  className="button secondary"
                  action={async () => {
                    if (
                      !(await askDialog("Đổi mã sẽ vô hiệu mã cũ. Tiếp tục?"))
                    )
                      return;
                    const result = await classApi.action(
                      classId,
                      "invite-code/rotate",
                      { expectedVersion: c.version },
                    );
                    setInvite(result.inviteCode ?? "");
                    await refresh();
                  }}
                >
                  Tạo mã mới
                </ActionButton>
                <div className="inline-actions class-controls">
                  <ActionButton
                    className="button secondary"
                    action={async () => {
                      if (
                        !(await askDialog(
                          "Tắt mã tham gia? Học sinh sẽ không thể dùng mã hiện tại để xin vào lớp.",
                        ))
                      )
                        return;
                      await classApi.action(classId, "invite-code/disable", {
                        expectedVersion: c.version,
                      });
                      setInvite("");
                      await refresh();
                    }}
                  >
                    Tắt mã tham gia
                  </ActionButton>
                  {c.status === "active" ? (
                    <ActionButton
                      className="button danger"
                      action={async () => {
                        if (
                          !(await askDialog(
                            "Lưu trữ lớp sẽ dừng các thao tác học tập mới. Tiếp tục?",
                          ))
                        )
                          return;
                        await classApi.action(classId, "archive", {
                          expectedVersion: c.version,
                        });
                        await refresh();
                      }}
                    >
                      Lưu trữ lớp
                    </ActionButton>
                  ) : null}
                </div>
              </aside>
            ) : (
              <aside className="panel editor-panel">
                <h2>Rời lớp</h2>
                <p className="muted">
                  Rời lớp sẽ ngừng quyền truy cập của bạn.
                </p>
                {c.membershipVersion !== undefined ? (
                  <ActionButton
                    className="button danger"
                    action={async () => {
                      if (
                        !(await askDialog(
                          "Rời lớp sẽ ngừng quyền truy cập ngay. Bạn vẫn có thể gửi yêu cầu tham gia lại. Tiếp tục?",
                        ))
                      )
                        return;
                      await classApi.leave(classId, c.membershipVersion!);
                      await refresh();
                      navigate("/app/classes", { replace: true });
                    }}
                  >
                    Rời lớp học
                  </ActionButton>
                ) : null}
              </aside>
            )
          ) : null}
        </div>
      )}
    </>
  );
}

function ClassCatalogFact({
  kind,
  id,
  label,
}: {
  kind: "courses" | "semesters";
  id: string | null;
  label: string;
}) {
  const query = useResource(
    ["catalog", kind, id],
    (signal) => classApi.catalogItem(kind, id!, signal),
    { enabled: !!id },
  );
  return (
    <div>
      <dt>{label}</dt>
      <dd>{id ? (query.data?.name ?? "—") : "Chưa chọn"}</dd>
    </div>
  );
}
