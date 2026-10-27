import { useState } from "react";
import { useParams } from "react-router-dom";
import { usePage, useResource, useRefresh } from "../../../application/queries";
import { useSession } from "../../../application/sessionContext";
import { learningApi } from "../../../infrastructure/learningApi";
import { ApiError, fileUrl } from "../../../infrastructure/http";
import {
  PageHeader,
  QueryState,
  Pagination,
  Empty,
  AsyncForm,
  Field,
  Badge,
} from "../../../shared/ui";
import { text, dateTime } from "../../../domain/format";
export default function SubmissionPage() {
  const { submissionId = "" } = useParams();
  const { page, setPage } = usePage();
  const { user } = useSession();
  const query = useResource(["revisions", submissionId, page], (s) =>
    learningApi.revisions(submissionId, page, s),
  );
  const [selected, setSelected] = useState<string | null>(null);
  const revision =
    query.data?.items.find((r) => r.id === selected) ?? query.data?.items[0];
  return (
    <>
      <PageHeader
        eyebrow="BÀI NỘP"
        title="Lịch sử & nhận xét"
        description="Mỗi lần nộp được giữ nguyên để có thể đối chiếu."
      />
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      {query.data && !query.data.items.length ? <Empty /> : null}
      <div className="detail-grid">
        <section className="panel editor-panel">
          <h2>Các lần nộp</h2>
          <div className="revision-tabs">
            {query.data?.items.map((r) => (
              <button
                key={r.id}
                className={
                  "button secondary small " +
                  (revision?.id === r.id ? "selected" : "")
                }
                onClick={() => setSelected(r.id)}
              >
                Lần {r.revisionNo}
              </button>
            ))}
          </div>
          {revision ? (
            <>
              <h3>Lần nộp {revision.revisionNo}</h3>
              <p className="muted">{dateTime(revision.submittedAt)}</p>
              <Badge value={revision.isLate ? "late" : "submitted"} />
              <p className="pre-wrap">{revision.note || "Không có ghi chú."}</p>
              {revision.files.map((f) => (
                <a className="file-link" key={f.id} href={fileUrl(f.id)}>
                  {f.originalName} ↓
                </a>
              ))}
            </>
          ) : null}
          <Pagination data={query.data} onPage={setPage} />
        </section>
        {revision && user?.role === "teacher" ? (
          <GradeEditor key={revision.id} revisionId={revision.id} />
        ) : (
          <aside className="tip-card">
            <h2>Điểm đã công bố</h2>
            <p>
              Xem điểm và nhận xét trong tab Điểm số của lớp. Giáo viên có thể
              đang chấm nháp trước khi công bố.
            </p>
          </aside>
        )}
      </div>
    </>
  );
}
function GradeEditor({ revisionId }: { revisionId: string }) {
  const refresh = useRefresh();
  const grade = useResource(["grade", revisionId], async (s) => {
    try {
      return await learningApi.grade(revisionId, s);
    } catch (e) {
      if (e instanceof ApiError && e.status === 404) return null;
      throw e;
    }
  });
  if (grade.isPending || grade.error)
    return (
      <QueryState
        pending={grade.isPending}
        error={grade.error}
        retry={grade.refetch}
      />
    );
  const g = grade.data;
  return (
    <section className="panel editor-panel">
      <h2>Chấm điểm</h2>
      <p className="muted">
        Lưu nháp trước khi công bố. Chỉ lần nộp mới nhất có thể được chấm.
      </p>
      <AsyncForm
        success="Đã lưu điểm nháp. Học sinh chưa thấy điểm mới này."
        onSubmit={async (f) => {
          await learningApi.saveGrade(revisionId, {
            score: Number(f.get("score")),
            feedback: text(f, "feedback"),
            expectedVersion: g?.version ?? 0,
          });
          await refresh();
        }}
      >
        <Field label="Điểm / 10">
          <input
            name="score"
            type="number"
            min="0"
            max="10"
            step="0.01"
            required
            defaultValue={g?.score}
          />
        </Field>
        <Field label="Nhận xét">
          <textarea
            name="feedback"
            rows={4}
            maxLength={10000}
            defaultValue={g?.feedback ?? ""}
          />
        </Field>
        <button className="button secondary">Lưu điểm nháp</button>
      </AsyncForm>
      {g ? (
        <>
          <hr />
          <Badge value={g.state} />
          <AsyncForm
            success="Điểm đã được công bố."
            onSubmit={async (f) => {
              await learningApi.publishGrade(g, text(f, "reason"));
              await refresh();
            }}
          >
            <Field
              label="Lý do điều chỉnh điểm đã công bố"
              hint="Bắt buộc khi thay một điểm đã công bố trước đó."
            >
              <textarea name="reason" rows={2} maxLength={500} />
            </Field>
            <button className="button" disabled={g.state === "published"}>
              Công bố điểm
            </button>
          </AsyncForm>
        </>
      ) : null}
    </section>
  );
}
