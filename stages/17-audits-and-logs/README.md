# Stage 17 — Audits, Logs & Troubleshooting (Pro)

**Goal:** Verify the entire Pro audits and troubleshooting toolkit — Activity Audits, Audit Logging Settings, Scheduled-Job Logs, Gateway Health Dashboard, Renewal Failures, Portal Action Failures, and Access-Rule Conflicts. Every task is performed by a human tester clicking through `wp-admin` and the storefront in a real browser. Each task ends with a falsifiable on-screen result tied to a specific UI string, badge, or table row.

**Prerequisites:**
- Stages 00–02 complete (plugins active, settings configured, environment verified).
- Stage 03 catalog and Stage 06 lifecycle data populated — at least one customer with an Active subscription, one with a Cancelled subscription, and at least 30+ existing audit entries so pagination is testable.
- ArraySubs **Pro** active for every task in this stage. If Pro is deactivated, all entry points under **ArraySubs → Audits [beta]** display the placeholder message — note this and stop.
- Stripe gateway is **already configured** in **Test mode** (API keys saved, webhook endpoint already registered in the Stripe dashboard) — no setup required for this stage. Stripe CLI installed locally (or access to "Send test webhook" in the Stripe developer dashboard).
- PayPal and Paddle gateways are **out of scope** for this regression cycle. If their cards render in the Gateway Health Dashboard anyway, verify they show `Not Configured` / `Needs Setup` and skip their sub-tests.
- Test customer accounts:
  - `member1@example.com` — Active Stripe-paid subscription with saved card `4242 4242 4242 4242`.
  - `member3@example.com` — Active subscription that we will switch to declining card `4000 0000 0000 0341` for Task 05.
  - `nonmember@example.com` — registered customer with no subscriptions (used as control).
- Shop Manager test user `shop_manager_qa` created (role `shop_manager`) with a known password — used in Task 01 to confirm role-based author tagging.
- Default browsers: Chrome (primary), Firefox (secondary). Use a private/incognito window when comparing logged-in vs logged-out perspectives.

**Run order:**
1. [01 — Activity audits](01-activity-audits.md) — *(Pro)* Open Activity Audits, verify filters (author role, entity, date range, search), confirm admin/customer/shop-manager actions log correctly.
2. [02 — Audit logging settings](02-audit-logging-settings.md) — *(Pro)* Toggle each entity logging switch (Product, Coupon, Email, Settings) off and on; confirm log rows appear or are suppressed accordingly.
3. [03 — Scheduled-job logs](03-scheduled-job-logs.md) — *(Pro)* Open Scheduled-Job Logs, verify columns, trigger a renewal-invoice generation, confirm Success row appears.
4. [04 — Gateway health dashboard](04-gateway-health-dashboard.md) — *(Pro)* Verify the **Stripe** status card (Connected / Test Mode), Stripe subscription count, last webhook timestamp; trigger a Stripe test webhook and verify the event reaches the webhook event log with pagination working. PayPal and Paddle are out of scope.
5. [05 — Renewal failures troubleshooting](05-renewal-failures-troubleshooting.md) — Trigger a failed renewal with Stripe test card `4000 0000 0000 0341`, verify failure row, "Retry" and "Mark as resolved" actions.
6. [06 — Portal action failures](06-portal-action-failures.md) — *(Pro)* Force a portal cancel error, verify it appears in the troubleshooting screen, resolve it.
7. [07 — Access-rule conflicts](07-access-rule-conflicts.md) — Create overlapping URL and post-meta rules, verify the conflict-detection / resolution UI shows the conflict.

**Exit criteria:**
- All 7 task files signed PASS, or failures recorded with screenshots, browser version, and reproduction steps.
- The exact UI strings recorded verbatim during testing match the user manual:
  - Activity Audits author badges: `System`, `Admin`, `Customer`, `Gateway`.
  - Activity Audits entity badges: `Subscription`, `Member`, `Product`, `Order`, `Coupon`, `Email`, `Settings`, `Other`.
  - Visibility labels: `Private` (lock icon), `Customer` (eye icon).
  - Scheduled-Job Logs status badges: green `Success` (checkmark) and red `Failed` (X icon, red row background).
  - Gateway status values: `Connected`, `Connected (Test Mode)`, `Needs Setup`, `Disabled`, `Unavailable`.
  - Cancellation reason recorded as `overdue_payment` for any system-cancelled subscription created during this stage.
- Stripe webhook test event visible in the **Webhook Event Log** with a `(gateway_slug, event_id)` pair and a parsed event type such as `charge.succeeded` or `payment_intent.succeeded`.
- All temporary audit entries, conflicting rules, and forced-failure subscriptions cleaned up at end of stage so Stage 18 starts with a known-good environment.
- Time and timezone notes recorded — the **Date** column on every screen should match the WordPress site timezone (Settings → General).
