import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Plus, FileText, ArrowUpRight } from "lucide-react";
import { useSession } from "../../../application/sessionContext";
import { usePage, useRefresh, useResource } from "../../../application/queries";
import { learningApi } from "../../../infrastructure/learningApi";
import {
  AsyncForm,
  Field,
  QueryState,
  Badge,
  Empty,
  Pagination,
} from "../../../shared/ui";
import { text, iso, dateTime } from "../../../domain/format";
export function AssignmentsPanel({ classId }: { classId: string }) {
  const { user } = useSession();
  const { page, setPage } = usePage();
  const [open, setOpen] = useState(false);
  const navigate = useNavigate();
  const refresh = useRefresh();
  const query = useResource(["assignments", classId, page], (s) =>
    learningApi.list(classId, page, s),
  );
  return (
    <>
      <div className="section-heading">
        <h2>Bài tập của lớp</h2>
        {user?.role === "teacher" ? (
          <button className="button small" onClick={() => setOpen(!open)}>
            <Plus size={16} />
            Giao bài tập
          </button>
        ) : null}
      </div>
      {open ? (
        <div className="panel editor-panel">
          <AsyncForm
            onSubmit={async (f) => {
              const a = await learningApi.save(classId, {
                title: text(f, "title"),
                instructions: text(f, "instructions"),
                dueAt: iso(f.get("dueAt")),
                allowLate: f.has("allowLate"),
                allowResubmit: f.has("allowResubmit"),
              });
              await refresh();
              navigate("/app/classes/" + classId + "/assignments/" + a.id);
            }}
          >
            <Field label="Tiêu đề bài tập">
              <input name="title" required maxLength={200} />
            </Field>
            <Field label="Hướng dẫn">
              <textarea name="instructions" rows={4} maxLength={20000} />
            </Field>
            <Field label="Hạn nộp (giờ máy của bạn)">
              <input name="dueAt" type="datetime-local" />
            </Field>
            <label className="check">
              <input type="checkbox" name="allowLate" />
              Cho phép nộp muộn
            </label>
            <label className="check">
              <input type="checkbox" name="allowResubmit" defaultChecked />
              Cho phép nộp lại
            </label>
            <button className="button">Tạo bản nháp</button>
          </AsyncForm>
        </div>
      ) : null}
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      <div className="panel">
        {query.data?.items.map((a) => (
          <Link
            className="list-row assignment-row"
            key={a.id}
            to={"/app/classes/" + classId + "/assignments/" + a.id}
          >
            <span className="stat-icon blue">
              <FileText />
            </span>
            <div className="grow">
              <h3>{a.title}</h3>
              <p>Hạn nộp: {dateTime(a.dueAt)}</p>
            </div>
            <Badge value={a.state} />
            <ArrowUpRight size={18} />
          </Link>
        ))}
        {query.isSuccess && !query.data.items.length ? (
          <Empty
            title="Chưa có bài tập"
            description="Bài tập đã công bố sẽ xuất hiện tại đây."
          />
        ) : null}
      </div>
      <Pagination data={query.data} onPage={setPage} />
    </>
  );
}
