import type { Session } from "../../../domain/models";
import { localDateTimeInput } from "../../../domain/schedule";
import { iso, text } from "../../../domain/format";
import { classApi } from "../../../infrastructure/classApi";
import { useRefresh } from "../../../application/queries";
import { AsyncForm, Field } from "../../../shared/ui";

export function SessionEditor({
  session,
  onClose,
}: {
  session: Session;
  onClose: () => void;
}) {
  const refresh = useRefresh();
  return (
    <section className="session-editor">
      <h3>Sửa buổi học</h3>
      <p className="muted">
        Giờ theo thiết bị của bạn. Buổi đã có lịch sử điểm danh sẽ được máy chủ
        bảo vệ khỏi đổi lịch.
      </p>
      <AsyncForm
        onSubmit={async (form) => {
          await classApi.saveSession(
            session.classId,
            {
              title: text(form, "title"),
              room: text(form, "room"),
              startsAt: iso(form.get("startsAt")),
              endsAt: iso(form.get("endsAt")),
              expectedVersion: session.version,
              allowConflicts: form.has("allowConflicts"),
            },
            session.id,
          );
          await refresh();
          onClose();
        }}
      >
        <div className="form-grid">
          <Field label="Tên buổi học cần sửa">
            <input
              name="title"
              maxLength={200}
              defaultValue={session.title ?? ""}
            />
          </Field>
          <Field label="Phòng học cần sửa">
            <input
              name="room"
              maxLength={120}
              defaultValue={session.room ?? ""}
            />
          </Field>
          <Field label="Bắt đầu mới (giờ máy)">
            <input
              name="startsAt"
              type="datetime-local"
              step={1}
              required
              defaultValue={localDateTimeInput(session.startsAt)}
            />
          </Field>
          <Field label="Kết thúc mới (giờ máy)">
            <input
              name="endsAt"
              type="datetime-local"
              step={1}
              required
              defaultValue={localDateTimeInput(session.endsAt)}
            />
          </Field>
        </div>
        <label className="check">
          <input name="allowConflicts" type="checkbox" />
          Tôi chấp nhận nếu lịch bị trùng
        </label>
        <div className="inline-actions">
          <button className="button">Lưu buổi học</button>
          <button type="button" className="button secondary" onClick={onClose}>
            Hủy sửa buổi
          </button>
        </div>
      </AsyncForm>
    </section>
  );
}
