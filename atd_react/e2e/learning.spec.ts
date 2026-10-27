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
test("teacher and student complete membership, submission retry, grading and QR over real API", async ({
  browser,
}) => {
  const teacherContext = await browser.newContext({
    viewport: { width: 1440, height: 1000 },
    timezoneId: "Asia/Ho_Chi_Minh",
  });
  const studentContext = await browser.newContext({
    viewport: { width: 1280, height: 900 },
    timezoneId: "Asia/Ho_Chi_Minh",
  });
  const teacher = await teacherContext.newPage(),
    student = await studentContext.newPage();
  const errors: string[] = [];
  teacher.on("pageerror", (e) => errors.push(e.message));
  student.on("pageerror", (e) => errors.push(e.message));
  await login(teacher, credentials.teacherA);
  await login(student, credentials.student);
  await teacher.getByRole("link", { name: "Lớp học", exact: true }).click();
  await teacher
    .getByRole("button", { name: "Tạo lớp học", exact: true })
    .click();
  const className =
    "Toán 10A1 · " + Math.random().toString(36).slice(2, 6).toUpperCase();
  await teacher.getByLabel("Tên lớp", { exact: true }).fill(className);
  await teacher
    .getByLabel("Mô tả", { exact: true })
    .fill("Cùng khám phá kiến thức, chủ động học tập và tiến bộ mỗi ngày.");
  await teacher.getByRole("button", { name: "Tạo lớp", exact: true }).click();
  await expect(
    teacher.getByRole("heading", { name: className, exact: true }),
  ).toBeVisible();
  const classUrl = teacher.url();
  const code = await teacher.locator(".invite-code code").innerText();
  await student.getByRole("link", { name: "Lớp học", exact: true }).click();
  await student
    .getByRole("button", { name: "Tham gia lớp", exact: true })
    .click();
  await student.getByLabel("Mã lớp từ giáo viên").fill(code);
  await student
    .getByRole("button", { name: "Gửi yêu cầu", exact: true })
    .click();
  await expect(
    student.getByText("Chờ duyệt", { exact: true }).first(),
  ).toBeVisible();
  await student.goto(classUrl);
  await expect(student.getByRole("alert")).toContainText("không còn quyền");
  await teacher.getByRole("link", { name: "Thành viên", exact: true }).click();
  await teacher
    .getByRole("button", { name: "Yêu cầu vào lớp", exact: true })
    .click();
  await teacher.getByRole("button", { name: "Duyệt", exact: true }).click();
  await expect(
    teacher.getByText("Đang hoạt động", { exact: true }).last(),
  ).toBeVisible();
  await student.goto(classUrl);
  await expect(
    student.getByRole("heading", { name: className, exact: true }),
  ).toBeVisible();
  await expect(
    student.getByRole("link", { name: "Thành viên", exact: true }),
  ).toHaveCount(0);
  await teacher.getByRole("link", { name: "Bài tập", exact: true }).click();
  await teacher.getByRole("button", { name: "Giao bài tập" }).click();
  await teacher.getByLabel("Tiêu đề bài tập").fill("Hàm số và đồ thị");
  await teacher
    .getByLabel("Hướng dẫn", { exact: true })
    .fill("Hoàn thành bài làm và gửi file lời giải.");
  await teacher
    .getByRole("button", { name: "Tạo bản nháp", exact: true })
    .click();
  await expect(
    teacher.getByRole("heading", { name: "Hàm số và đồ thị", exact: true }),
  ).toBeVisible();
  const assignmentUrl = teacher.url();
  await teacher
    .getByRole("button", { name: "Công bố bài tập", exact: true })
    .click();
  await expect(
    teacher.getByText("Đã công bố", { exact: true }).first(),
  ).toBeVisible();
  await student.goto(assignmentUrl);
  await expect(
    student.getByRole("heading", { name: "Hàm số và đồ thị", exact: true }),
  ).toBeVisible();
  await student.getByLabel("File bài làm", { exact: true }).setInputFiles({
    name: "loi-giai.txt",
    mimeType: "text/plain",
    buffer: Buffer.from("Bai lam kiem thu tong hop React API"),
  });
  await student
    .getByLabel("Ghi chú cho giáo viên")
    .fill("Em gửi bài làm của mình.");
  let dropped = false;
  const keys: string[] = [];
  await student.route(
    "**/api/v1/classes/*/assignments/*/submissions",
    async (route) => {
      if (route.request().method() !== "POST") {
        await route.continue();
        return;
      }
      keys.push(route.request().headers()["idempotency-key"]);
      if (!dropped) {
        dropped = true;
        await route.fetch();
        await route.abort("failed");
      } else await route.continue();
    },
  );
  await student.getByRole("button", { name: "Nộp bài", exact: true }).click();
  await expect(student.getByRole("alert")).toContainText("Không kết nối được");
  await expect(
    student.getByLabel("File bài làm", { exact: true }),
  ).toBeDisabled();
  await student
    .getByRole("button", { name: "Xác nhận lại lần nộp", exact: true })
    .click();
  await expect(
    student.getByRole("heading", { name: "Đã nộp bài thành công" }),
  ).toBeVisible();
  expect(keys).toHaveLength(2);
  expect(keys[0]).toBe(keys[1]);
  await student.getByRole("link", { name: "Xem bài đã nộp" }).click();
  const submissionUrl = student.url();
  await expect(
    student.getByRole("heading", { name: "Lần nộp 1", exact: true }),
  ).toBeVisible();
  await expect(
    student.getByRole("button", { name: "Lần 2", exact: true }),
  ).toHaveCount(0);
  await teacher.goto(submissionUrl);
  await teacher.getByLabel("Điểm / 10").fill("8.5");
  await teacher
    .getByLabel("Nhận xét", { exact: true })
    .fill("Bài làm tốt, trình bày rõ ràng.");
  await teacher
    .getByRole("button", { name: "Lưu điểm nháp", exact: true })
    .click();
  await expect(
    teacher.getByText("Đã lưu điểm nháp. Học sinh chưa thấy điểm mới này.", {
      exact: true,
    }),
  ).toBeVisible();
  await student.goto(classUrl + "/grades");
  await expect(student.locator(".average-box strong")).toHaveText("— / 10");
  await teacher
    .getByRole("button", { name: "Công bố điểm", exact: true })
    .click();
  await expect(
    teacher.getByText("Điểm đã được công bố.", { exact: true }),
  ).toBeVisible();
  await student.reload();
  await expect(student.locator(".average-box strong")).toContainText("8.5");
  await expect(
    student.getByText("Bài làm tốt, trình bày rõ ràng."),
  ).toBeVisible();
  await teacher.goto(classUrl + "/schedule");
  await teacher
    .getByRole("button", { name: "Tạo buổi học", exact: true })
    .click();
  await teacher.getByLabel("Tên buổi học").fill("Luyện tập hàm số");
  const times = await teacher.evaluate(() => {
    const now = Date.now();
    const local = (ms: number) =>
      new Date(ms - new Date(ms).getTimezoneOffset() * 60000)
        .toISOString()
        .slice(0, 16);
    return { start: local(now - 60000), end: local(now + 3600000) };
  });
  await teacher
    .getByLabel("Bắt đầu (giờ máy của bạn)", { exact: true })
    .fill(times.start);
  await teacher.getByLabel("Kết thúc", { exact: true }).fill(times.end);
  await teacher.getByLabel("Tôi chấp nhận nếu lịch bị trùng").check();
  await teacher.getByRole("button", { name: "Tạo lịch", exact: true }).click();
  await expect(
    teacher.getByRole("heading", { name: "Luyện tập hàm số" }),
  ).toBeVisible();
  await teacher
    .getByRole("link", { name: "Điểm danh QR", exact: true })
    .last()
    .click();
  const qrResponse = teacher.waitForResponse(
    (r) =>
      r.url().includes("/api/v1/attendance-windows/") &&
      r.url().endsWith("/qr") &&
      r.request().method() === "GET" &&
      r.status() === 200,
  );
  await teacher
    .getByRole("button", { name: "Mở QR & chốt danh sách", exact: true })
    .click();
  const qr = (await (await qrResponse).json()).data;
  await expect(
    teacher.getByRole("img", { name: "Mã QR điểm danh đang có hiệu lực" }),
  ).toBeVisible();
  await student.goto("/app/check-in?token=" + encodeURIComponent(qr.qrToken));
  await student
    .getByRole("button", { name: "Xác nhận điểm danh", exact: true })
    .click();
  await expect(
    student.getByRole("heading", {
      name: "Đã ghi nhận điểm danh",
      exact: true,
    }),
  ).toBeVisible();
  teacher.once("dialog", (dialog) => dialog.accept());
  await teacher
    .getByRole("button", { name: "Đóng điểm danh", exact: true })
    .click();
  await expect(
    teacher.getByText(
      "Điểm danh đã đóng. Giáo viên có thể điều chỉnh kết quả với lý do.",
    ),
  ).toBeVisible();
  await expect(teacher.getByText("Có mặt", { exact: true })).toBeVisible();
  await teacher
    .getByRole("button", { name: "Điều chỉnh", exact: true })
    .click();
  await teacher
    .getByRole("combobox", { name: "Kết quả mới", exact: true })
    .selectOption("late");
  await teacher
    .getByLabel("Lý do điều chỉnh", { exact: true })
    .fill("Điều chỉnh theo xác nhận tại lớp");
  await teacher
    .getByRole("button", { name: "Lưu điều chỉnh", exact: true })
    .click();
  await expect(teacher.getByText("Đi muộn", { exact: true })).toBeVisible();
  await teacher.goto("/app");
  await expect(teacher.getByText(className, { exact: true })).toBeVisible();
  await expect(
    teacher.getByText("Đang tải dữ liệu…", { exact: true }),
  ).toHaveCount(0);
  await teacher.screenshot({
    path: "../work/react-dashboard.png",
    fullPage: true,
  });
  await student.setViewportSize({ width: 390, height: 844 });
  await student.goto(classUrl + "/assignments");
  await expect(
    student.getByRole("heading", { name: className, exact: true }),
  ).toBeVisible();
  await expect(
    student.getByRole("heading", { name: "Hàm số và đồ thị", exact: true }),
  ).toBeVisible();
  expect(
    await student.evaluate(
      () => document.documentElement.scrollWidth <= innerWidth,
    ),
  ).toBe(true);
  await student.screenshot({
    path: "../work/react-student-mobile.png",
    fullPage: true,
  });
  await teacher.getByRole("button", { name: "Đăng xuất", exact: true }).click();
  await expect(teacher).toHaveURL(/\/login/);
  await expect(teacher.getByText(className, { exact: true })).toHaveCount(0);
  await login(teacher, credentials.teacherB);
  await teacher.goto(classUrl);
  await expect(teacher.getByRole("alert")).toContainText("không còn quyền");
  expect(errors).toEqual([]);
  await teacherContext.close();
  await studentContext.close();
});
test("login and registration render on mobile with safe redirects", async ({
  page,
}) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto("/app/classes");
  await expect(page).toHaveURL(/\/login\?next=/);
  await expect(
    page.getByRole("heading", { name: "Chào mừng trở lại." }),
  ).toBeVisible();
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= innerWidth,
    ),
  ).toBe(true);
  await page.screenshot({
    path: "../work/react-login-mobile.png",
    fullPage: true,
  });
  await page.getByRole("link", { name: "Đăng ký miễn phí" }).click();
  await expect(page.getByLabel("Bạn tham gia với vai trò")).toBeVisible();
  await expect(
    page.getByLabel("Nhập lại mật khẩu", { exact: true }),
  ).toBeVisible();
});

