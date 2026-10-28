import { test, expect, type Page } from "@playwright/test";
import fs from "node:fs";

const fixture = JSON.parse(
  fs.readFileSync(
    new URL("../../work/demo-accounts-demo.json", import.meta.url),
    "utf8",
  ),
);
async function login(page: Page, email: string, destination: RegExp) {
  await page.goto("/login");
  await page.getByLabel("Địa chỉ email").fill(email);
  await page
    .getByLabel("Mật khẩu", { exact: true })
    .fill(fixture.initialPassword);
  await page.getByRole("button", { name: "Đăng nhập", exact: true }).click();
  await expect(page).toHaveURL(destination);
}

test("admin filters, locks with reason, revokes sessions, audits and unlocks without reading classroom content", async ({
  browser,
}) => {
  const adminContext = await browser.newContext({
    viewport: { width: 1440, height: 1000 },
  });
  const studentContext = await browser.newContext({
    viewport: { width: 390, height: 844 },
  });
  const admin = await adminContext.newPage();
  const student = await studentContext.newPage();
  const errors: string[] = [];
  admin.on("pageerror", (error) => errors.push(error.message));
  student.on("pageerror", (error) => errors.push(error.message));
  const email = "student090@demo.attendance.test";
  await login(admin, "admin@demo.attendance.test", /\/admin$/);
  await admin.goto("/admin/users?q=" + encodeURIComponent(email));
  const setupRow = admin.getByRole("article", {
    name: "Tài khoản " + email,
    exact: true,
  });
  await expect(setupRow).toBeVisible();
  if (
    await setupRow
      .getByRole("button", { name: "Mở khóa tài khoản", exact: true })
      .count()
  ) {
    await setupRow
      .getByRole("button", { name: "Mở khóa tài khoản", exact: true })
      .click();
    await admin
      .getByLabel("Lý do thay đổi trạng thái")
      .fill("Chuẩn bị lại kịch bản kiểm tra demo");
    await admin
      .getByRole("button", { name: "Xác nhận mở khóa", exact: true })
      .click();
    await expect(
      setupRow.getByRole("button", { name: "Khóa tài khoản", exact: true }),
    ).toBeVisible();
  }
  await login(student, email, /\/app$/);
  await student.goto("/admin");
  await expect(
    student.getByRole("heading", { name: "Không có quyền quản trị" }),
  ).toBeVisible();
  expect((await student.request.get("/api/v1/admin/users")).status()).toBe(403);
  await admin.goto("/admin");
  for (const [name, count] of [
    ["users", "94"],
    ["admins", "1"],
    ["teachers", "3"],
    ["students", "90"],
  ]) {
    await expect(admin.getByTestId("admin-stat-" + name)).toContainText(count);
  }
  expect(
    (
      await admin.request.get("/api/v1/classes/" + fixture.classIds[0])
    ).status(),
  ).toBe(404);
  await admin.screenshot({
    path: "../docs/evidence/admin-desktop.png",
    fullPage: true,
  });
  await admin.goto("/admin/users");
  await admin.getByLabel("Vai trò tài khoản").selectOption("student");
  await admin.getByLabel("Tìm tài khoản").fill(email);
  const row = admin.getByRole("article", {
    name: "Tài khoản " + email,
    exact: true,
  });
  await expect(row).toBeVisible();
  const reason = "Kiểm tra khóa tài khoản demo bằng trình duyệt";
  await row
    .getByRole("button", { name: "Khóa tài khoản", exact: true })
    .click();
  await admin.getByLabel("Lý do thay đổi trạng thái").fill(reason);
  await admin
    .getByRole("button", { name: "Xác nhận khóa", exact: true })
    .click();
  await expect(
    row.getByRole("button", { name: "Mở khóa tài khoản", exact: true }),
  ).toBeVisible();
  // An already authenticated browser loses access as soon as the account is locked.
  expect((await student.request.get("/api/v1/me")).status()).toBe(401);
  await row.getByRole("link", { name: "Nhật ký", exact: true }).click();
  await expect(
    admin.getByText("Lý do: " + reason, { exact: true }).first(),
  ).toBeVisible();
  await admin.goto("/admin/users?q=" + encodeURIComponent(email));
  await row
    .getByRole("button", { name: "Mở khóa tài khoản", exact: true })
    .click();
  await admin
    .getByLabel("Lý do thay đổi trạng thái")
    .fill("Hoàn tất kiểm tra demo, mở lại tài khoản");
  await admin
    .getByRole("button", { name: "Xác nhận mở khóa", exact: true })
    .click();
  await expect(
    row.getByRole("button", { name: "Khóa tài khoản", exact: true }),
  ).toBeVisible();
  expect((await student.request.get("/api/v1/me")).status()).toBe(401);
  await login(student, email, /\/app$/);
  await admin.setViewportSize({ width: 390, height: 844 });
  await expect
    .poll(() =>
      admin
        .locator(".sidebar")
        .evaluate((element) => element.getBoundingClientRect().right),
    )
    .toBeLessThanOrEqual(0);
  await expect
    .poll(() =>
      admin.evaluate(
        () => document.documentElement.scrollWidth <= window.innerWidth,
      ),
    )
    .toBe(true);
  await admin.screenshot({
    path: "../docs/evidence/admin-mobile.png",
    fullPage: true,
  });
  expect(errors).toEqual([]);
  await adminContext.close();
  await studentContext.close();
});
