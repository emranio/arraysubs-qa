# D06 watch guarantees have no subscriptions from five missed D04 source tasks

- Severity: high
- Date found: 2026-08-08
- Watch day: D06
- Originating test task keys: `SLT-CHK-08`, `SLT-CHK-13`, `SLT-IMP-03`, `SLT-SW-09`, `SLT-SYN-11`
- Plan files:
  - `kanban/tasks/064-existing-active-subscriber-buys-a-second-different.md`
  - `kanban/tasks/065-buy-slt-box-daily-through-the-wizard-contents.md`
  - `kanban/tasks/069-concurrent-renewals-in-one-action-scheduler-window.md`
  - `kanban/tasks/073-retention-accept-the-20-for-3-cycles-discount-and.md`
  - `kanban/tasks/075-flexible-sync-exclusivity-negatives-renewal-price.md`
  - `watch-schedule.md` D06 row

## Affected records

- Subscription IDs: N/A — none of the authored D04 cohort subscriptions exists.
- Parent and renewal order IDs: N/A.
- Existing intended source product IDs:
  - `11927` — `SLT Daily Core`
  - `12577` — `SLT Signup Fee Daily`
  - `12600` — `SLT Box Daily`
  - `12608` — `SLT Plan Basic`
  - `12620` — `SLT Plan Peer`
- Missing `SLT-SYN-11` product IDs: N/A — the three exact probe products were never created.

## Affected users

- `slt-chk-second` / `slt-chk-second@example.test` / intended Customer role — user ID N/A.
- `slt-conc1` / `slt-conc1@example.test` / intended Customer role — user ID N/A.
- `slt-conc2` / `slt-conc2@example.test` / intended Customer role — user ID N/A.
- `slt-conc3` / `slt-conc3@example.test` / intended Customer role — user ID N/A.
- `slt-retain` / intended Customer role — user ID N/A.
- `slt-retain2` / intended Customer role — user ID N/A.
- `slt-excl` / `slt-excl@example.test` / intended Customer role — user ID N/A.
- Existing Box buyer counterpoint: user `347`, `slt-core` / `slt-core@example.test`, Customer. This user exists but owns no `SLT Box Daily` subscription.

## Gateway, checkout, and settings context

- Gateway: Stripe test for all intended purchases; `SLT-SYN-11` additionally expected the normal checkout gateway list to continue offering Paddle because the forced flex meta was ineligible.
- Checkout type: storefront product/cart plus the site's block checkout; the Box path begins in its storefront wizard.
- Non-default settings: none opened by this D06 watch. The D04 tasks were meant to use the frozen suite baseline; `SLT-SW-09` depended on the standing retention-offer baseline. The `SLT-SYN-11` force-meta and Shop Access append never happened because that task never started.

## Routes and browser context

- Detection context: D06 automated early-morning watch, read-only frozen facts and relationship-exact WP-CLI queries.
- Intended customer routes include `/product/slt-daily-core/`, `/product/slt-signup-fee-daily/`, `/product/slt-box-daily/`, `/checkout/`, and `/my-account/` subscription views.
- Intended admin routes include `/wp-admin/user-new.php`, `/wp-admin/post-new.php?post_type=product`, exact HPOS order pages, ArraySubs subscription pages, and Scheduled Actions searches.
- Intended browser sessions: `admin-SLT-CHK-08`, `cust-SLT-CHK-08`, `core-CHK13-SLT-CHK-13`, `admin-SLT-CHK-13`, `admin-SLT-IMP-03`, `customer-conc1-SLT-IMP-03`, `customer-conc2-SLT-IMP-03`, `customer-conc3-SLT-IMP-03`, `admin-SLT-SW-09`, `customer-a-SLT-SW-09`, `customer-b-SLT-SW-09`, `admin-SLT-SYN-11`, and `customer-SLT-SYN-11`.
- No D06 browser session was opened for these absent objects because there was no numeric subscription/order target and recreating a D04 cohort would silently shift the authored lifecycle.

## Reproduction steps

1. Read the appended 2026-08-06 closeout notes on cards `#64`, `#65`, `#69`, `#73`, and `#75`.
2. Observe that every source task was closed `UNVERIFIED` after the site-local D04 execution window elapsed, without authoring a shifted schedule.
3. Read the D06 row of `watch-schedule.md`.
4. Observe that it still unconditionally expects:
   - two `SLT-CHK-08` renewals for `$10.00` and `$9.00`,
   - `SLT Box Daily` renewal #1 for `$10.00`,
   - three `SLT-IMP-03` `$10.00` renewals with distinct spread timestamps,
   - `SLT-SW-09` renewals for `$4.00` and `$15.00`,
   - an `SLT-SYN-11` renews-soon message and trial conversion.
5. Query exact users `slt-chk-second`, `slt-conc1`, `slt-conc2`, `slt-conc3`, `slt-retain`, `slt-retain2`, and `slt-excl`; all seven counts are zero.
6. Query `arraysubs_data` by those exact users; zero subscriptions exist.
7. Query `SLT Box Daily`: product `12600` is published, but the relationship-exact subscription count and its order-line count are both zero.
8. Query the exact three `SLT-SYN-11` probe slugs; the product count is zero.
9. Inspect the D06 action/order/Mailpit snapshot. There are no rows that can be linked to any of the missing fixtures.

## Expected result

The D06 watch should present these renewal/reminder/conversion assertions as conditional on numeric source fixtures, or the D04 source tasks should have produced and registered the required users, orders, subscriptions, action IDs, mail baselines, and exact gates in their authored window.

## Actual result

All five D04 source tasks are closed without the required subscriptions, while the D06 watch row still presents their downstream outcomes as guaranteed. No legitimate action, renewal order, subscription transition, reminder, conversion, or task-specific Mailpit message can be observed. Creating replacement fixtures on D06 would test different dates and violate the calendar rather than repair the missing evidence.

## Concrete proof

- Frozen watch snapshot: `automation/logs/D06-2026-08-08-early-morning-facts.txt` lists all live SLT subscriptions, pending/completed actions, recent orders, and Mailpit messages; none belongs to this cohort.
- Reconciliation transcript: `/home/server-manager/slt-evidence/D06-2026-08-08-early-morning-verification.txt`.
- Source task closeouts: the five task files listed above, each with a 2026-08-06 `UNVERIFIED` note.
- Live 2026-08-08 relationship checks:
  - exact authored new users: `0 / 7` exist;
  - `SLT Box Daily` product `12600`: published, subscriptions `0`, order lines `0`;
  - `SLT-SYN-11` exact probe products: `0 / 3` exist;
  - exact subscriptions for the missing-user cohort: `0`.
- Screenshots, action IDs, order IDs, and Mailpit IDs: N/A because the source fixtures were never created; none was fabricated or substituted.

## Scope notes and counterexamples

- This is a QA-plan/source-execution coverage defect, not evidence that ArraySubs failed to renew a valid subscription.
- Existing issues already cover the separate D02 source conflicts, missing CPN-03 cycle fixtures, missing CHK-12 subscription, and the absent `SLT-SYN-11` lifetime-control assertion. This issue covers only D06 guarantees left by the missed D04 cohort, including the previously uncovered `SLT-SYN-11` reminder/conversion legs.
- Live counterexamples that did renew correctly before this phase are sub `11991` / order `13109` for `$10.00`, sub `12564` / order `13125` for `$18.00`, sub `12039` / order `13170` for `$14.00`, and sub `12749` / order `13174` for `$29.97`.
- No substitute checkout, source recreation, date mutation, scheduler force-run, or non-SLT mutation is permitted.
