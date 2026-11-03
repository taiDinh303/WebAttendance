import type { Session } from "./models";
export function localDay(date: Date): string {
  return [
    date.getFullYear(),
    String(date.getMonth() + 1).padStart(2, "0"),
    String(date.getDate()).padStart(2, "0"),
  ].join("-");
}
export function calendarRange(month: string, day: string | null = null) {
  const from = new Date((day || month + "-01") + "T00:00:00");
  const to = day
    ? new Date(from.getFullYear(), from.getMonth(), from.getDate() + 1)
    : new Date(from.getFullYear(), from.getMonth() + 1, 1);
  return { from: from.toISOString(), to: to.toISOString() };
}
export function reviewDays(
  sessions: Session[],
): Record<string, "approved" | "pending"> {
  const days: Record<string, "approved" | "pending"> = {};
  for (const session of sessions) {
    if (session.status === "cancelled") continue;
    const day = localDay(new Date(session.startsAt));
    const state =
      session.attendanceReviewState === "approved" ? "approved" : "pending";
    days[day] = days[day] === "pending" ? "pending" : state;
  }
  return days;
}
