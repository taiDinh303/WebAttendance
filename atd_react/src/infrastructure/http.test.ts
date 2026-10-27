import { afterEach, beforeEach, describe, it, expect, vi } from "vitest";
import { request, ApiError } from "./http";
describe("Sanctum HTTP boundary", () => {
  beforeEach(() => {
    vi.stubGlobal("document", { cookie: "XSRF-TOKEN=csrf%2Btoken" });
    vi.stubGlobal("window", new EventTarget());
  });
  afterEach(() => vi.unstubAllGlobals());
  it("sends cookie credentials and decoded CSRF with JSON mutations", async () => {
    const transport = vi
      .fn()
      .mockResolvedValue(Response.json({ data: { id: "ok" } }));
    vi.stubGlobal("fetch", transport);
    expect(
      await request("/classes", { method: "POST", body: { name: "Class" } }),
    ).toEqual({ id: "ok" });
    const options = transport.mock.calls[0][1];
    expect(options.credentials).toBe("include");
    expect(options.headers["X-XSRF-TOKEN"]).toBe("csrf+token");
    expect(options.headers["Content-Type"]).toBe("application/json");
  });
  it("bootstraps the CSRF cookie before a first mutation", async () => {
    document.cookie = "";
    const transport = vi.fn().mockImplementation(async (path: string) => {
      if (path === "/sanctum/csrf-cookie") {
        document.cookie = "XSRF-TOKEN=fresh";
        return new Response(null, { status: 204 });
      }
      return Response.json({ data: { ok: true } });
    });
    vi.stubGlobal("fetch", transport);
    await request("/auth/login", { method: "POST", body: {} });
    expect(transport.mock.calls.map((c) => c[0])).toEqual([
      "/sanctum/csrf-cookie",
      "/api/v1/auth/login",
    ]);
    expect(transport.mock.calls[1][1].headers["X-XSRF-TOKEN"]).toBe("fresh");
  });
  it("does not replay a mutation rejected with 419 and preserves trace and fields", async () => {
    const transport = vi.fn().mockResolvedValue(
      Response.json(
        {
          code: "CSRF_MISMATCH",
          traceId: "trace-test",
          errors: { name: ["Invalid"] },
        },
        { status: 419 },
      ),
    );
    vi.stubGlobal("fetch", transport);
    await expect(
      request("/classes", { method: "POST", body: {} }),
    ).rejects.toMatchObject({
      status: 419,
      code: "CSRF_MISMATCH",
      traceId: "trace-test",
      fields: { name: ["Invalid"] },
    });
    expect(transport).toHaveBeenCalledTimes(1);
  });
  it("does not set Content-Type on multipart uploads", async () => {
    const transport = vi
      .fn()
      .mockResolvedValue(Response.json({ data: { id: "file" } }));
    vi.stubGlobal("fetch", transport);
    const body = new FormData();
    body.set("file", new Blob(["content"]), "answer.txt");
    await request("/classes/test/files", { method: "POST", body });
    expect(transport.mock.calls[0][1].headers["Content-Type"]).toBeUndefined();
    expect(transport.mock.calls[0][1].body).toBe(body);
  });
  it("expires account state on 401", async () => {
    const listener = vi.fn();
    window.addEventListener("session-expired", listener);
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockResolvedValue(
          Response.json({ code: "UNAUTHENTICATED" }, { status: 401 }),
        ),
    );
    await expect(request("/me")).rejects.toBeInstanceOf(ApiError);
    expect(listener).toHaveBeenCalledTimes(1);
  });
  it("reports an uncertain network outcome without an automatic retry", async () => {
    const transport = vi.fn().mockRejectedValue(new TypeError("network"));
    vi.stubGlobal("fetch", transport);
    await expect(
      request("/classes", { method: "POST", body: {} }),
    ).rejects.toMatchObject({ status: 0, code: "NETWORK_ERROR" });
    expect(transport).toHaveBeenCalledTimes(1);
  });
});
