type Task = {
  id: number;
  label: string;
  state: "pending" | "done" | "failed";
  message?: string;
};
let tasks: Task[] = [];
let next = 0;
export const listeners = new Set<() => void>();
export const emit = () => listeners.forEach((f) => f());
export async function trackMutation<T>(
  label: string,
  work: () => Promise<T>,
): Promise<T> {
  const id = ++next;
  tasks = [
    ...tasks.filter((t) => t.state === "pending"),
    ...tasks.filter((t) => t.state !== "pending").slice(-3),
    { id, label, state: "pending" },
  ];
  emit();
  try {
    const result = await work();
    tasks = tasks.map((t) => (t.id === id ? { ...t, state: "done" } : t));
    emit();
    return result;
  } catch (error) {
    tasks = tasks.map((t) =>
      t.id === id
        ? {
            ...t,
            state: "failed",
            message:
              error instanceof Error ? error.message : "Thao tác thất bại",
          }
        : t,
    );
    emit();
    throw error;
  }
}

export const mutationSnapshot=()=>tasks;
export function dismissMutationResults(){tasks=tasks.filter(t=>t.state==="pending");emit();}
