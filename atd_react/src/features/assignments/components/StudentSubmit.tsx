import { useState } from "react";
import { Link } from "react-router-dom";
import { Upload, CheckCircle2 } from "lucide-react";
import { SubmissionFlow } from "../../../application/submissionFlow";
import { learningApi } from "../../../infrastructure/learningApi";
import { useRefresh, useResource } from "../../../application/queries";
import type { Assignment, Submission } from "../../../domain/models";
import { AsyncForm, Field, QueryState } from "../../../shared/ui";
import { text } from "../../../domain/format";
export function StudentSubmit({
  classId,
  assignment,
}: {
  classId: string;
  assignment: Assignment;
}) {
  const [files, setFiles] = useState<File[]>([]);
  const [step, setStep] = useState("");
  const [locked, setLocked] = useState(false);
  const [receipt, setReceipt] = useState<Submission | null>(null);
  const [flow] = useState(() => new SubmissionFlow(classId, assignment.id));
  const refresh = useRefresh();
  const previous = useResource(["submissions", assignment.id, 1], (s) =>
    learningApi.mine(1, s, assignment.id),
  );
  if (receipt)
    return (
      <div className="panel editor-panel success">
        <CheckCircle2 />
        <h2>Đã nộp bài thành công</h2>
        <p>Máy chủ đã xác nhận và lưu lịch sử bài nộp.</p>
        <Link
          className="button secondary"
          to={"/app/submissions/" + receipt.id}
        >
          Xem bài đã nộp →
        </Link>
      </div>
    );
  if (previous.isPending || previous.error)
    return (
      <QueryState
        pending={previous.isPending}
        error={previous.error}
        retry={previous.refetch}
      />
    );
  const old = previous.data?.items[0];
  if (old && !assignment.allowResubmit)
    return (
      <div className="panel editor-panel">
        <h2>Bạn đã nộp bài này</h2>
        <p>Giáo viên không bật nộp lại.</p>
        <Link className="button secondary" to={"/app/submissions/" + old.id}>
          Xem lịch sử bài nộp
        </Link>
      </div>
    );
  if (assignment.state !== "published")
    return <p className="info">Bài tập đã đóng, không nhận bài nộp mới.</p>;
  return (
    <div className="panel editor-panel">
      <h2>{old ? "Nộp lại bài của bạn" : "Nộp bài của bạn"}</h2>
      <p className="muted">
        Tối đa 5 file, mỗi file 20 MiB. PDF, DOCX, XLSX, PPTX, JPG, PNG hoặc
        TXT.
      </p>
      <AsyncForm
        onSubmit={async (form) => {
          try {
            const result = await flow.send(
              files,
              text(form, "note"),
              (stage, current, total) =>
                setStep(
                  stage === "upload"
                    ? "Đang tải và kiểm tra file " + current + "/" + total + "…"
                    : "Đang chờ máy chủ xác nhận bài nộp…",
                ),
            );
            setReceipt(result.submission);
            await refresh();
            setStep("");
          } catch (error) {
            setStep(
              flow.locked
                ? "Chưa nhận được xác nhận. Có thể thử lại cùng lần nộp."
                : "Lần gửi chưa hoàn tất. Kiểm tra lỗi bên dưới.",
            );
            throw error;
          } finally {
            setLocked(flow.locked);
          }
        }}
      >
        <Field label="File bài làm">
          <input
            aria-label="File bài làm"
            type="file"
            multiple
            disabled={locked}
            accept=".pdf,.docx,.xlsx,.pptx,.jpg,.jpeg,.png,.txt"
            onChange={(e) => {
              setFiles(Array.from(e.target.files ?? []));
              flow.resetFiles();
            }}
          />
        </Field>
        <ul className="file-selection">
          {files.map((file, i) => (
            <li key={i}>
              {file.name} · {(file.size / 1024 / 1024).toFixed(2)} MiB
            </li>
          ))}
        </ul>
        <Field label="Ghi chú cho giáo viên">
          <textarea name="note" maxLength={2000} rows={3} disabled={locked} />
        </Field>
        {locked ? (
          <p className="info">
            Nếu chưa nhận xác nhận, bấm lại để kiểm tra cùng lần nộp. Nội dung
            được khóa để tránh nộp trùng. Tải lại trang thì kiểm tra lịch sử
            trước khi gửi bài mới.
          </p>
        ) : null}
        <p className="muted" role="status">
          {step}
        </p>
        <button className="button">
          <Upload size={17} />
          {locked ? "Xác nhận lại lần nộp" : "Nộp bài"}
        </button>
      </AsyncForm>
    </div>
  );
}
