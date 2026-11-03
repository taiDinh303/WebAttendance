import { ClassToolbar } from "../../../shared/components/ClassToolbar";
import { askDialog } from "../../../shared/components/askDialog";
import { Modal } from "../../../shared/components/Modal";
import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { Plus, MapPin, ScanLine } from "lucide-react";
import { useSession } from "../../../application/sessionContext";
import { usePage, useResource, useRefresh } from "../../../application/queries";
import { classApi } from "../../../infrastructure/classApi";
import {
  PageHeader,
  AsyncForm,
  Field,
  QueryState,
  Empty,
  Badge,
  Pagination,
  ActionButton,
} from "../../../shared/ui";
import { dateTime, text } from "../../../domain/format";
import { SessionEditor } from "../components/SessionEditor";
import {
  parseScheduleDate,
  parseScheduleTime,
  parseScheduleDateTime,
  sessionDisplayStatus,
} from "../../../domain/schedule";
import type { Session } from "../../../domain/models";
export default function CalendarPage() {
  return (
    <>
      <PageHeader
        compact
        eyebrow="SẮP XẾP & CHỦ ĐỘNG"
        title="Lịch học"
        description="Các buổi học của bạn, ở cùng một nơi."
      />
      <CalendarContent />
    </>
  );
}
export function CalendarContent({ classId }: { classId?: string }) {
  const { user } = useSession();
  const { page, setPage } = usePage();
  const [month, setMonth] = useState(() =>
    new Date().toISOString().slice(0, 7),
  );
  const [now, setNow] = useState(() => Date.now());
  useEffect(() => {
    const update = () => setNow(Date.now());
    const timer = window.setInterval(update, 30000);
    window.addEventListener("focus", update);
    document.addEventListener("visibilitychange", update);
    return () => {
      window.clearInterval(timer);
      window.removeEventListener("focus", update);
      document.removeEventListener("visibilitychange", update);
    };
  }, []);
  const [selected, setSelected] = useState<Session[]>([]);
  const [bulkOpen, setBulkOpen] = useState(false);
  const [bulkLoading, setBulkLoading] = useState(false);
  const [bulkResult, setBulkResult] = useState("");
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<Session | null>(null);
  const [viewing, setViewing] = useState<Session | null>(null);
  const [weekly, setWeekly] = useState(false);
  const [preview, setPreview] = useState<number | null>(null);
  const refresh = useRefresh();
  const [from, to] = [
    new Date(month + "-01T00:00:00"),
    new Date(Number(month.slice(0, 4)), Number(month.slice(5, 7)), 1),
  ];
  const query = useResource(["calendar", classId, month, page], (s) =>
    classApi.sessions(
      {
        from: from.toISOString(),
        to: to.toISOString(),
        pageNumber: page,
        ...(classId ? { classId } : {}),
      },
      s,
    ),
  );
  return (
    <>
      <ClassToolbar>
        <div className="inline-actions">
          <input
            aria-label="Tháng xem lịch"
            type="month"
            disabled={bulkLoading}
            required
            value={month}
            onChange={(e) => {
              if (e.target.value) {
                setSelected([]);
                setBulkResult("");
                setMonth(e.target.value);
                setPage(1);
              }
            }}
          />
          {classId && user?.role === "teacher" ? (
            <button className="button small" onClick={() => setOpen(!open)}>
              <Plus size={16} />
              Tạo buổi học
            </button>
          ) : null}
        </div>
      </ClassToolbar>
      {user?.role === "teacher" ? (
        <div className="bulk-session-toolbar inline-actions">
          <label className="check">
            <input
              type="checkbox"
              disabled={
                bulkLoading ||
                !query.data?.items.some((s) => s.status === "scheduled")
              }
              checked={
                !!query.data?.items.some((s) => s.status === "scheduled") &&
                query.data.items
                  .filter((s) => s.status === "scheduled")
                  .every((s) => selected.some((x) => x.id === s.id))
              }
              onChange={(e) => {
                const rows =
                  query.data?.items.filter((s) => s.status === "scheduled") ??
                  [];
                setSelected((prev) =>
                  e.target.checked
                    ? [
                        ...prev.filter((x) => !rows.some((r) => r.id === x.id)),
                        ...rows,
                      ]
                    : prev.filter((x) => !rows.some((r) => r.id === x.id)),
                );
              }}
            />
            Chọn trang này
          </label>
          <ActionButton
            className="button secondary small"
            disabled={bulkLoading}
            action={async () => {
              setBulkLoading(true);
              try {
                const rows: Session[] = [];
                let next = 1;
                while (true) {
                  const result = await classApi.sessions({
                    from: from.toISOString(),
                    to: to.toISOString(),
                    pageNumber: next,
                    ...(classId ? { classId } : {}),
                  });
                  rows.push(
                    ...result.items.filter((s) => s.status === "scheduled"),
                  );
                  if (!result.hasNextPage) break;
                  next++;
                }
                setSelected(rows);
                setBulkResult("");
              } finally {
                setBulkLoading(false);
              }
            }}
          >
            Chọn tất cả trong tháng
          </ActionButton>
          <button
            className="button secondary small"
            disabled={bulkLoading || !selected.length}
            onClick={() => setSelected([])}
          >
            Bỏ chọn
          </button>
          <button
            className="button danger small"
            disabled={bulkLoading || !selected.length}
            onClick={() => setBulkOpen(true)}
          >
            Hủy đã chọn ({selected.length})
          </button>
        </div>
      ) : null}
      {bulkResult ? <p role="status">{bulkResult}</p> : null}
      {bulkOpen ? (
        <Modal
          title={"Hủy " + selected.length + " buổi học"}
          onClose={() => setBulkOpen(false)}
        >
          <p>Các buổi được chọn sẽ chuyển sang Đã hủy. Lịch sử được giữ lại.</p>
          <AsyncForm
            onSubmit={async (f) => {
              const reason = text(f, "reason");
              const failed: Session[] = [];
              let done = 0;
              setBulkLoading(true);
              try {
                for (const session of selected) {
                  try {
                    await classApi.cancelSession(
                      session.classId,
                      session,
                      reason,
                    );
                    done++;
                  } catch {
                    failed.push(session);
                  }
                }
                setSelected(failed);
                setBulkResult(
                  "Đã hủy " +
                    done +
                    " buổi" +
                    (failed.length
                      ? "; " + failed.length + " buổi chưa hủy."
                      : "."),
                );
                await refresh();
                if (failed.length)
                  throw Error(
                    "Còn " +
                      failed.length +
                      " buổi chưa hủy. Có thể lịch đã thay đổi hoặc đang có điểm danh. Đóng hộp thoại, kiểm tra lại trước khi thử tiếp.",
                  );
                setBulkOpen(false);
              } finally {
                setBulkLoading(false);
              }
            }}
          >
            <Field label="Lý do hủy">
              <textarea
                name="reason"
                required
                minLength={3}
                maxLength={500}
                rows={3}
              />
            </Field>
            <footer className="modal-actions">
              <button
                className="button secondary"
                type="button"
                onClick={() => setBulkOpen(false)}
              >
                Đóng
              </button>
              <button className="button danger">
                Xác nhận hủy {selected.length} buổi
              </button>
            </footer>
          </AsyncForm>
        </Modal>
      ) : null}
      {open && classId ? (
        <Modal title="Tạo lịch học" onClose={() => setOpen(false)}>
          <AsyncForm
            onSubmit={async (f) => {
              if (weekly) {
                const payload = {
                  title: text(f, "title"),
                  room: text(f, "room"),
                  startDate: parseScheduleDate(text(f, "startDate")),
                  endDate: parseScheduleDate(text(f, "endDate")),
                  startTime: parseScheduleTime(text(f, "startTime")),
                  endTime: parseScheduleTime(text(f, "endTime")),
                  weekdays: f.getAll("weekdays").map(Number),
                  allowConflicts: f.has("allowConflicts"),
                };
                const creating =
                  text(f, "intent") === "create" && preview !== null;
                if (
                  creating &&
                  !(await askDialog(
                    "Tạo " + preview + " buổi học theo lịch đã xem trước?",
                  ))
                )
                  return;
                const result = await classApi.series(
                  classId,
                  payload,
                  !creating,
                );
                if (!creating) {
                  setPreview(result.count ?? result.sessionCount ?? 0);
                  return;
                }
              } else
                await classApi.saveSession(classId, {
                  title: text(f, "title"),
                  room: text(f, "room"),
                  startsAt: parseScheduleDateTime(text(f, "startsAt")),
                  endsAt: parseScheduleDateTime(text(f, "endsAt")),
                  allowConflicts: f.has("allowConflicts"),
                });
              await refresh();
              setOpen(false);
            }}
          >
            <div onChange={() => setPreview(null)}>
              <div className="form-grid">
                <Field label="Tên buổi học">
                  <input name="title" maxLength={200} />
                </Field>
                <Field label="Phòng / địa điểm">
                  <input name="room" maxLength={120} />
                </Field>
                {weekly ? (
                  <>
                    <Field label="Từ ngày">
                      <input
                        name="startDate"
                        type="text"
                        placeholder="dd/mm/yyyy"
                        required
                      />
                    </Field>
                    <Field label="Đến ngày">
                      <input
                        name="endDate"
                        type="text"
                        placeholder="dd/mm/yyyy"
                        required
                      />
                    </Field>
                    <Field label="Giờ bắt đầu">
                      <input
                        name="startTime"
                        type="text"
                        placeholder="08:30"
                        required
                      />
                    </Field>
                    <Field label="Giờ kết thúc">
                      <input
                        name="endTime"
                        type="text"
                        placeholder="10:00"
                        required
                      />
                    </Field>
                  </>
                ) : (
                  <>
                    <Field label="Bắt đầu">
                      <input
                        name="startsAt"
                        type="text"
                        placeholder="03/11/2026 08:30"
                        required
                      />
                    </Field>
                    <Field label="Kết thúc">
                      <input
                        name="endsAt"
                        type="text"
                        placeholder="03/11/2026 10:00"
                        required
                      />
                    </Field>
                  </>
                )}
              </div>
              {weekly ? (
                <div className="weekday-picker">
                  {["T2", "T3", "T4", "T5", "T6", "T7", "CN"].map((day, i) => (
                    <label className="check" key={day}>
                      <input type="checkbox" name="weekdays" value={i + 1} />
                      {day}
                    </label>
                  ))}
                </div>
              ) : null}
              <label className="check">
                <input
                  type="checkbox"
                  checked={weekly}
                  onChange={(e) => {
                    setWeekly(e.target.checked);
                    setPreview(null);
                  }}
                />
                Lặp hằng tuần
              </label>
              <label className="check">
                <input type="checkbox" name="allowConflicts" />
                Chấp nhận trùng
              </label>
            </div>
            <footer className="modal-actions">
              <button
                type="button"
                className="button secondary"
                onClick={() => setOpen(false)}
              >
                Hủy
              </button>
              {weekly && preview === null ? (
                <button
                  className="button secondary"
                  type="submit"
                  name="intent"
                  value="preview"
                >
                  Xem trước số buổi
                </button>
              ) : (
                <button
                  className="button"
                  type="submit"
                  name="intent"
                  value="create"
                >
                  {weekly && preview !== null ? "Tạo lịch" : "Tạo buổi học"}
                </button>
              )}
            </footer>
            {preview !== null ? (
              <p className="info">
                Dự kiến {preview} buổi học trong khoảng thời gian trên. Bấm “Tạo
                lịch” để xác nhận.
              </p>
            ) : null}
          </AsyncForm>
        </Modal>
      ) : null}
      {viewing ? (
        <Modal
          title={viewing.title || "Buổi học"}
          onClose={() => setViewing(null)}
        >
          <dl className="profile-facts">
            <dt>Thời gian</dt>
            <dd>
              {dateTime(viewing.startsAt)} → {dateTime(viewing.endsAt)}
            </dd>
            <dt>Phòng học</dt>
            <dd>{viewing.room || "Chưa cung cấp"}</dd>
          </dl>
          <footer className="modal-actions">
            <button
              className="button secondary"
              onClick={() => setViewing(null)}
            >
              Đóng
            </button>
            <button
              className="button"
              onClick={() => {
                setEditing(viewing);
                setViewing(null);
              }}
            >
              Điều chỉnh
            </button>
          </footer>
        </Modal>
      ) : null}
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      <div className="panel">
        {editing ? (
          <SessionEditor
            key={editing.id + ":" + editing.version}
            session={editing}
            onClose={() => setEditing(null)}
          />
        ) : null}
        {query.data?.items.map((s) => (
          <article className="list-row calendar-row" key={s.id}>
            {user?.role === "teacher" ? (
              <input
                className="session-selector"
                type="checkbox"
                aria-label={
                  "Chọn buổi " +
                  (s.title || "Buổi học") +
                  " · " +
                  dateTime(s.startsAt)
                }
                disabled={bulkLoading || s.status !== "scheduled"}
                checked={selected.some((x) => x.id === s.id)}
                onChange={(e) =>
                  setSelected((prev) =>
                    e.target.checked
                      ? [...prev.filter((x) => x.id !== s.id), s]
                      : prev.filter((x) => x.id !== s.id),
                  )
                }
              />
            ) : null}
            <div className="date-tile">
              <strong>{new Date(s.startsAt).getDate()}</strong>
              <small>THÁNG {new Date(s.startsAt).getMonth() + 1}</small>
            </div>
            <div className="grow">
              <h3>{s.title || "Buổi học"}</h3>
              <p>
                {dateTime(s.startsAt)} → {dateTime(s.endsAt)}
              </p>
              {s.room ? (
                <small>
                  <MapPin size={12} />
                  {s.room}
                </small>
              ) : null}
            </div>
            <Badge value={sessionDisplayStatus(s, now)} />
            {user?.role === "teacher" && s.status === "scheduled" ? (
              <div className="inline-actions">
                <button
                  className="button secondary small"
                  onClick={() => setViewing(s)}
                >
                  Xem
                </button>
                <Link
                  className="button secondary small"
                  to={"/app/classes/" + s.classId + "/sessions/" + s.id + "/qr"}
                >
                  <ScanLine size={16} />
                  Điểm danh QR
                </Link>
                <ActionButton
                  className="button ghost small"
                  action={async () => {
                    const reason = await askDialog(
                      "Lý do hủy buổi học (ít nhất 3 ký tự)",
                      true,
                    );
                    if (!reason) return;
                    await classApi.cancelSession(s.classId, s, reason);
                    await refresh();
                  }}
                >
                  Hủy
                </ActionButton>
              </div>
            ) : null}
          </article>
        ))}
        {query.isSuccess && !query.data.items.length ? (
          <Empty
            title="Chưa có buổi học trong tháng"
            description="Lịch học do giáo viên tạo sẽ xuất hiện tại đây."
          />
        ) : null}
      </div>
      <Pagination data={query.data} onPage={setPage} />
    </>
  );
}
