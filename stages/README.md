# ArraySubs + ArraySubsPro — End-to-End Regression Test Plan

> Full browser-based regression test plan for the ArraySubs (free) and ArraySubsPro (premium) WooCommerce subscription plugins.
> This is **manual E2E testing** performed by a human QA tester using a real browser against a live WordPress site.
> No unit tests, no Postman tests, no API-only tests. Every step is something a tester clicks, types, or observes in a browser.

---

## How to Use This Plan

1. Start with **Stage 00** and work top-to-bottom. Stages are ordered so that artifacts (settings, products, customers, subscriptions) created in earlier stages are reused by later stages.
2. Open each stage folder and run the task `.md` files in numeric order.
3. Each task file is self-contained: it lists pre-conditions, sub-tasks, expected results, and a sign-off block.
4. Mark the **Pass Criteria** checkbox in each sub-task as you go. If something fails, write a short note in **Fail Notes** and continue — do not skip the rest of the task unless the failure is blocking.
5. At the end of each stage, fill the **Sign-off** block at the bottom of every task file with tester name, date, browser, and notes.

### Conventions used in every task file

- `WP-Admin →` means *click through the WordPress admin sidebar*.
- `WC →` is shorthand for the WooCommerce admin section.
- `Portal →` means *the customer-facing My Account / Subscriptions area*.
- **Bold UI labels** match what is rendered on screen.
- A `Sub-Task` is a discrete check inside a parent task. Each sub-task has its own pass/fail checkbox.
- "Verify in admin" means open a second browser window or incognito session as the admin to confirm what the customer just did.

### Pass / Fail policy

- A sub-task **passes** only when **every** expected result is observed.
- A sub-task **fails** if any expected result is missing, the wrong text appears, the wrong status is set, an email did not arrive, or the page errored.
- Console errors, PHP notices, and 404 / 500 responses always count as a failure even if the visible UI looks fine. Keep DevTools open.

---

## Environment Baseline

Every stage assumes the following baseline. Re-confirm at the start of each session.

### Prerequisite stack

- WordPress (current stable) with permalinks set to **Post name**
- WooCommerce (current stable), installed, activated, and through its store-setup wizard at least once
- ArraySubs (free) installed and activated
- ArraySubsPro (premium) installed and activated
- Default theme that supports WooCommerce (Twenty Twenty-Four or Storefront)
- Server-side cron working (WP-Cron via real cron or `wp cron event run --due-now` available)

### Required test accounts

| Role | Username | Email | Purpose |
|---|---|---|---|
| Admin | `admin` | `admin@test.local` | Configures plugins, creates products, runs admin checks |
| Shop Manager | `shopmgr` | `shopmgr@test.local` | Verifies capability checks |
| Customer 1 | `cust1` | `cust1@test.local` | Subscribes, runs portal actions |
| Customer 2 | `cust2` | `cust2@test.local` | Trial-restriction checks, second-customer scenarios |
| Customer 3 | `cust3` | `cust3@test.local` | Reserved for store-credit / refund / member tests |
| Guest | (none) | `guest@test.local` | Guest checkout scenarios |

### Payment methods in scope for this regression cycle

This pass tests **only Stripe + manual payment**. PayPal and Paddle are **out of scope** for this cycle. Any task that historically referenced them has been rewritten or skipped — the tester does not need PayPal or Paddle sandbox accounts.

- **Manual payment** — WooCommerce **Direct Bank Transfer** (BACS) and **Cash on Delivery** are enabled. These are the fallback "manual" methods used to exercise the core renewal-invoice path that does not depend on a gateway.
- **Stripe test mode** — already configured by the developer before this regression begins. The QA tester does NOT run any Stripe setup task. They only verify Stripe is connected (under **WooCommerce → Settings → Payments**) and use these test cards:
  - `4242 4242 4242 4242` — generic success
  - `4000 0027 6000 3184` — SCA / 3-D Secure challenge
  - `4000 0000 0000 0341` — declines on every renewal (used for failed-renewal grace tests)

### Test product catalog (canonical, used across every stage)

To make renewals feasible inside a real-time regression run, almost every test product uses **days or weeks** for its billing interval. **Exactly one product** uses `month` so that month-interval edge cases (calendar-aware date math) still get coverage.

