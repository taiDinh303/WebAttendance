import { useState } from "react";
import type { RosterRow } from "../../../domain/models";
import { attendanceApi } from "../../../infrastructure/attendanceApi";
import { useRefresh } from "../../../application/queries";
import { AsyncForm, Field, Badge } from "../../../shared/ui";
import { text } from "../../../domain/format";
export function RosterEntry({
  classId,
  sessionId,
  row,
  refreshing,
}: {
  classId: string;
  sessionId: string;
  row: RosterRow;
  refreshing: boolean;
}) {
  const [draft, setDraft] = useState<{
    version: number;
    status: string;
  } | null>(null);
  const refresh = useRefresh();
  return (
    <article className="roster-entry">
      <div className="list-row">
        <div>
          <strong>{row.studentName}</strong>
          <div>
            <Badge value={row.status} />
          </div>
        </div>
        <button
          className="button secondary small"
          disabled={refreshing}
          onClick={() =>
            setDraft({
              version: row.recordVersion,
              status: row.status === "unmarked" ? "present" : row.status,
            })
          }
        >
          Điều chỉnh
        </button>
      </div>
      {draft ? (
        <div className="correction-form">
          <AsyncForm
            onSubmit={async (f) => {
              await attendanceApi.correct(
                classId,
                sessionId,
                { ...row, recordVersion: draft.version },
                text(f, "status"),
                text(f, "reason"),
              );
              await refresh();
              setDraft(null);
            }}
          >
            <Field label="Kết quả mới">
              <select name="status" defaultValue={draft.status}>
                <option value="present">Có mặt</option>
                <option value="late">Đi muộn</option>
                <option value="absent">Vắng</option>
              </select>
            </Field>
            <Field label="Lý do điều chỉnh">
              <textarea
                name="reason"
                required
                minLength={3}
                maxLength={500}
                rows={2}
              />
            </Field>
            <p className="muted">
              Thay đổi được lưu vào lịch sử. Nếu kết quả đã được sửa bởi người
              khác, hãy xem lại trước khi lưu.
            </p>
            <div className="inline-actions">
              <button className="button small">Lưu điều chỉnh</button>
              <button
                type="button"
                className="button secondary small"
                onClick={() => setDraft(null)}
              >
                Hủy chỉnh sửa
              </button>
            </div>
          </AsyncForm>
        </div>
      ) : null}
    </article>
  );
}
