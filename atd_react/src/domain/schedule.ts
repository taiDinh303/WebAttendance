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

export function parseScheduleDate(value:string):string {
 const input=value.trim();const match=/^(\d{2})\/(\d{2})\/(\d{4})$/.exec(input);
 const normalized=match?match[3]+"-"+match[2]+"-"+match[1]:input;
 if(!/^\d{4}-\d{2}-\d{2}$/.test(normalized))throw Error("Nhập ngày theo dạng dd/mm/yyyy.");
 const date=new Date(normalized+"T00:00:00Z");if(!Number.isFinite(date.getTime())||date.toISOString().slice(0,10)!==normalized)throw Error("Ngày không hợp lệ.");return normalized;
}
export function parseScheduleTime(value:string):string {const input=value.trim();if(!/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/.test(input))throw Error("Nhập giờ từ 00:00 đến 23:59.");return input;}
export function parseScheduleDateTime(value:string):string {const parts=value.trim().split(/[T ]+/);if(parts.length!==2)throw Error("Nhập ngày và giờ, ví dụ 03/11/2026 08:30.");return new Date(parseScheduleDate(parts[0])+"T"+parseScheduleTime(parts[1])).toISOString();}

/** One session uses one local calendar day and a strictly increasing time range. */
export function singleSessionTimes(day: string, start: string, end: string) {
  const date = parseScheduleDate(day);
  const startTime = parseScheduleTime(start);
  const endTime = parseScheduleTime(end);
  if (endTime <= startTime) throw Error("Giờ kết thúc phải sau giờ bắt đầu trong cùng ngày. Hãy kiểm tra lại hai ô giờ.");
  return { startsAt: parseScheduleDateTime(date + "T" + startTime), endsAt: parseScheduleDateTime(date + "T" + endTime) };
}
