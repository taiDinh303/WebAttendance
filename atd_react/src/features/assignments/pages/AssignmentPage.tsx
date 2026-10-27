import { useParams, Link } from "react-router-dom";
import { useSession } from "../../../application/sessionContext";
import { useResource, useRefresh } from "../../../application/queries";
import { learningApi } from "../../../infrastructure/learningApi";
import { fileUrl } from "../../../infrastructure/http";
import {
  PageHeader,
  QueryState,
  Badge,
  AsyncForm,
  Field,
  ActionButton,
} from "../../../shared/ui";
import { dateTime, text, iso } from "../../../domain/format";
import { SubmissionList } from "../components/SubmissionList";
import { StudentSubmit } from "../components/StudentSubmit";
export default function AssignmentPage() {
  const { classId = "", assignmentId = "" } = useParams();
  const { user } = useSession();
  const refresh = useRefresh();
  const query = useResource(["assignment", assignmentId], (s) =>
    learningApi.get(classId, assignmentId, s),
  );
  const data = query.data;
  if (!data)
    return (
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
    );
  const a = data.assignment;
  const teacher = user?.role === "teacher";
  return (
    <>
      <Link
        className="back-link"
        to={"/app/classes/" + classId + "/assignments"}
      >
        ← Bài tập của lớp
      </Link>
      <PageHeader
        eyebrow="BÀI TẬP"
        title={a.title}
        description={"Hạn nộp: " + dateTime(a.dueAt) + " · Thang điểm 10"}
        action={<Badge value={a.state} />}
      />
      <div className="detail-grid">
        <article className="panel editor-panel">
          <h2>Hướng dẫn bài tập</h2>
          <p className="pre-wrap">
            {a.instructions || "Giáo viên chưa thêm hướng dẫn."}
          </p>
          <div className="policy-row">
            <span>
              {a.allowLate ? "Cho phép nộp muộn" : "Không nhận nộp muộn"}
            </span>
            <span>{a.allowResubmit ? "Có thể nộp lại" : "Một lần nộp"}</span>
          </div>
          <h3>Tài liệu đính kèm</h3>
          {data.files.length ? (
            data.files.map((file) => (
              <a className="file-link" key={file.id} href={fileUrl(file.id)}>
                {file.originalName}{" "}
                <small>{(file.sizeBytes / 1024).toFixed(0)} KB ↓</small>
              </a>
            ))
          ) : (
            <p className="muted">Không có tài liệu đính kèm.</p>
          )}
        </article>
        {teacher ? (
          <section className="panel editor-panel">
            <h2>Quản lý bài tập</h2>
            <div className="inline-actions">
              {a.state === "draft" ? (
                <ActionButton
                  className="button"
                  action={async () => {
                    await learningApi.transition(classId, a, "publish");
                    await refresh();
                  }}
                >
                  Công bố bài tập
                </ActionButton>
              ) : a.state === "published" ? (
                <ActionButton
                  className="button secondary"
                  action={async () => {
                    if (!window.confirm("Đóng bài tập và dừng nhận bài mới?"))
                      return;
                    await learningApi.transition(classId, a, "close");
                    await refresh();
                  }}
                >
                  Đóng bài tập
                </ActionButton>
              ) : null}
            </div>
            {a.state !== "closed" ? (
              <>
                <AsyncForm
                  key={a.id}
                  success="Đã cập nhật bài tập."
                  onSubmit={async (f) => {
                    await learningApi.save(
                      classId,
                      {
                        title: text(f, "title"),
                        instructions: text(f, "instructions"),
                        dueAt: iso(f.get("dueAt")),
                        allowLate: f.has("allowLate"),
                        allowResubmit: f.has("allowResubmit"),
                        expectedVersion: a.version,
                      },
                      a.id,
                    );
                    await refresh();
                  }}
                >
                  <Field label="Tiêu đề">
                    <input
                      name="title"
                      required
                      maxLength={200}
                      defaultValue={a.title}
                    />
                  </Field>
                  <Field label="Hướng dẫn">
                    <textarea
                      name="instructions"
                      rows={4}
                      defaultValue={a.instructions ?? ""}
                    />
                  </Field>
                  <Field label="Hạn nộp (giờ máy của bạn)">
                    <input
                      type="datetime-local"
                      name="dueAt"
                      defaultValue={
                        a.dueAt
                          ? new Date(
                              new Date(a.dueAt).getTime() -
                                new Date(a.dueAt).getTimezoneOffset() * 60000,
                            )
                              .toISOString()
                              .slice(0, 16)
                          : ""
                      }
                    />
                  </Field>
                  <label className="check">
                    <input
                      name="allowLate"
                      type="checkbox"
                      defaultChecked={a.allowLate}
                    />
                    Nhận nộp muộn
                  </label>
                  <label className="check">
                    <input
                      name="allowResubmit"
                      type="checkbox"
                      defaultChecked={a.allowResubmit}
                    />
                    Cho nộp lại
                  </label>
                  <button className="button secondary">Lưu thay đổi</button>
                </AsyncForm>
                <hr />
                <AsyncForm
                  success="Đã gắn tài liệu vào bài tập."
                  onSubmit={async (f) => {
                    const files = f
                      .getAll("files")
                      .filter(
                        (v): v is File => v instanceof File && v.size > 0,
                      );
                    if (!files.length || files.length + data.files.length > 5)
                      throw Error("Tổng tài liệu đính kèm tối đa 5 file.");
                    const ids = data.files.map((file) => file.id);
                    for (const file of files) {
                      if (file.size > 20 * 1024 * 1024)
                        throw Error("Mỗi file tối đa 20 MiB.");
                      ids.push(
                        (
                          await learningApi.upload(
                            classId,
                            a.id,
                            "assignment_material",
                            file,
                          )
                        ).id,
                      );
                    }
                    await learningApi.save(
                      classId,
                      {
                        title: a.title,
                        allowLate: a.allowLate,
                        allowResubmit: a.allowResubmit,
                        fileIds: ids,
                        expectedVersion: a.version,
                      },
                      a.id,
                    );
                    await refresh();
                  }}
                >
                  <Field label="Thêm tài liệu">
                    <input
                      name="files"
                      type="file"
                      multiple
                      accept=".pdf,.docx,.xlsx,.pptx,.jpg,.jpeg,.png,.txt"
                    />
                  </Field>
                  <button className="button secondary">
                    Tải lên & gắn tài liệu
                  </button>
                </AsyncForm>
              </>
            ) : null}
          </section>
        ) : (
          <StudentSubmit classId={classId} assignment={a} />
        )}
      </div>
      <SubmissionList classId={classId} assignmentId={assignmentId} />
    </>
  );
}
