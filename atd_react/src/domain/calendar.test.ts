import { describe, it, expect } from "vitest";
import { calendarRange, localDay, reviewDays } from "./calendar";
import type { Session } from "./models";
const session = (day: number, state: "approved" | "pending"): Session => ({
  id: String(day),
  classId: "c",
  title: null,
  room: null,
  startsAt: new Date(2026, 10, day, 8).toISOString(),
  endsAt: new Date(2026, 10, day, 9).toISOString(),
  status: "scheduled",
  version: 1,
  attendanceReviewState: state,
});
describe("calendar review filter", () => {
  it("marks mixed days pending, approved-only days green, and excludes cancelled dates", () => {
    expect(
      reviewDays([
        session(3, "approved"),
        session(3, "pending"),
        session(4, "approved"),
        { ...session(5, "pending"), status: "cancelled" },
      ]),
    ).toEqual({ "2026-11-03": "pending", "2026-11-04": "approved" });
  });
  it("uses local day boundaries including month/year rollover", () => {
    const day = calendarRange("2026-12", "2026-12-31");
    expect(localDay(new Date(day.from))).toBe("2026-12-31");
    expect(localDay(new Date(day.to))).toBe("2027-01-01");
    const month = calendarRange("2028-02");
    expect(localDay(new Date(month.to))).toBe("2028-03-01");
  });
});
