import { useSession } from "../application/sessionContext";
import type { Classroom } from "../domain/models";
import { useAccountPreference } from "../application/accountPreference";
import { useState } from "react";
import { NavLink } from "react-router-dom";
import { useResource } from "../application/queries";
import { classApi } from "../infrastructure/classApi";
import { Modal } from "../shared/components/Modal";
import { ErrorNotice, QueryState } from "../shared/ui";
export function SidebarClasses({
  actorId,
  onNavigate,
}: {
  actorId: string;
  onNavigate: () => void;
}) {
  void actorId;
  const { user } = useSession();
  const teacher = user?.role === "teacher";
  const groupId = (c: Classroom) =>
    teacher ? (c.semesterId ?? "none") : "none";
  const preference = useAccountPreference<string[]>("sidebar-hidden", []);
  const hidden = preference.value;
  const [editing, setEditing] = useState(false);
  const classes = useResource(["sidebar-classes"], async (signal) => {
    const items = [];
    let page = 1;
    while (true) {
      const result = await classApi.list(
        { status: "active", pageNumber: page },
        signal,
      );
      items.push(...result.items);
      if (!result.hasNextPage) return items;
      page++;
    }
  });
  const terms = useResource(
    ["catalog-options", "semesters"],
    (signal) => classApi.catalogOptions("semesters", signal),
    { enabled: teacher },
  );
  const toggle = (id: string) =>
    void preference.save((prev) =>
      prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id],
    );
  const groups = [...new Set(classes.data?.map((c) => groupId(c)) ?? [])];
  return (
    <section className="sidebar-classes">
      <ErrorNotice error={preference.error} />
      <div className="sidebar-class-heading">
        <strong>Lớp của bạn</strong>
        <button
          className="button secondary small"
          onClick={() => setEditing(true)}
        >
          Ghim
        </button>
      </div>
      <QueryState
        pending={classes.isPending || (teacher && terms.isPending)}
        error={classes.error || terms.error}
        retry={() => {
          void classes.refetch();
          void terms.refetch();
        }}
      />
      {groups
        .filter((id) => !hidden.includes("term:" + id))
        .map((id) => (
          <details key={id} open>
            <summary>
              {terms.data?.find((t) => t.id === id)?.name ??
                (!teacher
                  ? "Đang theo học"
                  : id === "none"
                    ? "Chưa gắn học kỳ"
                    : "Học kỳ")}
            </summary>
            <nav>
              {classes.data
                ?.filter((c) => groupId(c) === id && !hidden.includes(c.id))
                .map((c) => (
                  <NavLink
                    key={c.id}
                    title={c.name}
                    to={"/app/classes/" + c.id}
                    onClick={onNavigate}
                  >
                    {c.name}
                  </NavLink>
                ))}
            </nav>
          </details>
        ))}
      {editing ? (
        <Modal title="Ghim lớp trên sidebar" onClose={() => setEditing(false)}>
          <p className="muted">
            Bỏ chọn để ẩn khỏi sidebar. Lớp vẫn hoạt động bình thường. Lựa chọn
            lưu theo tài khoản trên các thiết bị.
          </p>
          {groups.map((id) => (
            <section key={id}>
              <label className="check">
                <input
                  type="checkbox"
                  disabled={!preference.ready || preference.saving}
                  checked={!hidden.includes("term:" + id)}
                  onChange={() => toggle("term:" + id)}
                />
                <strong>
                  {terms.data?.find((t) => t.id === id)?.name ??
                    (!teacher
                      ? "Đang theo học"
                      : id === "none"
                        ? "Chưa gắn học kỳ"
                        : "Học kỳ")}
                </strong>
              </label>
              {classes.data
                ?.filter((c) => groupId(c) === id)
                .map((c) => (
                  <label className="check" key={c.id}>
                    <input
                      type="checkbox"
                      disabled={
                        !preference.ready ||
                        preference.saving ||
                        hidden.includes("term:" + id)
                      }
                      checked={!hidden.includes(c.id)}
                      onChange={() => toggle(c.id)}
                    />
                    {c.name}
                  </label>
                ))}
            </section>
          ))}
          <footer className="modal-actions">
            <button className="button" onClick={() => setEditing(false)}>
              Đóng
            </button>
          </footer>
        </Modal>
      ) : null}
    </section>
  );
}
