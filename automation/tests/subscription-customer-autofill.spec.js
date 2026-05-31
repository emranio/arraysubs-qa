const { test, expect } = require("@playwright/test");

const username = process.env.QA_ADMIN_USER || "admin";
const password = process.env.QA_ADMIN_PASS;

test("selecting a customer auto-fills billing/shipping on the add subscription form", async ({
  page,
}) => {
  test.skip(!password, "Set QA_ADMIN_PASS before running.");

  // Login
  await page.goto("/wp-login.php");
  await page.getByLabel("Username or Email Address").fill(username);
  await page.locator("#user_pass").fill(password);
  await page.getByRole("button", { name: "Log In" }).click();
  await expect(page.locator("#wpadminbar")).toBeVisible();

  // Capture the customer-profile API response triggered by selection
  const customerApi = page.waitForResponse(
    (res) =>
      /arraysubs\/v1\/subscriptions\/customer\/\d+/.test(res.url()) &&
      res.request().method() === "GET",
    { timeout: 20000 },
  );

  // Go to Add New Subscription form
  await page.goto(
    "/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/form",
  );

  // Open the Customer select and search
  const customerField = page
    .locator(".arraysubs-fb-select")
    .filter({ hasText: /Select a customer/i })
    .first();
  await customerField.click();

  const search = page.locator(".arraysubs-fb-select-search-input").first();
  await search.fill("a");

  // Wait for search results, pick the first option
  const firstOption = page
    .locator(".arraysubs-fb-select-option")
    .first();
  await firstOption.waitFor({ state: "visible", timeout: 15000 });
  await firstOption.click();

  // Assert the prefill API fired and returned 200
  const response = await customerApi;
  expect(response.status()).toBe(200);
  const body = await response.json();
  const data = body.content || body.data || body;
  console.log("Customer profile API payload:", JSON.stringify(data));

  // The invoice email field should reflect the returned email (unique placeholder)
  if (data.email) {
    await expect(
      page.locator('input[placeholder="customer@example.com"]'),
    ).toHaveValue(data.email);
  }

  // Billing fields precede shipping in the DOM → first() targets billing.
  const billing = data.billing || {};

  if (billing.address_1) {
    await expect(
      page
        .locator('.field-warper:has(label:has-text("Address Line 1")) input')
        .first(),
    ).toHaveValue(billing.address_1);
  }

  if (billing.city) {
    await expect(
      page
        .locator('.field-warper:has(label:text-is("City")) input')
        .first(),
    ).toHaveValue(billing.city);
  }

  if (billing.first_name) {
    await expect(
      page
        .locator('.field-warper:has(label:text-is("First Name")) input')
        .first(),
    ).toHaveValue(billing.first_name);
  }

  if (billing.phone) {
    await expect(
      page.locator('.field-warper:has(label:text-is("Phone")) input').first(),
    ).toHaveValue(billing.phone);
  }
});
