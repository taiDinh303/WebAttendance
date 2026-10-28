import { request, params } from "./http";
import type {
  Classroom,
  Membership,
  Page,
  Session,
  Notice,
  CatalogItem,
  Dashboard,
} from "../domain/models";
export const classApi = {
  dashboard: (signal?: AbortSignal) =>
    request<Dashboard>("/dashboard", { signal }),
  list: (filter: Record<string, string | number> = {}, signal?: AbortSignal) =>
    request<Page<Classroom>>("/classes" + params(filter), { signal }),
  get: (id: string, signal?: AbortSignal) =>
    request<Classroom>("/classes/" + id, { signal }),
  create: (body: unknown) =>
    request<{ class: Classroom; inviteCode: string }>("/classes", {
      method: "POST",
      body,
    }),
  update: (id: string, body: unknown) =>
    request<Classroom>("/classes/" + id, { method: "PATCH", body }),
  action: (id: string, action: string, body: unknown) =>
    request<{ inviteCode?: string }>("/classes/" + id + "/" + action, {
      method: "POST",
      body,
    }),
  join: (inviteCode: string) =>
    request<Membership>("/join-requests", {
      method: "POST",
      body: { inviteCode },
    }),
  myRequests: (pageNumber: number, signal?: AbortSignal) =>
    request<Page<Membership>>("/me/join-requests" + params({ pageNumber }), {
      signal,
    }),
  members: (
    id: string,
    kind: "members" | "join-requests",
    pageNumber: number,
    signal?: AbortSignal,
  ) =>
    request<Page<Membership>>(
      "/classes/" + id + "/" + kind + params({ pageNumber }),
      { signal },
    ),
  membership: (id: string, member: Membership, action: string) =>
    request<Membership>(
      "/classes/" +
        id +
        (action === "remove"
          ? "/members/" + member.studentId + "/remove"
          : "/join-requests/" + member.id + "/" + action),
      { method: "POST", body: { expectedVersion: member.version } },
    ),
  cancel: (member: Membership) =>
    request("/join-requests/" + member.id + "/cancel", {
      method: "POST",
      body: { expectedVersion: member.version },
    }),
  leave: (id: string, version: number) =>
    request("/classes/" + id + "/leave", {
      method: "POST",
      body: { expectedVersion: version },
    }),
  sessions: (filter: Record<string, string | number>, signal?: AbortSignal) =>
    request<Page<Session>>("/calendar" + params(filter), { signal }),
  saveSession: (id: string, body: unknown, sessionId?: string) =>
    request<Session>(
      "/classes/" + id + "/sessions" + (sessionId ? "/" + sessionId : ""),
      { method: sessionId ? "PATCH" : "POST", body },
    ),
  series: (id: string, body: unknown, preview: boolean) =>
    request<{ count?: number; sessionCount?: number }>(
      "/classes/" + id + "/session-series" + (preview ? "/preview" : ""),
      { method: "POST", body },
    ),
  cancelSession: (id: string, s: Session, reason: string) =>
    request("/classes/" + id + "/sessions/" + s.id + "/cancel", {
      method: "POST",
      body: { expectedVersion: s.version, reason },
    }),
  notices: (pageNumber: number, signal?: AbortSignal) =>
    request<Page<Notice>>("/notifications" + params({ pageNumber }), {
      signal,
    }),
  unread: (signal?: AbortSignal) =>
    request<{ unreadCount: number }>("/notifications/unread-count", { signal }),
  readNotice: (id: string) =>
    request("/notifications/" + id + "/read", { method: "PUT" }),
  catalog: (kind: string, pageNumber: number, signal?: AbortSignal) =>
    request<Page<CatalogItem>>("/" + kind + params({ pageNumber }), { signal }),
  catalogItem: (kind: string, id: string, signal?: AbortSignal) =>
    request<CatalogItem>("/" + kind + "/" + id, { signal }),
  saveCatalog: (kind: string, body: unknown, id?: string) =>
    request("/" + kind + (id ? "/" + id : ""), {
      method: id ? "PUT" : "POST",
      body,
    }),
  deleteCatalog: (kind: string, item: CatalogItem) =>
    request("/" + kind + "/" + item.id, {
      method: "DELETE",
      body: { expectedVersion: item.version },
    }),
};
