import { request } from "./http";
import type { User } from "../domain/models";
export const authApi = {
  me: (signal?: AbortSignal) => request<User>("/me", { signal }),
  login: (body: unknown) =>
    request<User>("/auth/login", { method: "POST", body }),
  register: (body: unknown) =>
    request<User>("/auth/register", { method: "POST", body }),
  logout: () => request<void>("/auth/logout", { method: "POST" }),
  resend: () => request("/auth/verification/resend", { method: "POST" }),
  forgot: (body: unknown) =>
    request("/auth/forgot-password", { method: "POST", body }),
  reset: (body: unknown) =>
    request("/auth/reset-password", { method: "POST", body }),
  profile: (body: unknown) =>
    request<User>("/me/profile", { method: "PATCH", body }),
  password: (body: unknown) =>
    request("/me/change-password", { method: "POST", body }),
};
