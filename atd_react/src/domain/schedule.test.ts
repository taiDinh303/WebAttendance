import { singleSessionTimes } from "./schedule";
import { parseScheduleDate, parseScheduleTime, parseScheduleDateTime } from "./schedule";
import { afterEach, describe, expect, it, vi } from "vitest";
import { localDateTimeInput, sessionDisplayStatus } from "./schedule";

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

describe("session display status", () => {
  const session = {
    status: "scheduled",
    startsAt: "2026-11-03T01:00:00Z",
    endsAt: "2026-11-03T02:00:00Z",
  };
  it.each([
    ["2026-11-01T01:00:00Z", "scheduled"],
    ["2026-11-03T00:59:59Z", "scheduled"],
    ["2026-11-03T01:00:00Z", "ongoing"],
    ["2026-11-03T01:59:59Z", "ongoing"],
    ["2026-11-03T02:00:00Z", "ended"],
    ["2026-11-05T01:00:00Z", "ended"],
  ])("%s displays %s", (instant, expected) => {
    expect(sessionDisplayStatus(session, Date.parse(instant))).toBe(expected);
  });
  it("keeps cancelled sessions cancelled before, during and after the scheduled time", () => {
    for (const instant of [
      "2026-11-01T01:00:00Z",
      "2026-11-03T01:30:00Z",
      "2026-11-05T01:00:00Z",
    ]) {
      expect(
        sessionDisplayStatus(
          { ...session, status: "cancelled" },
          Date.parse(instant),
        ),
      ).toBe("cancelled");
    }
  });
  it("compares instants across timezone offsets and midnight", () => {
    expect(
      sessionDisplayStatus(
        {
          ...session,
          startsAt: "2026-11-03T23:30:00+07:00",
          endsAt: "2026-11-04T00:30:00+07:00",
        },
        Date.parse("2026-11-03T17:30:00Z"),
      ),
    ).toBe("ended");
  });
});

describe("manual schedule entry",()=>{it("normalizes typed dates and preserves seconds",()=>{expect(parseScheduleDate("03/11/2026")).toBe("2026-11-03");expect(parseScheduleTime("08:30:17")).toBe("08:30:17");expect(parseScheduleDateTime("03/11/2026 08:30:17")).toBe(new Date("2026-11-03T08:30:17").toISOString());});it("rejects impossible dates and invalid hours",()=>{expect(()=>parseScheduleDate("31/11/2026")).toThrow();expect(()=>parseScheduleTime("24:00")).toThrow();expect(()=>parseScheduleDateTime("not a date")).toThrow();});});

describe("single day session", () => {
  it("uses the same day for both instants", () => {
    expect(singleSessionTimes("03/11/2026", "08:30", "17:00")).toEqual({ startsAt: new Date("2026-11-03T08:30:00").toISOString(), endsAt: new Date("2026-11-03T17:00:00").toISOString() });
  });
  it("explains equal or reversed hours in Vietnamese", () => {
    for (const end of ["08:30", "07:00"]) expect(() => singleSessionTimes("03/11/2026", "08:30", end)).toThrow("Giờ kết thúc phải sau giờ bắt đầu");
  });
});