| # | Product name | Type | Billing | Price | Trial | Notes |
|---|---|---|---|---|---|---|
| 1 | **Basic Monthly** | Simple | every 1 month | $29.99 | – | The single monthly product. Used wherever month-interval behaviour must be checked. |
| 2 | **Standard Weekly** | Simple | every 1 week | $19.99 | – | Canonical reusable subscription. Most lifecycle, portal, and email tests run on this. |
| 3 | **Trial Weekly** | Simple | every 1 week | $19.99 | 7 days | Trial-related tests. |
| 4 | **Signup Fee Weekly** | Simple | every 1 week | $19.99 | – | $4.99 signup fee. |
| 5 | **Stepped Weekly** | Simple | every 1 week | $19.99 → $29.99 after 3 cycles | – | Different-renewal-price tests. |
| 6 | **Lifetime Deal** | Simple | Lifetime | $199 | – | Lifetime / one-time path. |
| 7 | **Fixed-Length Weekly (6 cycles)** | Simple | every 1 week | $24.99 | – | Length-based expiration. |
| 8 | **Limited 3-Cycle Weekly** | Simple | every 1 week | $19.99 | – | Expiring-soon and end-of-term tests. |
| 9 | **PM Tool** | Variable | Weekly $9.99 / Bi-weekly (every 2 weeks) $14.99 | – | – | Variable-product baseline. |
| 10 | **Coffee Plan** | Variable | Daily $2.99 / Weekly $14.99 (with $4.99 signup fee) / Bi-weekly $24.99 | – | varied | Variable + signup-fee + per-variation independence. |
| 11 | **Basic Plan** | Simple | every 1 week | $9.99 | – | Plan ladder — Basic. |
| 12 | **Pro Plan** | Simple | every 1 week | $19.99 | – | Plan ladder — Pro (upgrade target). |
| 13 | **Enterprise Plan** | Simple | every 1 week | $39.99 | – | Plan ladder — Enterprise. |
| 14 | **Crossgrade Plus** | Simple | every 1 week | $19.99 | – | Crossgrade target for Pro. |

**Convention reminder:** when a task says "the simple weekly subscription" it means **Standard Weekly** unless a more specific product is named. When a task says "the monthly subscription" it means **Basic Monthly**.

### Time-travel helpers

Many lifecycle tests need the clock to advance days or hours. Use **one** of these techniques and stay consistent:

- Edit the subscription's `_next_payment_date` post meta from the admin **Custom Fields** panel (preferred — no plugin needed and matches what production uses).
- Run `wp action-scheduler run --hooks=arraysubs_*` from the CLI to drain pending Action Scheduler hooks immediately.
- Use a date-mocking plugin (e.g., **WP Time Capsule** or any plugin that sets `current_time` filters). Disable it immediately after each test to avoid contaminating other stages.

Note any time-travel technique you used in the **Notes** field of the task you ran it in.

---

## Stage Index

Run stages in order. Each stage builds on the artifacts of the previous one.

