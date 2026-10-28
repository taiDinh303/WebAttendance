import { afterEach, describe, expect, it, vi } from "vitest";
import { localDateTimeInput } from "./schedule";

afterEach(() => vi.unstubAllEnvs());
describe("session edit local datetime", () => {
  it("converts UTC to the device timezone across midnight", () => {
    vi.stubEnv("TZ", "Asia/Ho_Chi_Minh");
    expect(localDateTimeInput("2026-12-31T18:05:00Z")).toBe("2027-01-01T01:05");
  });
  it("preserves the instant through a datetime-local edit round trip", () => {
    vi.stubEnv("TZ", "Asia/Ho_Chi_Minh");
    const input = localDateTimeInput("2026-10-28T02:30:00Z");
    expect(input).toBe("2026-10-28T09:30");
    expect(new Date(input).toISOString()).toBe("2026-10-28T02:30:00.000Z");
  });
  it("preserves seconds when only the session title changes", () => {
    vi.stubEnv("TZ", "Asia/Ho_Chi_Minh");
    const input = localDateTimeInput("2026-10-28T02:30:17Z");
    expect(input).toBe("2026-10-28T09:30:17");
    expect(new Date(input).toISOString()).toBe("2026-10-28T02:30:17.000Z");
  });
});