test("dashboard counters and notification read acknowledgments use the real contract", async ({
  page,
}) => {
  await login(page, credentials.teacherA);
  const counters = page.locator(".stat-card strong");
  for (let index = 0; index < 3; index++)
    await expect(counters.nth(index)).not.toHaveText("—");
  const before = Number(await counters.nth(2).innerText());
  await page
    .getByRole("link", { name: /^Thông báo/ })
    .first()
    .click();
  await expect(
    page.getByRole("heading", { name: "Thông báo", exact: true }),
  ).toBeVisible();
  if (before > 0) {
    const acknowledged = page.waitForResponse(
      (r) => r.request().method() === "PUT" && r.url().endsWith("/read"),
    );
    await page
      .getByRole("button", { name: "Đánh dấu đã đọc", exact: true })
      .first()
      .click();
    expect((await acknowledged).status()).toBe(204);
  }
  await page.goto("/app");
  for (let index = 0; index < 3; index++)
    await expect(page.locator(".stat-card strong").nth(index)).not.toHaveText(
      "—",
    );
  if (before > 0)
    await expect(page.locator(".stat-card strong").nth(2)).toHaveText(
      String(before - 1),
    );
  await expect(
    page.getByText("Đang tải dữ liệu…", { exact: true }),
  ).toHaveCount(0);
  await page.screenshot({
    path: "../work/react-dashboard.png",
    fullPage: true,
  });
});