| # | Stage | Folder | Why it runs here |
|---|---|---|---|
| 00 | Pre-flight & Environment Verification | [00-preflight/](00-preflight/) | Confirm fresh install, plugin activation, menus, cron, mail before any feature work |
| 01 | Easy Setup Wizard | [01-setup-wizard/](01-setup-wizard/) | Run the guided 9-step wizard end to end so all later stages start from sane defaults |
| 02 | General & Toolkit Settings | [02-settings/](02-settings/) | Manually exercise every settings group, save behaviour, and import/export |
| 03 | Subscription Product Creation | [03-products/](03-products/) | Build the catalog used by every checkout, lifecycle, and portal test |
| 04 | Cart Validation Rules | [04-cart-rules/](04-cart-rules/) | Confirm every cart-level guard before any real checkout |
| 05 | Checkout Flow | [05-checkout/](05-checkout/) | Classic + Block checkout, gateways, plan switch, one-click, account creation |
| 06 | Initial Subscription Lifecycle | [06-initial-lifecycle/](06-initial-lifecycle/) | Confirm subscription records and statuses immediately after checkout |
| 07 | Customer Portal Self-Service | [07-customer-portal/](07-customer-portal/) | Cancel, undo, pause, skip, change plan, update payment, etc. |
| 08 | Retention Flow | [08-retention/](08-retention/) | Discount, pause, downgrade, contact-support offers + analytics |
| 09 | Member Access & Restriction Rules | [09-member-access/](09-member-access/) | Role mapping, URL/CPT/discount/ecommerce/download rules, drip, shortcodes |
| 10 | Profile Builder & My Account | [10-profile-builder/](10-profile-builder/) | Custom profile fields, avatar, My Account editor |
| 11 | Feature Manager (Pro) | [11-feature-manager/](11-feature-manager/) | Plan entitlements, customer & storefront display, usage tracking |
| 12 | Store Credit (Pro) | [12-store-credit/](12-store-credit/) | Manual adjustments, history, auto-apply, refund-to-credit, buy credits |
| 13 | Email Notifications | [13-emails/](13-emails/) | Trigger and verify every customer + admin + store-credit email |
| 14 | Admin Subscription Management | [14-admin-subscriptions/](14-admin-subscriptions/) | List, search, create, edit, status change, notes, related orders, export |
| 15 | Manage Members (Pro) | [15-manage-members/](15-manage-members/) | Member search, profile, login-as, member commerce overview |
| 16 | Analytics & Reports | [16-analytics/](16-analytics/) | Performance dashboard, charts, WC Analytics extensions, reports hub |
| 17 | Audits, Logs & Troubleshooting (Pro) | [17-audits-and-logs/](17-audits-and-logs/) | Activity audits, scheduled-job logs, gateway health, troubleshooting screens |
| 18 | Renewal Follow-up & Time-travel | [18-renewal-followup/](18-renewal-followup/) | Successful renewals, failed-payment grace, on-hold, auto-cancel, trial conversion, different renewal price, skip/pause behaviour over time |
| 19 | Refund Management | [19-refunds/](19-refunds/) | Refund settings, prorated refunds, refund-to-credit, post-refund subscription state |
| 20 | Edge Cases & Final Regression | [20-edge-and-regression/](20-edge-and-regression/) | Pro deactivation, plugin deactivation, performance, concurrent actions, cron stoppage |

---

## Coverage Map (User-Manual Topics → Stages)

This map lets the QA team confirm every documented topic is covered by at least one stage.

| User Manual Topic | Covered In |
|---|---|
| Getting Started → Easy Setup Wizard | 01 |
| Getting Started → First-Time Setup, Cron, Import/Export | 00, 02 |
| Settings → General Settings | 02, 04, 06, 07 |
| Settings → Toolkit Settings | 02 |
| Subscription Products → Create & Configure | 03 |
| Subscription Products → Plan Switching & Linked Products | 03, 05, 07 |
| Subscription Products → Product Experience | 03 |
| Subscription Products → Coupons | 03, 05 |
| Subscription Products → Product Lifecycle | 03 |
| Manage Subscriptions → Operations, Detail, Notes, Export | 14 |
| Manage Subscriptions → Lifecycle Management | 06, 18 |
| Customer Portal → Pages, Self-Service, Payment & Shipping | 07 |
| Manage Members (Pro) | 15 |
| Store Credit (Pro) | 12, 19 |
| Member Access & Restriction Rules | 09 |
| Checkout & Payments → Subscription Checkout | 04, 05 |
| Checkout & Payments → Automatic Payments (Pro) | 05, 18 |
| Checkout & Payments → Checkout Builder (Pro) | 05 |
| Billing & Renewals → Renewal Operations, Trials, Grace, Communication | 18, 13 |
| Retention & Refunds → Cancellation, Offers, Analytics, Refunds | 08, 19 |
| Analytics (Pro) | 16 |
| Emails & Notifications | 13 |
| Profile Builder & My Account | 10 |
| Feature Manager (Pro) | 11 |
| Audits & Logs (Pro) | 17 |

---

## Reset / Reuse Strategy

- **Do not reset between stages.** Later stages rely on subscriptions, customers, and orders created earlier.
- If a destructive test is required (e.g., deleting a product mid-cycle), the task file will say so explicitly and the artifact will be re-created at the start of the next stage.
- If you must restart from zero, snapshot the database after Stage 03 (products created) and again after Stage 06 (subscriptions created). Restore to the appropriate snapshot rather than re-running every prior stage.

---

## How to file regressions

For every failure, capture:

1. The exact task file and sub-task number (e.g., `05-checkout/04-trial-checkout.md` → Sub-Task 4.2).
2. Browser + version, viewport size, and whether DevTools console showed errors.
3. A screenshot of the failure state.
4. The expected vs actual result, in one sentence each.
5. Any cron / time-travel actions you ran beforehand.

Save these in your bug tracker against the matching task ID. Do not edit the task `.md` files to record bugs — keep them clean for the next regression cycle.
