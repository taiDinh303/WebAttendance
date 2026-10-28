import {
  useLocation,
  useParams,
  NavLink,
  useNavigate,
  Link,
} from "react-router-dom";
import { useState } from "react";
import { BookOpen, Copy } from "lucide-react";
import { useSession } from "../../../application/sessionContext";
import { useResource, useRefresh } from "../../../application/queries";
import { classApi } from "../../../infrastructure/classApi";
import {
  PageHeader,
  QueryState,
  Badge,
  AsyncForm,
  Field,
  ActionButton,
} from "../../../shared/ui";
import { text } from "../../../domain/format";
import { MembersPanel } from "../components/MembersPanel";
import { AssignmentsPanel } from "../../assignments/components/AssignmentsPanel";
import { GradesPanel } from "../../grades/components/GradesPanel";
import { CalendarContent } from "../../calendar/pages/CalendarPage";
import { AttendanceHistory } from "../../attendance/components/AttendanceHistory";
import { CatalogFields } from "../components/CatalogFields";
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
  const refresh = useRefresh();
  const query = useResource(["class", classId], (s) =>
    classApi.get(classId, s),
  );
  const c = query.data;
  const teacher = user?.role === "teacher";
  const tabs = [
    ["overview", "Tổng quan"],
    ["assignments", "Bài tập"],
    ["schedule", "Lịch học"],
    ...(teacher ? [["members", "Thành viên"]] : []),
    ["attendance", "Điểm danh"],
    ["grades", "Điểm số"],
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
      <PageHeader
        eyebrow="KHÔNG GIAN LỚP HỌC"
        title={c.name}
        description={c.description || "Cùng học tập, cùng tiến bộ."}
        action={<Badge value={c.status} />}
      />
      <div className="class-shortcuts inline-actions">
        <Link
          className="button secondary small"
          to={"/app/classes/" + classId + "/assignments"}
        >
          Bài tập của lớp
        </Link>
        <Link
          className="button secondary small"
          to={"/app/classes/" + classId + "/schedule"}
        >
          Lịch & buổi học
        </Link>
        <span className="muted">Múi giờ · {c.timezone}</span>
      </div>
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
      {tab === "assignments" ? (
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
            <span className="stat-icon blue">
              <BookOpen />
            </span>
            <h2>Thông tin lớp học</h2>
            <p>{c.description || "Giáo viên chưa thêm mô tả."}</p>
            <p className="muted">Múi giờ lớp: {c.timezone}</p>
            {teacher ? (
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
                <button className="button secondary">Lưu thay đổi</button>
              </AsyncForm>
            ) : (
              <p className="info">
                Bạn chỉ xem được lớp khi yêu cầu tham gia đã được duyệt.
              </p>
            )}
          </section>
          {teacher ? (
            <aside className="panel editor-panel">
              <span className="eyebrow">KẾT NỐI VỚI HỌC SINH</span>
              <h2>Mã tham gia lớp</h2>
              <p className="muted">
                Chia sẻ mã với học sinh. Mọi yêu cầu vẫn cần bạn phê duyệt.
              </p>
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
                  Mã chỉ hiển thị khi tạo hoặc đổi mã. Tạo mã mới nếu không còn
                  giữ mã hiện tại.
                </p>
              )}
              <ActionButton
                className="button secondary"
                action={async () => {
                  if (!window.confirm("Đổi mã sẽ vô hiệu mã cũ. Tiếp tục?"))
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
              <hr />
              <ActionButton
                className="button secondary"
                action={async () => {
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
                      !window.confirm(
                        "Lưu trữ lớp sẽ dừng các thao tác học tập mới. Tiếp tục?",
                      )
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
            </aside>
          ) : (
            <aside className="tip-card">
              <h2>Mọi thứ, ngay tại đây.</h2>
              <p>
                Mở tab Bài tập để xem và nộp bài. Theo dõi lịch học, điểm danh
                và điểm số trong các tab tương ứng.
              </p>
              {c.membershipVersion !== undefined ? (
                <ActionButton
                  className="button danger"
                  action={async () => {
                    if (
                      !window.confirm(
                        "Rời lớp sẽ ngừng quyền truy cập ngay. Bạn vẫn có thể gửi yêu cầu tham gia lại. Tiếp tục?",
                      )
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
          )}
        </div>
      )}
    </>
  );
}
