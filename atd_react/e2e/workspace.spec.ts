import { test, expect, type Page } from "@playwright/test";
import fs from "node:fs";
const credentials = JSON.parse(
  fs.readFileSync(
    new URL("../../work/http-credentials.json", import.meta.url),
    "utf8",
  ),
);
async function login(page: Page, account: { email: string; password: string }) {
  await page.goto("/login");
  await page.getByLabel("Địa chỉ email").fill(account.email);
  await page.getByLabel("Mật khẩu", { exact: true }).fill(account.password);
  await page.getByRole("button", { name: "Đăng nhập", exact: true }).click();
  await expect(page).toHaveURL(/\/app$/);
}

test("v2 catalog, class association, session edit, audit and self-leave work over real API", async ({
  browser,
}) => {
  const teacherContext = await browser.newContext({
    viewport: { width: 1440, height: 1000 },
    timezoneId: "Asia/Ho_Chi_Minh",
  });
  const studentContext = await browser.newContext({
    viewport: { width: 390, height: 844 },
    timezoneId: "Asia/Ho_Chi_Minh",
  });
  const teacher = await teacherContext.newPage();
  const student = await studentContext.newPage();
  const errors: string[] = [];
  teacher.on("pageerror", (error) => errors.push(error.message));
  student.on("pageerror", (error) => errors.push(error.message));
  const suffix = Date.now().toString(36);
  const course = "Môn V2 " + suffix;
  const editedCourse = course + " sửa";
  const semester = "Học kỳ V2 " + suffix;
  const className = "Lớp V2 " + suffix;
  await login(teacher, credentials.teacherA);
  await teacher.goto("/app/catalog");
  await teacher.getByLabel("Tên", { exact: true }).fill(course);
  await teacher.getByRole("button", { name: "Thêm mới", exact: true }).click();
  await expect(
    teacher.getByRole("heading", { name: course, exact: true }),
  ).toBeVisible();
  await teacher
    .getByRole("button", { name: "Sửa " + course, exact: true })
    .click();
  await teacher.getByLabel("Tên", { exact: true }).fill(editedCourse);
  await teacher
    .getByRole("button", { name: "Lưu danh mục", exact: true })
    .click();
  await expect(
    teacher.getByRole("heading", { name: editedCourse, exact: true }),
  ).toBeVisible();
  await teacher.getByLabel("Tên", { exact: true }).fill("Tạm " + suffix);
  await teacher.getByRole("button", { name: "Thêm mới", exact: true }).click();
  await expect(
    teacher.getByRole("heading", { name: "Tạm " + suffix, exact: true }),
  ).toBeVisible();
  teacher.once("dialog", (dialog) => dialog.accept());
  await teacher
    .getByRole("button", { name: "Xóa Tạm " + suffix, exact: true })
    .click();
  await expect(
    teacher.getByRole("heading", { name: "Tạm " + suffix, exact: true }),
  ).toHaveCount(0);
  await teacher.getByRole("button", { name: "Học kỳ", exact: true }).click();
  await teacher.getByLabel("Tên", { exact: true }).fill(semester);
  await teacher.getByLabel("Ngày bắt đầu").fill("2026-01-01");
  await teacher.getByLabel("Ngày kết thúc").fill("2026-12-31");
  await teacher.getByRole("button", { name: "Thêm mới", exact: true }).click();
  await expect(
    teacher.getByRole("heading", { name: semester, exact: true }),
  ).toBeVisible();
  await teacher.goto("/app");
  await teacher.getByRole("link", { name: "Tạo lớp học", exact: true }).click();
  await teacher.getByLabel("Tên lớp", { exact: true }).fill(className);
  await teacher
    .getByLabel("Môn học (không bắt buộc)")
    .selectOption({ label: editedCourse });
  await teacher
    .getByLabel("Học kỳ (không bắt buộc)")
    .selectOption({ label: semester });
  await teacher.getByRole("button", { name: "Tạo lớp", exact: true }).click();
  await expect(
    teacher.getByRole("heading", { name: className, exact: true }),
  ).toBeVisible();
  await expect(teacher).toHaveURL(/\/app\/classes\/[^/?]+$/);
  const classUrl = teacher.url();
  const invite = await teacher.locator(".invite-code code").innerText();
  await expect(teacher.getByLabel("Môn học (không bắt buộc)")).toHaveValue(
    /.+/,
  );
  await login(student, credentials.student);
  await student.goto("/app/classes");
  await student
    .getByRole("button", { name: "Tham gia lớp", exact: true })
    .click();
  await student.getByLabel("Mã lớp từ giáo viên").fill(invite);
  await student
    .getByRole("button", { name: "Gửi yêu cầu", exact: true })
    .click();
  await expect(
    student.getByText("Chờ duyệt", { exact: true }).first(),
  ).toBeVisible();
  await teacher.goto(classUrl + "/members?queue=pending");
  await teacher.getByRole("button", { name: "Duyệt", exact: true }).click();
  await expect(
    teacher.getByText("Đang hoạt động", { exact: true }).last(),
  ).toBeVisible();
  await teacher.goto(classUrl + "/schedule");
  await teacher
    .getByRole("button", { name: "Tạo buổi học", exact: true })
    .click();
  await teacher.getByLabel("Tên buổi học", { exact: true }).fill("Buổi V2");
  const start = new Date(Date.now() + 7 * 3600000 + 30 * 60000)
    .toISOString()
    .slice(0, 16);
  const end = new Date(Date.now() + 7 * 3600000 + 90 * 60000)
    .toISOString()
    .slice(0, 16);
  await teacher.getByLabel("Bắt đầu (giờ máy của bạn)").fill(start);
  await teacher.getByLabel("Kết thúc", { exact: true }).fill(end);
  await teacher.getByLabel("Tôi chấp nhận nếu lịch bị trùng").check();
  await teacher.getByRole("button", { name: "Tạo lịch", exact: true }).click();
  await expect(
    teacher.getByRole("heading", { name: "Buổi V2", exact: true }),
  ).toBeVisible();
  await teacher.getByRole("button", { name: "Sửa buổi", exact: true }).click();
  await teacher.getByLabel("Tên buổi học cần sửa").fill("Buổi V2 đã sửa");
  await expect(teacher.getByLabel("Bắt đầu mới (giờ máy)")).toHaveValue(start);
  await teacher.getByLabel("Tôi chấp nhận nếu lịch bị trùng").check();
  await teacher
    .getByRole("button", { name: "Lưu buổi học", exact: true })
    .click();
  await expect(
    teacher.getByRole("heading", { name: "Buổi V2 đã sửa", exact: true }),
  ).toBeVisible();
  await teacher
    .getByRole("link", { name: "Điểm danh QR", exact: true })
    .click();
  await teacher
    .getByRole("button", { name: "Mở QR & chốt danh sách", exact: true })
    .click();
  await expect(
    teacher.getByRole("button", { name: "Điều chỉnh", exact: true }),
  ).toBeVisible();
  await teacher
    .getByRole("button", { name: "Điều chỉnh", exact: true })
    .click();
  await teacher
    .getByLabel("Lý do điều chỉnh")
    .fill("Xác nhận có mặt tại lớp V2");
  await teacher
    .getByRole("button", { name: "Lưu điều chỉnh", exact: true })
    .click();
  await expect(teacher.getByText("Có mặt", { exact: true })).toBeVisible();
  await teacher.goto(classUrl + "/attendance");
  await teacher
    .getByRole("button", { name: "Lịch sử thay đổi", exact: true })
    .click();
  await expect(
    teacher.getByText("Lý do: Xác nhận có mặt tại lớp V2", { exact: true }),
  ).toBeVisible();
  await expect(
    teacher.getByText("Buổi V2 đã sửa", { exact: false }),
  ).toBeVisible();
  await student.goto(classUrl);
  await expect(
    student.getByRole("button", { name: "Rời lớp học", exact: true }),
  ).toBeVisible();
  expect(
    await student.evaluate(
      () => document.documentElement.scrollWidth <= innerWidth,
    ),
  ).toBe(true);
  await student.screenshot({
    path: "../work/v2-class-mobile.png",
    fullPage: true,
  });
  await student.goto("/app");
  await expect(
    student.getByRole("heading", { name: "Việc học cần hoàn thành" }),
  ).toBeVisible();
  await student.getByRole("button", { name: "Mở menu", exact: true }).click();
  await expect(
    student.locator('button[aria-controls="workspace-navigation"]'),
  ).toHaveAttribute("aria-expanded", "true");
  await student.getByRole("button", { name: "Đóng menu", exact: true }).click();
  await expect(student.locator("#workspace-navigation")).not.toBeInViewport();
  await student.screenshot({
    path: "../work/v2-dashboard-student-mobile.png",
    fullPage: true,
  });
  await student.goto(classUrl);
  student.once("dialog", (dialog) => dialog.accept());
  await student
    .getByRole("button", { name: "Rời lớp học", exact: true })
    .click();
  await expect(student).toHaveURL(/\/app\/classes$/);
  await student.goto(classUrl);
  await expect(student.getByRole("alert")).toContainText("không còn quyền");
  await teacher.goto("/app");
  await expect(
    teacher.getByRole("heading", { name: "Bàn làm việc hôm nay" }),
  ).toBeVisible();
  await expect(teacher.locator(".stat-card strong").first()).not.toHaveText(
    "—",
  );
  await teacher.screenshot({
    path: "../work/v2-dashboard-teacher.png",
    fullPage: true,
  });
  expect(errors).toEqual([]);
  await teacherContext.close();
  await studentContext.close();
});

