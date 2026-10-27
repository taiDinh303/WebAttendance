import { request, params } from "./http";
import type { Window, Qr, Page, RosterRow, Attendance } from "../domain/models";
export const attendanceApi = {
  window: (c: string, s: string, signal?: AbortSignal) =>
    request<Window>("/classes/" + c + "/sessions/" + s + "/attendance-window", {
      signal,
    }),
  open: (c: string, s: string, body: unknown) =>
    request<Window>(
      "/classes/" + c + "/sessions/" + s + "/attendance-windows",
      { method: "POST", body },
    ),
  qr: (w: string, signal?: AbortSignal) =>
    request<Qr>("/attendance-windows/" + w + "/qr", { signal }),
  roster: (w: string, pageNumber: number, signal?: AbortSignal) =>
    request<Page<RosterRow>>(
      "/attendance-windows/" + w + "/roster" + params({ pageNumber }),
      { signal },
    ),
  close: (w: Window) =>
    request("/attendance-windows/" + w.id + "/close", {
      method: "POST",
      body: { expectedVersion: w.version },
    }),
  correct: (
    c: string,
    s: string,
    row: RosterRow,
    status: string,
    reason: string,
  ) =>
    request(
      "/classes/" + c + "/sessions/" + s + "/attendance/" + row.studentId,
      {
        method: "PUT",
        body: { status, reason, expectedVersion: row.recordVersion },
      },
    ),
  checkIn: (qrToken: string) =>
    request<Attendance>("/attendance/check-ins", {
      method: "POST",
      body: { qrToken },
    }),
  records: (c: string, pageNumber: number, signal?: AbortSignal) =>
    request<Page<Attendance>>(
      "/classes/" + c + "/attendance" + params({ pageNumber }),
      { signal },
    ),
  mine: (c: string, pageNumber: number, signal?: AbortSignal) =>
    request<Page<Attendance>>(
      "/me/attendance" + params({ classId: c, pageNumber }),
      { signal },
    ),
};
