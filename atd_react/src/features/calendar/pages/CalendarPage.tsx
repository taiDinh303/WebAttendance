import { useState } from "react";
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
import { dateTime, text, iso } from "../../../domain/format";
import { SessionEditor } from "../components/SessionEditor";
import type { Session } from "../../../domain/models";
export default function CalendarPage() {
  return (
    <>
      <PageHeader
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
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<Session | null>(null);
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
      <div className="section-heading">
        <h2>Các buổi học</h2>
        <div className="inline-actions">
          <input
            aria-label="Tháng xem lịch"
            type="month"
            required
            value={month}
            onChange={(e) => {
              if (e.target.value) {
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
      </div>
      {!classId && user?.role === "teacher" ? (
        <p className="muted">Vào tab Lịch học của từng lớp để tạo buổi học.</p>
      ) : null}
      {open && classId ? (
        <section className="panel editor-panel">
          <h2>Tạo lịch học</h2>
          <label className="check">
            <input
              type="checkbox"
              checked={weekly}
              onChange={(e) => {
                setWeekly(e.target.checked);
                setPreview(null);
              }}
            />
            Lặp hằng tuần theo múi giờ lớp
          </label>
          <AsyncForm
            key={String(weekly)}
            success="Lịch học đã được tạo."
            onSubmit={async (f) => {
              if (weekly) {
                const payload = {
                  title: text(f, "title"),
                  room: text(f, "room"),
                  startDate: text(f, "startDate"),
                  endDate: text(f, "endDate"),
                  startTime: text(f, "startTime"),
                  endTime: text(f, "endTime"),
                  weekdays: f.getAll("weekdays").map(Number),
                  allowConflicts: f.has("allowConflicts"),
                };
                const result = await classApi.series(
                  classId,
                  payload,
                  text(f, "intent") === "preview",
                );
                if (text(f, "intent") === "preview") {
                  setPreview(result.count ?? 0);
                  return;
                }
              } else
                await classApi.saveSession(classId, {
                  title: text(f, "title"),
                  room: text(f, "room"),
                  startsAt: iso(f.get("startsAt")),
                  endsAt: iso(f.get("endsAt")),
                  allowConflicts: f.has("allowConflicts"),
                });
              await refresh();
              setOpen(false);
            }}
          >
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
                    <input name="startDate" type="date" required />
                  </Field>
                  <Field label="Đến ngày">
                    <input name="endDate" type="date" required />
                  </Field>
                  <Field label="Giờ bắt đầu (múi giờ lớp)">
                    <input name="startTime" type="time" required />
                  </Field>
                  <Field label="Giờ kết thúc">
                    <input name="endTime" type="time" required />
                  </Field>
                </>
              ) : (
                <>
                  <Field label="Bắt đầu (giờ máy của bạn)">
                    <input name="startsAt" type="datetime-local" required />
                  </Field>
                  <Field label="Kết thúc">
                    <input name="endsAt" type="datetime-local" required />
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
              <input type="checkbox" name="allowConflicts" />
              Tôi chấp nhận nếu lịch bị trùng
            </label>
            <button className="button">Tạo lịch</button>
            {weekly ? (
              <button
                className="button secondary"
                type="submit"
                name="intent"
                value="preview"
              >
                Xem số buổi trước
              </button>
            ) : null}
            {preview !== null ? (
              <p className="info">Dự kiến {preview} buổi học.</p>
            ) : null}
          </AsyncForm>
        </section>
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
            <Badge value={s.status} />
            {user?.role === "teacher" && s.status === "scheduled" ? (
              <div className="inline-actions">
                <button
                  className="button secondary small"
                  onClick={() => setEditing(s)}
                >
                  Sửa buổi
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
                    const reason = window.prompt(
                      "Lý do hủy buổi học (ít nhất 3 ký tự)",
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
