import { request, params } from "./http";
import type {
  Assignment,
  PrivateFile,
  Page,
  Submission,
  Revision,
  Grade,
  Publication,
  GradeRow,
} from "../domain/models";
const base = (c: string) => "/classes/" + c + "/assignments";
export const learningApi = {
  list: (c: string, pageNumber: number, signal?: AbortSignal) =>
    request<Page<Assignment>>(base(c) + params({ pageNumber }), { signal }),
  get: (c: string, id: string, signal?: AbortSignal) =>
    request<{ assignment: Assignment; files: PrivateFile[] }>(
      base(c) + "/" + id,
      { signal },
    ),
  save: (c: string, body: unknown, id?: string) =>
    request<Assignment>(base(c) + (id ? "/" + id : ""), {
      method: id ? "PATCH" : "POST",
      body,
    }),
  transition: (c: string, a: Assignment, action: string) =>
    request<Assignment>(base(c) + "/" + a.id + "/" + action, {
      method: "POST",
      body: { expectedVersion: a.version },
    }),
  upload: (c: string, assignmentId: string, purpose: string, file: File) => {
    const body = new FormData();
    body.set("assignmentId", assignmentId);
    body.set("purpose", purpose);
    body.set("file", file);
    return request<PrivateFile>("/classes/" + c + "/files", {
      method: "POST",
      body,
    });
  },
  submit: (c: string, a: string, body: unknown, key: string) =>
    request<{ submission: Submission; revision: Revision }>(
      base(c) + "/" + a + "/submissions",
      { method: "POST", body, headers: { "Idempotency-Key": key } },
    ),
  submissions: (
    c: string,
    a: string,
    pageNumber: number,
    signal?: AbortSignal,
  ) =>
    request<Page<Submission>>(
      base(c) + "/" + a + "/submissions" + params({ pageNumber }),
      { signal },
    ),
  mine: (pageNumber: number, signal?: AbortSignal, assignmentId?: string) =>
    request<Page<Submission>>(
      "/me/submissions" + params({ pageNumber, assignmentId }),
      { signal },
    ),
  revisions: (id: string, pageNumber: number, signal?: AbortSignal) =>
    request<Page<Revision>>("/submissions/" + id + params({ pageNumber }), {
      signal,
    }),
  grade: (revision: string, signal?: AbortSignal) =>
    request<Grade>("/submission-revisions/" + revision + "/grade", { signal }),
  saveGrade: (revision: string, body: unknown) =>
    request<Grade>("/submission-revisions/" + revision + "/grade", {
      method: "PUT",
      body,
    }),
  publishGrade: (g: Grade, reason: string) =>
    request<Publication>("/grades/" + g.id + "/publish", {
      method: "POST",
      body: { expectedVersion: g.version, reason: reason || null },
    }),
  grades: (c: string, pageNumber: number, signal?: AbortSignal) =>
    request<Page<Publication>>(
      "/classes/" + c + "/grades" + params({ pageNumber }),
      { signal },
    ),
  gradebook: (c: string, pageNumber: number, signal?: AbortSignal) =>
    request<Page<GradeRow>>(
      "/classes/" + c + "/gradebook" + params({ pageNumber }),
      { signal },
    ),
  average: (c: string, signal?: AbortSignal) =>
    request<{ average: number | null; gradedCount: number }>(
      "/classes/" + c + "/grade-average",
      { signal },
    ),
};
