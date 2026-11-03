import { Modal } from "../../../shared/components/Modal";
import type { Session } from "../../../domain/models";
import { localDateTimeInput, parseScheduleDateTime } from "../../../domain/schedule";
import { text } from "../../../domain/format";
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
    <Modal title="Điều chỉnh buổi học" onClose={onClose}>
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
              startsAt: parseScheduleDateTime(text(form,"startsAt")),
              endsAt: parseScheduleDateTime(text(form,"endsAt")),
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
          <Field label="Bắt đầu mới">
            <input
              name="startsAt"
              type="text" placeholder="dd/mm/yyyy hh:mm"
              step={1}
              required
              defaultValue={localDateTimeInput(session.startsAt)}
            />
          </Field>
          <Field label="Kết thúc mới">
            <input
              name="endsAt"
              type="text" placeholder="dd/mm/yyyy hh:mm"
              step={1}
              required
              defaultValue={localDateTimeInput(session.endsAt)}
            />
          </Field>
        </div>
        <label className="check">
          <input name="allowConflicts" type="checkbox" />
          Chấp nhận trùng
        </label>
        <footer className="modal-actions">
          <button type="button" className="button secondary" onClick={onClose}>
            Đóng
          </button>
          <button className="button">Lưu</button>
        </footer>
      </AsyncForm>
    </Modal>
  );
}
