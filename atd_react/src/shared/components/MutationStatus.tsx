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
  const errors = current.filter((task) => task.state === "failed");
  const uniqueErrors = [...new Map(errors.map((task) => [task.message || task.label, task])).values()];
  if (!uniqueErrors.length) return null;
  return (
    <aside
      className="mutation-status"
      aria-label="Thông báo lỗi"
      aria-live="polite"
    >
      {uniqueErrors.map((task) => <p key={task.id} className="failed">{task.message || ("Không thể hoàn tất: " + task.label)}</p>)}
      <button
        className="button secondary small"
        onClick={() => {
          dismissMutationResults();
        }}
      >
        Đóng thông báo
      </button>
    </aside>
  );
}
