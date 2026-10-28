/** datetime-local uses the device timezone, matching the single-session form. */
export function localDateTimeInput(instant: string): string {
  const date = new Date(instant);
  const pad = (value: number) => String(value).padStart(2, "0");
  const minute = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
  return date.getSeconds() ? `${minute}:${pad(date.getSeconds())}` : minute;
}
