import { useState } from "react";
import { ClassToolbar } from "../../../shared/components/ClassToolbar";
import { Modal } from "../../../shared/components/Modal";
import { askDialog } from "../../../shared/components/askDialog";
import { ExportButtons } from "../../exports/components/ExportButtons";
import { useSession } from "../../../application/sessionContext";
import { usePage, useResource, useRefresh } from "../../../application/queries";
import { attendanceApi } from "../../../infrastructure/attendanceApi";
import { classApi } from "../../../infrastructure/classApi";
import type {
  Attendance,
  Page,
  Membership,
  Session,
} from "../../../domain/models";
import {
  QueryState,
  Badge,
  Empty,
  Pagination,
  ActionButton,
  AsyncForm,
  Field,
} from "../../../shared/ui";
import { dateTime, text } from "../../../domain/format";
import { AttendanceAuditTimeline } from "./AttendanceAuditTimeline";

async function allPages<T>(
  load: (page: number) => Promise<Page<T>>,
): Promise<T[]> {
  const items: T[] = [];
  for (let page = 1; ; page++) {
    const result = await load(page);
    items.push(...result.items);
    if (!result.hasNextPage) return items;
  }
}
export function AttendanceHistory({ classId }: { classId: string }) {
  const { user } = useSession();
  const { page, setPage } = usePage();
  const refresh = useRefresh();
  const [bulkBusy, setBulkBusy] = useState(false);
  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [editing, setEditing] = useState<Attendance | "new" | null>(null);
  const query = useResource(["attendance", classId, page], (s) =>
    user?.role === "teacher"
      ? attendanceApi.records(classId, page, s)
      : attendanceApi.mine(classId, page, s),
  );
  const pending =
    query.data?.items.filter((record) => !record.approvedAt) ?? [];
  const selected = pending.filter((record) => selectedIds.includes(record.id));
  const allSelected = pending.length > 0 && selected.length === pending.length;
  return (
    <>
      <ClassToolbar>
        <h2 className="sr-only">Điểm danh</h2>
        {user?.role === "teacher" ? (
          <div className="inline-actions">
            <button className="button" onClick={() => setEditing("new")}>
              Thêm điểm danh
            </button>
            <ExportButtons classId={classId} kind="attendance" />
          </div>
        ) : null}
      </ClassToolbar>
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      {user?.role === "teacher" ? (
        <div className="inline-actions attendance-selection">
          <label className="check">
            <input
              type="checkbox"
              checked={allSelected}
              disabled={bulkBusy || !pending.length}
              onChange={(event) =>
                setSelectedIds(
                  event.target.checked
                    ? pending.map((record) => record.id)
                    : [],
                )
              }
            />
            Chọn trang này
          </label>
          <button
            className="button secondary small"
            disabled={bulkBusy || !selected.length}
            onClick={() => setSelectedIds([])}
          >
            Bỏ chọn
          </button>
          <ActionButton
            className="button small"
            errorPlacement="popup"
            disabled={!selected.length}
            action={async () => {
              if (
                !(await askDialog(
                  "Duyệt và khóa " +
                    selected.length +
                    " bản ghi đã chọn? Sau khi duyệt không thể sửa.",
                ))
              )
                return;
              setBulkBusy(true);
              try {
                const failed: string[] = [];
                let done = 0;
                for (const record of selected) {
                  try {
                    await attendanceApi.approve(classId, record);
                    done++;
                  } catch {
                    failed.push(record.id);
                  }
                }
                setSelectedIds(failed);
                await refresh();
                if (failed.length)
                  throw Error(
                    "Đã duyệt " +
                      done +
                      " bản ghi; " +
                      failed.length +
                      " bản ghi chưa duyệt được. Các dòng lỗi vẫn được chọn, hãy kiểm tra dữ liệu mới rồi thử lại.",
                  );
              } finally {
                setBulkBusy(false);
              }
            }}
          >
            {"Duyệt đã chọn (" + selected.length + ")"}
          </ActionButton>
        </div>
      ) : null}
      <div className="panel table-scroll">
        <table className="attendance-table">
          <thead>
            <tr>
              {user?.role === "teacher" ? <th>Chọn</th> : null}
              <th>Mã học sinh</th>
              <th>Họ và tên</th>
              <th>Buổi học</th>
              <th>Giờ ghi nhận</th>
              <th>Kết quả</th>
              <th>Duyệt</th>
              {user?.role === "teacher" ? <th>Thao tác</th> : null}
            </tr>
          </thead>
          <tbody>
            {query.data?.items.map((record) => (
              <tr key={record.id}>
                {user?.role === "teacher" ? (
                  <td>
                    <input
                      type="checkbox"
                      aria-label={
                        "Chọn " + (record.studentName || record.studentId)
                      }
                      disabled={bulkBusy || !!record.approvedAt}
                      checked={
                        !record.approvedAt && selectedIds.includes(record.id)
                      }
                      onChange={(event) =>
                        setSelectedIds((previous) =>
                          event.target.checked
                            ? [...new Set([...previous, record.id])]
                            : previous.filter((id) => id !== record.id),
                        )
                      }
                    />
                  </td>
                ) : null}
                <td>{record.studentCode || "—"}</td>
                <td>{record.studentName || user?.displayName}</td>
                <td>
                  {record.sessionTitle || "Buổi học"}
                  <br />
                  <small>
                    {record.sessionStartsAt
                      ? dateTime(record.sessionStartsAt)
                      : ""}
                  </small>
                </td>
                <td>
                  {record.checkedInAt
                    ? dateTime(record.checkedInAt)
                    : "Giáo viên / hệ thống"}
                </td>
                <td>
                  <Badge value={record.status} />
                </td>
                <td>
                  {record.approvedAt ? (
                    <>
                      Đã duyệt
                      <br />
                      <small>{dateTime(record.approvedAt)}</small>
                    </>
                  ) : (
                    "Chờ duyệt"
                  )}
                </td>
                {user?.role === "teacher" ? (
                  <td>
                    <div className="inline-actions">
                      {!record.approvedAt ? (
                        <>
                          <ActionButton
                            className="button small"
                            errorPlacement="popup"
                            disabled={bulkBusy}
                            action={async () => {
                              if (
                                !(await askDialog(
                                  "Duyệt và khóa kết quả của " +
                                    (record.studentName || "học sinh") +
                                    "? Sau khi duyệt không thể sửa.",
                                ))
                              )
                                return;
                              await attendanceApi.approve(classId, record);
                              await refresh();
                            }}
                          >
                            Duyệt
                          </ActionButton>
                          <button
                            className="button secondary small"
                            disabled={bulkBusy}
                            onClick={() => setEditing(record)}
                          >
                            Sửa
                          </button>
                        </>
                      ) : null}
                      <AttendanceAuditTimeline
                        classId={classId}
                        recordId={record.id}
                      />
                    </div>
                  </td>
                ) : null}
              </tr>
            ))}
          </tbody>
        </table>
        {query.isSuccess && !query.data.items.length ? (
          <Empty title="Chưa có điểm danh" />
        ) : null}
      </div>
      <Pagination data={query.data} onPage={setPage} />
      {editing ? (
        <AttendanceEditor
          classId={classId}
          record={editing}
          onClose={() => setEditing(null)}
        />
      ) : null}
    </>
  );
}
function AttendanceEditor({
  classId,
  record,
  onClose,
}: {
  classId: string;
  record: Attendance | "new";
  onClose: () => void;
}) {
  const refresh = useRefresh();
  const adding = record === "new";
  const [month, setMonth] = useState(() =>
    new Date().toISOString().slice(0, 7),
  );
  const members = useResource(
    ["attendance-members", classId],
    (signal) =>
      allPages<Membership>((page) =>
        classApi.members(classId, "members", page, signal),
      ),
    { enabled: adding },
  );
  const sessions = useResource(
    ["attendance-sessions", classId, month],
    (signal) =>
      allPages<Session>((pageNumber) =>
        classApi.sessions(
          {
            classId,
            status: "scheduled",
            pageNumber,
            pageSize: 100,
            from: new Date(month + "-01T00:00:00").toISOString(),
            to: new Date(
              Number(month.slice(0, 4)),
              Number(month.slice(5, 7)),
              1,
            ).toISOString(),
          },
          signal,
        ),
      ),
    { enabled: adding },
  );
  return (
    <Modal
      title={adding ? "Thêm điểm danh" : "Sửa điểm danh"}
      onClose={onClose}
    >
      <AsyncForm
        onSubmit={async (form) => {
          const studentId = adding ? text(form, "studentId") : record.studentId;
          const sessionId = adding ? text(form, "sessionId") : record.sessionId;
          if (!studentId || !sessionId)
            throw Error("Chọn học sinh đã duyệt và buổi học.");
          await attendanceApi.correct(
            classId,
            sessionId,
            {
              studentId,
              recordVersion: adding ? 0 : record.version,
              studentName: "",
              recordId: adding ? null : record.id,
              status: text(form, "status"),
            },
            text(form, "status"),
            text(form, "reason"),
          );
          await refresh();
          onClose();
        }}
      >
        {adding ? (
          <>
            <QueryState
              pending={members.isPending || sessions.isPending}
              error={members.error || sessions.error}
              retry={() => {
                void members.refetch();
                void sessions.refetch();
              }}
            />
            <Field label="Học sinh">
              <select name="studentId" required defaultValue="">
                <option value="">Chọn học sinh</option>
                {members.data
                  ?.filter((m) => m.status === "active")
                  .map((m) => (
                    <option key={m.studentId} value={m.studentId}>
                      {m.studentName || m.studentId}
                    </option>
                  ))}
              </select>
            </Field>
            <Field label="Tháng">
              <input
                type="month"
                value={month}
                required
                onChange={(event) => {
                  if (event.target.value) setMonth(event.target.value);
                }}
              />
            </Field>
            <Field label="Buổi học">
              <select key={month} name="sessionId" required defaultValue="">
                <option value="">Chọn buổi học</option>
                {sessions.data?.map((s) => (
                  <option key={s.id} value={s.id}>
                    {s.title || "Buổi học"} · {dateTime(s.startsAt)}
                  </option>
                ))}
              </select>
            </Field>
          </>
        ) : (
          <p>
            {record.studentName} · {record.sessionTitle || "Buổi học"}
          </p>
        )}
        <Field label="Kết quả">
          <select
            name="status"
            defaultValue={adding ? "present" : record.status}
          >
            <option value="present">Có mặt</option>
            <option value="late">Đi muộn</option>
            <option value="absent">Vắng</option>
          </select>
        </Field>
        <Field label="Lý do">
          <textarea
            name="reason"
            required
            minLength={3}
            maxLength={500}
            rows={3}
          />
        </Field>
        <p className="muted">
          Lưu vào trạng thái chờ duyệt. Thay đổi được ghi vào lịch sử.
        </p>
        <footer className="modal-actions">
          <button type="button" className="button secondary" onClick={onClose}>
            Hủy
          </button>
          <button
            className="button"
            disabled={
              adding &&
              (members.isPending ||
                sessions.isPending ||
                !!members.error ||
                !!sessions.error)
            }
          >
            Lưu
          </button>
        </footer>
      </AsyncForm>
    </Modal>
  );
}
