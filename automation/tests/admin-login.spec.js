const { test, expect } = require("@playwright/test");

test("admin login reaches wp-admin", async ({ page }) => {
  const username = process.env.QA_ADMIN_USER || "admin";
  const password = process.env.QA_ADMIN_PASS;

  test.skip(!password, "Set QA_ADMIN_PASS before running live admin login checks.");

  const consoleErrors = [];
  page.on("console", (message) => {
    if (message.type() === "error") {
      consoleErrors.push(message.text());
    }
  });

  page.on("response", (response) => {
    const status = response.status();
    if (status >= 500) {
      consoleErrors.push(`${status} ${response.url()}`);
    }
  });

  await page.goto("/wp-login.php");
  await page.getByLabel("Username or Email Address").fill(username);
  await page.locator("#user_pass").fill(password);
  await page.getByRole("button", { name: "Log In" }).click();

  await expect(page).toHaveURL(/\/wp-admin\/?/);
  await expect(page.locator("#wpadminbar")).toBeVisible();
  expect(consoleErrors).toEqual([]);
});
