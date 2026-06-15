import process from "node:process";
import { newSession, loginAdmin, expect, skip } from "../lib/ab.mjs";

export const name = "selecting a customer auto-fills billing/shipping on the add subscription form";

export async function run() {
  if (!process.env.QA_ADMIN_PASS) throw skip("Set QA_ADMIN_PASS before running.");

  const page = newSession("autofill", { viewport: { width: 1440, height: 1000 } });
  loginAdmin(page);
  expect(page.exists("#wpadminbar")).toBe(true);

  // Go to the Add New Subscription form (SPA hash route).
  page.goto("/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/form");
  page.waitSelector(".arraysubs-fb-select");
  page.waitTimeout(1500);

  // Hook fetch + XHR so we can read the customer-profile API response body that
  // the selection triggers (agent-browser drives via CLI, so we capture in-page).
  page.evaluate(() => {
    window.__qaCustomerProfile = null;
    const re = /arraysubs\/v1\/subscriptions\/customer\/\d+/;
    const origFetch = window.fetch;
    window.fetch = function (...args) {
      return origFetch.apply(this, args).then((res) => {
        try {
          const url = (args[0] && args[0].url) || String(args[0] || "");
          if (re.test(url) && res.ok) {
            res
              .clone()
              .json()
              .then((j) => {
                window.__qaCustomerProfile = j;
              })
              .catch(() => {});
          }
        } catch (_) {}
        return res;
      });
    };
    const origOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function (method, url, ...rest) {
      this.__qaUrl = url;
      return origOpen.call(this, method, url, ...rest);
    };
    const origSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.send = function (...a) {
      this.addEventListener("load", () => {
        try {
          if (re.test(this.__qaUrl || "") && this.status >= 200 && this.status < 300) {
            window.__qaCustomerProfile = JSON.parse(this.responseText);
          }
        } catch (_) {}
      });
      return origSend.apply(this, a);
    };
  });

  // Open the Customer select (the .arraysubs-fb-select showing "Select a customer").
  const opened = page.evalJson(() => {
    const els = Array.from(document.querySelectorAll(".arraysubs-fb-select"));
    const hit = els.find((e) => /select a customer/i.test(e.textContent || ""));
    if (!hit) return false;
    hit.setAttribute("data-qa-customer", "1");
    return true;
  });
  expect(Boolean(opened)).toBe(true);
  page.click('[data-qa-customer="1"]');

  // Search and pick the first option.
  page.fill(".arraysubs-fb-select-search-input", "a");
  page.waitSelector(".arraysubs-fb-select-option");
  page.waitTimeout(800);
  page.click(".arraysubs-fb-select-option");

  // Wait for the prefill API to resolve and fields to populate.
  page.waitTimeout(2500);
  const body = page.evalJson(() => window.__qaCustomerProfile);

  // The prefill API must have fired and returned a payload (equivalent to the old 200 check).
  expect(Boolean(body)).toBe(true);
  const data = (body && (body.content || body.data || body)) || {};
  console.log("Customer profile API payload:", JSON.stringify(data));

  // Read an input's value by its field label (resolves in-page; avoids non-standard
  // :has-text / :text-is engine selectors).
  const valueForLabel = (label, exact) =>
    page.evalJson(
      (a) => {
        const warps = Array.from(document.querySelectorAll(".field-warper"));
        const w = warps.find((x) => {
          const lab = x.querySelector("label");
          if (!lab) return false;
          const t = (lab.textContent || "").trim();
          return a.exact ? t === a.label : t.includes(a.label);
        });
        if (!w) return null;
        const inp = w.querySelector("input");
        return inp ? inp.value : null;
      },
      { label, exact },
    );

  // The invoice email field should reflect the returned email (unique placeholder).
  if (data.email) {
    expect(page.getValue('input[placeholder="customer@example.com"]')).toBe(data.email);
  }

  // Billing fields precede shipping in the DOM → the first match targets billing.
  const billing = data.billing || {};
  if (billing.address_1) expect(valueForLabel("Address Line 1", false)).toBe(billing.address_1);
  if (billing.city) expect(valueForLabel("City", true)).toBe(billing.city);
  if (billing.first_name) expect(valueForLabel("First Name", true)).toBe(billing.first_name);
  if (billing.phone) expect(valueForLabel("Phone", true)).toBe(billing.phone);
}
