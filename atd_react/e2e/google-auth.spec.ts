import { test, expect, type Page } from "@playwright/test";
const clientId = "synthetic.apps.googleusercontent.com";
const user = {
  id: "synthetic",
  displayName: "Synthetic User",
  email: "student@gmail.com",
  role: "student",
  emailVerified: true,
  accountStatus: "active",
  phone: null,
  locale: "vi",
  timezone: "Asia/Ho_Chi_Minh",
  version: 1,
  googleLinked: false,
  hasPassword: true,
};
const gisScript = `(() => {let callback;window.google={accounts:{id:{initialize(options){callback=options.callback;},renderButton(element){const b=document.createElement('button');b.type='button';b.textContent='Tiếp tục với Google';b.onclick=()=>callback({credential:'synthetic-token'});element.append(b);}}}};})();`;
async function setup(page: Page, signedIn = false, failFirst = false) {
  let account: typeof user | null = signedIn ? { ...user } : null;
  const bodies: Record<string, unknown>[] = [];
  let challenges = 0;
  await page.route("https://accounts.google.com/gsi/client", (route) =>
    route.fulfill({ contentType: "application/javascript", body: gisScript }),
  );
  await page.route("**/sanctum/csrf-cookie", (route) =>
    route.fulfill({ status: 204 }),
  );
  await page.route("**/api/v1/**", async (route) => {
    const path = new URL(route.request().url()).pathname;
    if (path === "/api/v1/me")
      return route.fulfill({
        status: account ? 200 : 401,
        json: account ? { data: account } : { code: "UNAUTHENTICATED" },
      });
    if (path === "/api/v1/auth/google/challenge") {
      challenges++;
      return route.fulfill({
        json: { data: { clientId, nonce: `nonce-${challenges}` } },
      });
    }
    if (path === "/api/v1/auth/google") {
      bodies.push(route.request().postDataJSON());
      if (failFirst && bodies.length === 1)
        return route.fulfill({
          status: 409,
          json: { code: "GOOGLE_LINK_REQUIRED" },
        });
      account = {
        ...user,
        googleLinked: true,
        hasPassword: false,
        role: String(bodies.at(-1)?.role ?? "student"),
      };
      return route.fulfill({ json: { data: account } });
    }
    if (path === "/api/v1/me/google") {
      account = {
        ...user,
        googleLinked: route.request().method() !== "DELETE",
      };
      return route.fulfill({ json: { data: account } });
    }
    if (path === "/api/v1/dashboard")
      return route.fulfill({
        json: {
          data: {
            role: account?.role,
            serverTime: new Date().toISOString(),
            timezone: "Asia/Ho_Chi_Minh",
            summary: {
              activeClasses: 0,
              todaySessions: 0,
              upcomingSessions: 0,
              unreadCount: 0,
              pendingAssignments: 0,
              publishedGrades: 0,
            },
            classes: [],
            sessions: [],
            assignments: [],
            grades: [],
          },
        },
      });
    return route.fulfill({
      json: {
        data: {
          items: [],
          totalItems: 0,
          pageNumber: 1,
          pageSize: 20,
          value: null,
          version: 0,
        },
      },
    });
  });
  return { bodies, challenges: () => challenges };
}
test("Google registration sends the chosen role and establishes the session", async ({
  page,
}) => {
  const state = await setup(page);
  await page.setViewportSize({ width: 393, height: 852 });
  await page.goto("/register");
  await page.getByLabel("Bạn tham gia với vai trò").selectOption("student");
  await page
    .getByRole("button", { name: "Tiếp tục với Google", exact: true })
    .click();
  await expect(page).toHaveURL(/\/app$/);
  await expect(page.getByRole("heading", { name: /Chào User/ })).toBeVisible();
  expect(state.bodies).toEqual([
    { credential: "synthetic-token", role: "student" },
  ]);
});
test("existing-email error preserves the password form and retry gets a fresh challenge", async ({
  page,
}) => {
  const state = await setup(page, false, true);
  await page.goto("/login");
  await page.getByLabel("Địa chỉ email").fill("student@gmail.com");
  await page
    .getByRole("button", { name: "Tiếp tục với Google", exact: true })
    .click();
  await expect(
    page.getByText(/Email này đã có tài khoản/).first(),
  ).toBeVisible();
  await expect(page.getByLabel("Địa chỉ email")).toHaveValue(
    "student@gmail.com",
  );
  await page
    .getByRole("button", { name: "Thử lại Google", exact: true })
    .click();
  await page
    .getByRole("button", { name: "Tiếp tục với Google", exact: true })
    .click();
  await expect(page).toHaveURL(/\/app$/);
  expect(state.challenges()).toBe(2);
  expect(state.bodies[1]).toEqual({ credential: "synthetic-token" });
});
test("Settings connects and disconnects Google through the signed-in account", async ({
  page,
}) => {
  await setup(page, true);
  await page.goto("/app/settings?tab=account");
  await page
    .getByRole("button", { name: "Liên kết Google", exact: true })
    .click();
  await page
    .getByRole("button", { name: "Tiếp tục với Google", exact: true })
    .click();
  await expect(
    page.getByText("Google đã liên kết", { exact: true }),
  ).toBeVisible();
  page.once("dialog", (dialog) => dialog.accept());
  await page
    .getByRole("button", { name: "Ngắt liên kết", exact: true })
    .click();
  await expect(
    page.getByRole("button", { name: "Liên kết Google", exact: true }),
  ).toBeVisible();
});

test("a blocked Google script can be retried without losing the email form", async ({page}) => {
  await setup(page);
  let scripts = 0;
  await page.route("https://accounts.google.com/gsi/client", route => {
    scripts++;
    return scripts === 1 ? route.abort() : route.fulfill({contentType:"application/javascript",body:gisScript});
  });
  await page.goto("/login");
  await page.getByLabel("Địa chỉ email").fill("student@gmail.com");
  await expect(page.getByText(/Không tải được Google/).first()).toBeVisible();
  await page.getByRole("button",{name:"Thử lại Google",exact:true}).click();
  await page.getByRole("button",{name:"Tiếp tục với Google",exact:true}).click();
  await expect(page).toHaveURL(/\/app$/);
  expect(scripts).toBe(2);
});
