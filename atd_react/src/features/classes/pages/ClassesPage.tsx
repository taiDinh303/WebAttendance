import { useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { Plus, Search, ArrowRight } from "lucide-react";
import { useSession } from "../../../application/sessionContext";
import { usePage, useRefresh, useResource } from "../../../application/queries";
import { classApi } from "../../../infrastructure/classApi";
import { ClassCard } from "../components/ClassCard";
import { CatalogFields } from "../components/CatalogFields";
import {
  AsyncForm,
  Field,
  PageHeader,
  Pagination,
  QueryState,
  Empty,
} from "../../../shared/ui";
import { text } from "../../../domain/format";
export default function ClassesPage() {
  const { user } = useSession();
  const [params] = useSearchParams();
  const { page, search, setSearch, setPage } = usePage();
  const [open, setOpen] = useState(() => params.get("create") === "1");
  const navigate = useNavigate();
  const refresh = useRefresh();
  const q = search.get("q") ?? "",
    status = search.get("status") ?? "active";
  const classes = useResource(["classes", page, q, status], (s) =>
    classApi.list({ pageNumber: page, q, status }, s),
  );
  return (
    <>
      <PageHeader
        eyebrow="KẾT NỐI & HỌC TẬP"
        title="Lớp học của bạn"
        description="Cùng nhau tạo nên những giờ học ý nghĩa."
        action={
          <button className="button" onClick={() => setOpen(!open)}>
            <Plus size={18} />
            {user?.role === "teacher" ? "Tạo lớp học" : "Tham gia lớp"}
          </button>
        }
      />
      {open ? (
        <section className="panel editor-panel">
          <h2>
            {user?.role === "teacher"
              ? "Tạo không gian học tập mới"
              : "Tham gia bằng mã lớp"}
          </h2>
          <AsyncForm
            onSubmit={async (f) => {
              if (user?.role === "teacher") {
                const result = await classApi.create({
                  name: text(f, "name"),
                  description: text(f, "description") || null,
                  timezone: "Asia/Ho_Chi_Minh",
                  courseId: text(f, "courseId") || null,
                  semesterId: text(f, "semesterId") || null,
                });
                await refresh();
                navigate("/app/classes/" + result.class.id, {
                  state: { inviteCode: result.inviteCode },
                });
              } else {
                await classApi.join(text(f, "inviteCode"));
                await refresh();
                navigate("/app/join-requests");
              }
            }}
          >
            {user?.role === "teacher" ? (
              <>
                <Field label="Tên lớp">
                  <input
                    name="name"
                    required
                    maxLength={160}
                    placeholder="Ví dụ: Toán 10 · Năm học 2026"
                  />
                </Field>
                <Field label="Mô tả">
                  <textarea
                    name="description"
                    rows={3}
                    maxLength={5000}
                    placeholder="Giới thiệu ngắn về lớp học…"
                  />
                </Field>
                <CatalogFields />
              </>
            ) : (
              <Field
                label="Mã lớp từ giáo viên"
                hint="Yêu cầu cần được giáo viên phê duyệt trước khi vào lớp."
              >
                <input
                  name="inviteCode"
                  required
                  maxLength={32}
                  autoComplete="off"
                  placeholder="Nhập mã lớp"
                />
              </Field>
            )}
            <button className="button">
              {user?.role === "teacher" ? "Tạo lớp" : "Gửi yêu cầu"}
              <ArrowRight size={16} />
            </button>
          </AsyncForm>
        </section>
      ) : null}
      <div className="filter-bar">
        <label className="search-box">
          <Search size={18} />
          <input
            aria-label="Tìm lớp học"
            type="search"
            placeholder="Tìm theo tên lớp…"
            value={q}
            onChange={(e) =>
              setSearch(
                { q: e.target.value, status, page: "1" },
                { replace: true },
              )
            }
          />
        </label>
        <select
          aria-label="Trạng thái lớp"
          value={status}
          onChange={(e) => setSearch({ q, status: e.target.value, page: "1" })}
        >
          <option value="active">Đang hoạt động</option>
          <option value="archived">Đã lưu trữ</option>
        </select>
        <span className="muted">{classes.data?.totalItems ?? "—"} lớp học</span>
      </div>
      <QueryState
        pending={classes.isPending}
        error={classes.error}
        retry={classes.refetch}
      />
      {classes.data?.items.length ? (
        <div className="class-grid">
          {classes.data.items.map((c, i) => (
            <ClassCard key={c.id} classroom={c} index={i} />
          ))}
        </div>
      ) : classes.isSuccess ? (
        <div className="panel">
          <Empty
            title={q ? "Chưa tìm thấy lớp phù hợp" : "Chưa có lớp học"}
            description={
              q
                ? "Thử tìm bằng từ khóa khác."
                : "Tạo lớp mới hoặc gửi yêu cầu tham gia bằng mã lớp."
            }
          />
        </div>
      ) : null}
      <Pagination data={classes.data} onPage={setPage} />
    </>
  );
}
