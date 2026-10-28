import { params, request } from "./http";
import type {
  AdminAccountEvent,
  AdminSummary,
  AdminUser,
  Page,
} from "../domain/models";

export const adminApi = {
  summary: (signal?: AbortSignal) =>
    request<AdminSummary>("/admin/summary", { signal }),
  users: (filters: Record<string, string | number>, signal?: AbortSignal) =>
    request<Page<AdminUser>>("/admin/users" + params(filters), { signal }),
  changeStatus: (
    user: AdminUser,
    operation: "lock" | "unlock",
    reason: string,
  ) =>
    request<AdminUser>("/admin/users/" + user.id + "/" + operation, {
      method: "POST",
      body: { reason, expectedVersion: user.version },
    }),
  events: (filters: Record<string, string | number>, signal?: AbortSignal) =>
    request<Page<AdminAccountEvent>>("/admin/audit" + params(filters), {
      signal,
    }),
};
