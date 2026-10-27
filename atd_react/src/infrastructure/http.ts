import { ApiError } from "../domain/errors";
export { ApiError } from "../domain/errors";
const origin = (import.meta.env.VITE_API_ORIGIN ?? "").replace(/\/$/, "");
export const cookie = (name: string) =>
  document.cookie
    .split("; ")
    .find((c) => c.startsWith(name + "="))
    ?.slice(name.length + 1);
let csrfPending: Promise<void> | undefined;
async function csrf() {
  if (cookie("XSRF-TOKEN")) return;
  csrfPending ??= fetch(origin + "/sanctum/csrf-cookie", {
    credentials: "include",
    headers: { Accept: "application/json" },
  })
    .then((r) => {
      if (!r.ok)
        throw new ApiError(
          r.status,
          "CSRF_FAILED",
          "Không thể tạo phiên bảo mật. Vui lòng thử lại.",
        );
    })
    .finally(() => {
      csrfPending = undefined;
    });
  await csrfPending;
}
export async function request<T>(
  path: string,
  options: {
    method?: string;
    body?: unknown;
    signal?: AbortSignal;
    headers?: Record<string, string>;
  } = {},
): Promise<T> {
  const method = options.method ?? "GET";
  if (!["GET", "HEAD"].includes(method)) await csrf();
  const headers: Record<string, string> = {
    Accept: "application/json",
    ...options.headers,
  };
  const token = cookie("XSRF-TOKEN");
  if (token) headers["X-XSRF-TOKEN"] = decodeURIComponent(token);
  const multipart = options.body instanceof FormData;
  if (options.body !== undefined && !multipart)
    headers["Content-Type"] = "application/json";
  let response: Response;
  try {
    response = await fetch(origin + "/api/v1" + path, {
      method,
      headers,
      credentials: "include",
      signal: options.signal,
      body:
        options.body === undefined
          ? undefined
          : multipart
            ? (options.body as FormData)
            : JSON.stringify(options.body),
    });
  } catch (error) {
    if (error instanceof DOMException && error.name === "AbortError")
      throw error;
    throw new ApiError(
      0,
      "NETWORK_ERROR",
      "Không kết nối được máy chủ. Nội dung của bạn vẫn được giữ trên trang.",
    );
  }
  const body =
    response.status === 204 ? null : await response.json().catch(() => null);
  if (!response.ok) {
    if (response.status === 401)
      window.dispatchEvent(new Event("session-expired"));
    const messages: Record<number, string> = {
      401: "Phiên đăng nhập đã hết hạn.",
      403: "Tài khoản không có quyền thực hiện.",
      404: "Dữ liệu không tồn tại hoặc bạn không còn quyền truy cập.",
      409: "Dữ liệu đã thay đổi. Hãy tải lại trước khi lưu; nội dung đang nhập vẫn được giữ.",
      419: "Phiên bảo mật đã hết hạn. Tải lại trang rồi thực hiện lại.",
      429: "Bạn thao tác quá nhanh. Vui lòng chờ trước khi thử lại.",
      503: "Dịch vụ tạm thời chưa sẵn sàng.",
    };
    throw new ApiError(
      response.status,
      body?.code ?? "REQUEST_FAILED",
      messages[response.status] ??
        body?.message ??
        "Không thể hoàn tất thao tác.",
      body?.errors ?? {},
      body?.traceId,
    );
  }
  return body?.data as T;
}
export const params = (
  values: Record<string, string | number | boolean | undefined | null>,
) => {
  const p = new URLSearchParams();
  for (const [k, v] of Object.entries(values))
    if (v !== undefined && v !== null && v !== "") p.set(k, String(v));
  return "?" + p.toString();
};
export const fileUrl = (id: string) =>
  origin + "/api/v1/files/" + encodeURIComponent(id) + "/download";
