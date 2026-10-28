import { defineConfig } from "@playwright/test";
export default defineConfig({
  testDir: "./e2e",
  fullyParallel: false,
  workers: 1,
  timeout: 240000,
  expect: { timeout: 20000 },
  retries: 0,
  reporter: [
    ["list"],
    ["json", { outputFile: "../work/react-e2e-results.json" }],
  ],
  use: {
    actionTimeout: 20000,
    navigationTimeout: 20000,
    baseURL: process.env.PLAYWRIGHT_BASE_URL ?? "http://127.0.0.1:5173",
    browserName: "chromium",
    channel: "msedge",
    headless: true,
    viewport: { width: 1440, height: 1000 },
    timezoneId: "Asia/Ho_Chi_Minh",
    screenshot: "only-on-failure",
    trace: "retain-on-failure",
  },
  outputDir: "test-results",
});
