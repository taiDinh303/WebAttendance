import { useSyncExternalStore } from "react";
import { listeners, mutationSnapshot, dismissMutationResults } from "../../domain/mutationTasks";
export function MutationStatus() {
  const current = useSyncExternalStore(
    (f) => {
      listeners.add(f);
      return () => {
        listeners.delete(f);
      };
    },
    mutationSnapshot,
  );
  if (!current.length) return null;
  return (
    <aside
      className="mutation-status"
      aria-label="Tiến trình thao tác"
      aria-live="polite"
    >
      {current.map((t) => (
        <p key={t.id} className={t.state === "failed" ? "failed" : ""}>
          {t.state === "pending"
            ? "Đang xử lý"
            : t.state === "done"
              ? "Hoàn tất"
              : "Chưa hoàn tất"}{" "}
          · {t.label}
          {t.message ? ": " + t.message : ""}
        </p>
      ))}
      <button
        className="button secondary small"
        onClick={() => {
          dismissMutationResults();
        }}
      >
        Ẩn kết quả
      </button>
    </aside>
  );
}
