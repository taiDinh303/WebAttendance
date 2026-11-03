import { useResource } from "../../../application/queries";
import { classApi } from "../../../infrastructure/classApi";
import { calendarRange, reviewDays } from "../../../domain/calendar";
import type { Session } from "../../../domain/models";
import { QueryState } from "../../../shared/ui";

export function MonthCalendar({
  classId,
  month,
  day,
  onMonth,
  onDay,
  disabled = false,
}: {
  classId?: string;
  month: string;
  day: string | null;
  onMonth: (value: string) => void;
  onDay: (value: string | null) => void;
  disabled?: boolean;
}) {
  const query = useResource(
    ["month-review", classId, month],
    async (signal) => {
      const sessions: Session[] = [];
      for (let pageNumber = 1; ; pageNumber++) {
        const result = await classApi.sessions(
          {
            ...calendarRange(month),
            startWithin: 1,
            status: "scheduled",
            pageSize: 100,
            pageNumber,
            ...(classId ? { classId } : {}),
          },
          signal,
        );
        sessions.push(...result.items);
        if (!result.hasNextPage) return sessions;
      }
    },
    { refetchInterval: 30000 },
  );
  const states = reviewDays(query.data ?? []);
  const date = new Date(month + "-01T00:00:00");
  const count = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
  const offset = (date.getDay() + 6) % 7;
  const move = (delta: number) => {
    const next = new Date(date.getFullYear(), date.getMonth() + delta, 1);
    onMonth(
      next.getFullYear() + "-" + String(next.getMonth() + 1).padStart(2, "0"),
    );
  };
  return (
    <section className="panel month-calendar" aria-label="Lọc theo ngày học">
      <header className="month-calendar-header">
        <button
          type="button"
          className="button secondary small"
          aria-label="Tháng trước"
          disabled={disabled || date.getFullYear() < 2}
          onClick={() => move(-1)}
        >
          ‹
        </button>
        <strong>
          {date.toLocaleDateString("vi-VN", { month: "long", year: "numeric" })}
        </strong>
        <button
          type="button"
          className="button secondary small"
          aria-label="Tháng sau"
          disabled={disabled}
          onClick={() => move(1)}
        >
          ›
        </button>
      </header>
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      <div className="month-calendar-grid">
        {["T2", "T3", "T4", "T5", "T6", "T7", "CN"].map((label) => (
          <span className="month-weekday" key={label}>
            {label}
          </span>
        ))}
        {Array.from({ length: offset }, (_, index) => (
          <span key={"blank" + index} />
        ))}
        {Array.from({ length: count }, (_, index) => {
          const value = month + "-" + String(index + 1).padStart(2, "0");
          const state = states[value];
          return (
            <button
              type="button"
              key={value}
              disabled={disabled || !state || query.isPending || !!query.error}
              aria-pressed={day === value}
              aria-label={
                value +
                (state
                  ? state === "approved"
                    ? " · Đã duyệt"
                    : " · Chưa duyệt"
                  : " · Không có lịch")
              }
              className={"month-day " + (state || "no-session")}
              onClick={() => onDay(value)}
            >
              {index + 1}
            </button>
          );
        })}
      </div>
      <footer className="month-calendar-footer">
        <div className="month-legend">
          <span className="approved">Đã duyệt</span>
          <span className="pending">Chưa duyệt</span>
          <span>Không có lịch</span>
        </div>
        <button
          type="button"
          className="button secondary small"
          disabled={disabled || !day}
          onClick={() => onDay(null)}
        >
          Cả tháng
        </button>
      </footer>
    </section>
  );
}
