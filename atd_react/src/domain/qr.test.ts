import { describe, it, expect } from "vitest";
import { extractQrToken } from "./qr";
describe("QR input boundary", () => {
  it("extracts only an own-app check-in token", () => {
    expect(
      extractQrToken(
        "https://class.example/app/check-in?token=YWJj.abcd",
        "https://class.example",
      ),
    ).toBe("YWJj.abcd");
  });
  it("accepts copied signed token without assigning identity or time", () => {
    expect(extractQrToken(" YWJj.abcd ", "https://class.example")).toBe(
      "YWJj.abcd",
    );
  });
  it.each([
    "https://evil.example/app/check-in?token=a.b",
    "https://class.example/login?token=a.b",
    "CLASSCODE",
    "a.b.extra",
    "a.b\nattack",
  ])("rejects malformed or unrelated QR: %s", (value) => {
    expect(() => extractQrToken(value, "https://class.example")).toThrow();
  });
});
