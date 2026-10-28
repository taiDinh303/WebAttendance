import { describe, expect, it } from "vitest";
import { canAdminister } from "./access";

describe("administrator navigation boundary", () => {
  it("admits only an active verified administrator", () => {
    expect(
      canAdminister({
        role: "admin",
        accountStatus: "active",
        emailVerified: true,
      }),
    ).toBe(true);
  });
  it.each(["teacher", "student"] as const)("rejects the %s role", (role) => {
    expect(
      canAdminister({ role, accountStatus: "active", emailVerified: true }),
    ).toBe(false);
  });
  it("rejects an administrator whose account is locked", () => {
    expect(
      canAdminister({
        role: "admin",
        accountStatus: "locked",
        emailVerified: true,
      }),
    ).toBe(false);
  });
  it("rejects an unverified administrator", () => {
    expect(
      canAdminister({
        role: "admin",
        accountStatus: "active",
        emailVerified: false,
      }),
    ).toBe(false);
  });
  it("rejects a missing session", () => {
    expect(canAdminister(null)).toBe(false);
    expect(canAdminister(undefined)).toBe(false);
  });
});
