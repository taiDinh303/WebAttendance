import {
  useState,
  type ReactNode,
  type FormEvent,
  type ButtonHTMLAttributes,
} from "react";
import {
  ArrowRight,
  BookOpen,
  LoaderCircle,
  RefreshCw,
  TriangleAlert,
} from "lucide-react";
import { ApiError } from "../../domain/errors";
import { statusLabel } from "../../domain/format";
import type { Page } from "../../domain/models";
export function ErrorNotice({ error }: { error: unknown }) {
  if (!error) return null;
  const e =
    error instanceof Error ? error : new Error("Thao tác chưa hoàn tất.");
  return (
    <div className="error" role="alert">
      <TriangleAlert size={18} />
      <div>
        {e.message}
        {e instanceof ApiError ? (
          <>
            <ul>
              {Object.entries(e.fields).map(([field, values]) => (
                <li key={field}>
                  {field}: {values.join(" ")}
                </li>
              ))}
            </ul>
            {e.traceId ? <small>Mã hỗ trợ: {e.traceId}</small> : null}
          </>
        ) : null}
      </div>
    </div>
  );
}
export function Loading() {
  return (
    <div className="loading" role="status">
      <LoaderCircle size={22} className="spin" />
      Đang tải dữ liệu…
    </div>
  );
}
export function Empty({
  title = "Chưa có dữ liệu",
  description = "Nội dung mới sẽ xuất hiện tại đây.",
  children,
}: {
  title?: string;
  description?: string;
  children?: ReactNode;
}) {
  return (
    <div className="empty">
      <span className="empty-icon">
        <BookOpen size={30} />
      </span>
      <h3>{title}</h3>
      <p>{description}</p>
      {children}
    </div>
  );
}
export function Badge({ value }: { value: string }) {
  return (
    <span className={"badge badge-" + value}>
      {statusLabel[value] ?? value}
    </span>
  );
}
export function PageHeader({
  eyebrow,
  title,
  description,
  action,
}: {
  eyebrow?: string;
  title: string;
  description?: string;
  action?: ReactNode;
}) {
  return (
    <header className="page-heading">
      <div>
        {eyebrow ? <p className="eyebrow">{eyebrow}</p> : null}
        <h1>{title}</h1>
        {description ? <p className="muted">{description}</p> : null}
      </div>
      {action}
    </header>
  );
}
export function AsyncForm({
  onSubmit,
  children,
  className = "",
  success,
}: {
  onSubmit: (data: FormData) => Promise<unknown>;
  children: ReactNode;
  className?: string;
  success?: string;
}) {
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>(null);
  const [done, setDone] = useState(false);
  async function submit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    if (busy) return;
    const data = new FormData(
      e.currentTarget,
      (e.nativeEvent as SubmitEvent).submitter,
    );
    setBusy(true);
    setError(null);
    setDone(false);
    try {
      await onSubmit(data);
      setDone(true);
    } catch (error) {
      setError(error);
    } finally {
      setBusy(false);
    }
  }
  return (
    <form onSubmit={submit} className={"form " + className}>
      <fieldset disabled={busy} aria-busy={busy}>
        {children}
      </fieldset>
      {busy ? (
        <p className="muted" role="status">
          Đang xử lý…
        </p>
      ) : null}
      <ErrorNotice error={error} />
      {done && success ? (
        <p className="success" role="status">
          {success}
        </p>
      ) : null}
    </form>
  );
}
export function ActionButton({
  action,
  children,
  ...props
}: {
  action: () => Promise<unknown>;
  children: ReactNode;
} & ButtonHTMLAttributes<HTMLButtonElement>) {
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<unknown>();
  return (
    <span className="action-wrap">
      <button
        {...props}
        type="button"
        disabled={busy || props.disabled}
        onClick={async () => {
          setBusy(true);
          setError(null);
          try {
            await action();
          } catch (e) {
            setError(e);
          } finally {
            setBusy(false);
          }
        }}
      >
        {busy ? <LoaderCircle className="spin" size={16} /> : null}
        {children}
      </button>
      <ErrorNotice error={error} />
    </span>
  );
}
export function Field({
  label,
  children,
  hint,
}: {
  label: string;
  children: ReactNode;
  hint?: string;
}) {
  return (
    <label className="field">
      <span>{label}</span>
      {children}
      {hint ? <small>{hint}</small> : null}
    </label>
  );
}
export function Pagination({
  data,
  onPage,
}: {
  data?: Pick<
    Page<unknown>,
    | "pageNumber"
    | "totalPages"
    | "totalItems"
    | "hasPreviousPage"
    | "hasNextPage"
  >;
  onPage: (page: number) => void;
}) {
  if (!data) return null;
  return (
    <nav className="pagination" aria-label="Phân trang">
      <span>
        {data.totalItems} kết quả · Trang {data.pageNumber}/
        {Math.max(1, data.totalPages)}
      </span>
      <div>
        <button
          className="button secondary small"
          disabled={!data.hasPreviousPage}
          onClick={() => onPage(data.pageNumber - 1)}
        >
          Trước
        </button>
        <button
          className="button secondary small"
          disabled={!data.hasNextPage}
          onClick={() => onPage(data.pageNumber + 1)}
        >
          Sau <ArrowRight size={14} />
        </button>
      </div>
    </nav>
  );
}
export function QueryState({
  pending,
  error,
  retry,
}: {
  pending: boolean;
  error: unknown;
  retry: () => unknown;
}) {
  if (pending) return <Loading />;
  if (error)
    return (
      <>
        <ErrorNotice error={error} />
        <button className="button secondary" onClick={() => retry()}>
          <RefreshCw size={15} />
          Thử tải lại
        </button>
      </>
    );
  return null;
}
