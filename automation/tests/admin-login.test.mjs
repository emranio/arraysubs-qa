import process from "node:process";
import { newSession, loginAdmin, expect, skip } from "../lib/ab.mjs";

export const name = "admin login reaches wp-admin";

export async function run() {
  if (!process.env.QA_ADMIN_PASS) throw skip("Set QA_ADMIN_PASS before running live admin login checks.");

  const page = newSession("admin_login");
  loginAdmin(page);

  // Landed in wp-admin.
  expect(page.url()).toMatch(/\/wp-admin\/?/);

  // Admin chrome is rendered for an authenticated admin (presence check —
  // agent-browser's is-visible is stricter for position:fixed elements).
  expect(page.exists("#wpadminbar")).toBe(true);

  // No console errors and no 5xx responses during the login flow.
  const consoleErrors = page.consoleErrors();
  const serverErrors = page
    .networkRequests()
    .filter((r) => Number(r.status) >= 500)
    .map((r) => `${r.status} ${r.url}`);

  expect(consoleErrors).toEqual([]);
  expect(serverErrors).toEqual([]);
}