test("catalog pagination inside a class form does not submit the form", async ({
  page,
}) => {
  await login(page, credentials.teacherB);
  const suffix = Date.now().toString(36);
  const csrf = (await page.context().cookies()).find(
    (cookie) => cookie.name === "XSRF-TOKEN",
  );
  expect(csrf).toBeDefined();
  for (let index = 0; index < 21; index++) {
    const response = await page.request.post("/api/v1/courses", {
      headers: {
        "X-XSRF-TOKEN": decodeURIComponent(csrf!.value),
        Accept: "application/json",
      },
      data: {
        name: "Pagination " + suffix + " " + String(index).padStart(2, "0"),
      },
    });
    expect(response.status()).toBe(201);
  }
  await page.goto("/app/classes?create=1");
  await page
    .getByLabel("Tên lớp", { exact: true })
    .fill("This form must not submit " + suffix);
  let submissions = 0;
  await page.route("**/api/v1/classes", async (route) => {
    if (route.request().method() === "POST") {
      submissions++;
      await route.abort();
    } else await route.continue();
  });
  const picker = page
    .locator(".catalog-select")
    .filter({ has: page.getByLabel("Môn học (không bắt buộc)") });
  await picker.getByRole("button", { name: "Sau", exact: false }).click();
  await expect(picker.getByText(/Trang 2\//)).toBeVisible();
  expect(submissions).toBe(0);
  await expect(page).toHaveURL(/\/app\/classes\?create=1$/);
  await expect(page.getByLabel("Tên lớp", { exact: true })).toHaveValue(
    "This form must not submit " + suffix,
  );
});
