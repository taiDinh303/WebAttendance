import type { Session } from "./models";

/** datetime-local uses the device timezone, matching the single-session form. */
export function localDateTimeInput(instant: string): string {
  const date = new Date(instant);
  const pad = (value: number) => String(value).padStart(2, "0");
  const minute = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
  return date.getSeconds() ? `${minute}:${pad(date.getSeconds())}` : minute;
}

/** Display progress without changing the persisted scheduling/cancellation state. */
export function sessionDisplayStatus(
  session: Pick<Session, "status" | "startsAt" | "endsAt">,
  now: number,
): string {
  if (session.status !== "scheduled") return session.status;
  if (now >= Date.parse(session.endsAt)) return "ended";
  if (now >= Date.parse(session.startsAt)) return "ongoing";
  return "scheduled";
}
