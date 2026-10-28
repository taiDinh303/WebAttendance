import { useState } from "react";
import { Link } from "react-router-dom";
import { Search, ShieldCheck } from "lucide-react";
import { usePage, useRefresh, useResource } from "../../../application/queries";
import { adminApi } from "../../../infrastructure/adminApi";
import type { AdminUser } from "../../../domain/models";
import { roleLabel } from "../../../domain/access";
import { text } from "../../../domain/format";
import {
  AsyncForm,
  Badge,
  Empty,
  Field,
  PageHeader,
  Pagination,
  QueryState,
} from "../../../shared/ui";

export default function AdminUsersPage() {
  const { page, search, setSearch, setPage } = usePage();
  const q = search.get("q") ?? "",
    role = search.get("role") ?? "",
    status = search.get("status") ?? "";
  const [selected, setSelected] = useState<AdminUser | null>(null);
  const refresh = useRefresh();
  const query = useResource(["admin-users", page, q, role, status], (signal) =>
    adminApi.users({ pageNumber: page, q, role, status }, signal),
  );
  const filter = (values: Record<string, string>) =>
    setSearch({ q, role, status, ...values, page: "1" }, { replace: true });
  return (
    <>
      <PageHeader
        eyebrow="QUẢN TRỊ / TÀI KHOẢN"
        title="Quản lý tài khoản"
        description="Tìm theo tên hoặc email; lọc vai trò và trạng thái. Quyền đọc nội dung lớp được giữ riêng."
      />
      <div className="filter-bar admin-filters">
        <label className="search-box">
          <Search size={18} />
          <input
            aria-label="Tìm tài khoản"
            type="search"
            maxLength={200}
            placeholder="Tên hoặc email…"
            value={q}
            onChange={(event) => filter({ q: event.target.value })}
          />
        </label>
        <select
          aria-label="Vai trò tài khoản"
          value={role}
          onChange={(event) => filter({ role: event.target.value })}
        >
          <option value="">Tất cả vai trò</option>
          <option value="admin">Quản trị viên</option>
          <option value="teacher">Giáo viên</option>
          <option value="student">Học sinh</option>
        </select>
        <select
          aria-label="Trạng thái tài khoản"
          value={status}
          onChange={(event) => filter({ status: event.target.value })}
        >
          <option value="">Tất cả trạng thái</option>
          <option value="active">Đang hoạt động</option>
          <option value="locked">Đã khóa</option>
        </select>
        <span className="muted">{query.data?.totalItems ?? "—"} tài khoản</span>
      </div>
      {selected ? (
        <section
          className="panel editor-panel admin-moderation"
          aria-label="Thay đổi trạng thái tài khoản"
        >
          <h2>
            {selected.accountStatus === "active" ? "Khóa" : "Mở khóa"} ·{" "}
            {selected.displayName}
          </h2>
          <p className="muted">
            {selected.email} · Phiên bản {selected.version}. Phiên đăng nhập cũ
            sẽ bị thu hồi.
          </p>
          <AsyncForm
            key={selected.id + ":" + selected.version}
            onSubmit={async (form) => {
              await adminApi.changeStatus(
                selected,
                selected.accountStatus === "active" ? "lock" : "unlock",
                text(form, "reason"),
              );
              setSelected(null);
              await refresh();
            }}
          >
            <Field label="Lý do thay đổi trạng thái">
              <textarea
                name="reason"
                required
                minLength={3}
                maxLength={500}
                rows={3}
                placeholder="Nêu rõ lý do để lưu trong nhật ký quản trị."
              />
            </Field>
            <div className="inline-actions">
              <button
                className={
                  "button " +
                  (selected.accountStatus === "active" ? "danger" : "")
                }
              >
                Xác nhận{" "}
                {selected.accountStatus === "active" ? "khóa" : "mở khóa"}
              </button>
              <button
                type="button"
                className="button secondary"
                onClick={() => setSelected(null)}
              >
                Hủy thao tác
              </button>
            </div>
          </AsyncForm>
        </section>
      ) : null}
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      <div className="panel admin-user-list">
        {query.data?.items.map((user) => (
          <article
            key={user.id}
            className="admin-user-row"
            aria-label={"Tài khoản " + user.email}
          >
            <div className="admin-user-identity">
              <strong>{user.displayName}</strong>
              <p>{user.email}</p>
            </div>
            <span className="admin-role">{roleLabel(user.role)}</span>
            <Badge value={user.accountStatus} />
            <small className="muted">
              {user.emailVerified ? "Đã xác minh email" : "Chưa xác minh email"}
            </small>
            <div className="inline-actions">
              {user.isSelf ? (
                <small className="muted">
                  <ShieldCheck size={15} /> Tài khoản của bạn
                </small>
              ) : (
                <button
                  type="button"
                  className="button secondary small"
                  onClick={() => setSelected(user)}
                >
                  {user.accountStatus === "active"
                    ? "Khóa tài khoản"
                    : "Mở khóa tài khoản"}
                </button>
              )}
              <Link
                className="text-link"
                to={"/admin/audit?subjectId=" + user.id}
              >
                Nhật ký
              </Link>
            </div>
          </article>
        ))}
        {query.isSuccess && !query.data.items.length ? (
          <Empty
            title="Không tìm thấy tài khoản"
            description="Thử đổi bộ lọc hoặc từ khóa tìm kiếm."
          />
        ) : null}
      </div>
      <Pagination data={query.data} onPage={setPage} />
    </>
  );
}
