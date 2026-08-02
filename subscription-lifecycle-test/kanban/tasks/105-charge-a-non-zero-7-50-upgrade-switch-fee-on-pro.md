---
id: 105
title: Charge a non-zero $7.50 upgrade switch fee on Pro→Enterprise and restore the fee to 0 in-task
status: todo
priority: medium
created: 2026-08-02T03:43:11.73831357+02:00
updated: 2026-08-02T03:43:23.157216912+02:00
tags:
    - plan-switching
    - day-07
    - has-conflicts
due: "2026-08-09"
estimate: 1h 30m
depends_on:
    - 87
class: standard
---

> **SLT-SW-08** · group `switching` · scheduled **D07** (2026-08-09)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · same-account-collision / duplicate account creation** — with `SLT-SW-04`, `SLT-SW-06`

- *Problem:* SLT-SW-06 (d5) states 'this task also creates slt-switch2 / slt-switch2@example.test' and buys SLT Plan Basic on it, then upgrades to Pro. SLT-SW-04 (d7) states 'this task CREATES slt-switch2 / slt-switch2@example.test' and buys SLT Plan Basic on it again. SLT-SW-08 (d7) then operates on 'slt-switch2, on SLT Plan Pro from SLT-SW-06'. SW-04 either aborts on a duplicate user, or buys SLT Plan Basic a second time on an account that already holds a Pro subscription from the same ladder - and with auto_migrate_on_checkout the checkout-migration ladder in CheckoutMigrationTrait becomes reachable, silently converting SW-08's Pro subscription instead of creating SW-04's Basic one.
- *Required fix:* SLT-SW-04 creates and uses a distinct account, slt-switch4 / slt-switch4@example.test (Customer, SltQa!2026#Pass, SETUP-03 step 4 billing), registered for 99B deletion. SLT-SW-06 remains the sole creator of slt-switch2 and the sole owner of its subscription; SLT-SW-08 continues to inherit it. Add to SW-04's preconditions: 'slt-switch2 belongs to SLT-SW-06/SLT-SW-08 and must not be reused'.

**`high` · shared-global-setting / same-day bracket collision** — with `SLT-SW-04`, `SLT-SW-02`, `SLT-ADM-01`, `SLT-MYA-04`, `SLT-DUN-05`

- *Problem:* SLT-SW-08 (d7) sets proration.switch_fees.upgrade from 0 to 7.50 globally and restores it in the same task, declaring 'no other SLT switch may run between set and restore'. SLT-SW-04 (d7) performs a Basic->Pro upgrade the same day and asserts its proration order matches SLT-SW-01's record-for-record with 'no switch-fee row'. If SW-04 runs inside SW-08's bracket its order gains a $7.50 'Plan Upgrade switch fee' line and the comparison fails for the wrong reason. The bracket file exists but nothing sequences the two tasks.
- *Required fix:* Fix the D7 order explicitly in the calendar and in both task bodies: SLT-SW-04 completes and its proration order is PAID before SLT-SW-08 opens its bracket. SW-08's step 2 gains a pre-flight assertion: 'SLT-SW-04 is done on the board and no plan_switch order created today is still unpaid'. SW-08's bracket file must record open/close UTC and be posted to the registry so any switch order created inside it can be attributed and re-run.

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

---
## Objective
Prove a non-zero `proration.switch_fees.upgrade` is really charged, appears as its own order line on top of the proration amount, is charged once and never folded into the recurring amount — then restore it to 0 in this task. The baseline has all three fees at 0, so this is a bracketed, self-restoring deviation: no other SLT switch may run between set and restore.

## Scope
- Gateway: Stripe test
- Checkout: N/A (order-pay page only)
- Account: existing — `slt-switch2`, on SLT Plan Pro from SLT-SW-06
- Plugins: free-only

## Preconditions
- **SLT-SW-06 complete and its D6 renewal evidence captured** — this task mutates that subscription.
- Run only after its D7 renewal has fired (`due + OFFSET`), so `_last_payment_date` is fresh and `dr` is near 1.
- Baseline `proration.switch_fees` all 0, `minimum_charge=0`, `rounding_method=round`, `proration_type=prorate_immediately`. Sessions `admin`, `cust-SLT-SW-06`.

## Test data
| Item | Value |
|---|---|
| Products | SLT Plan Pro $15.00 day/1 → SLT Plan Enterprise $30.00 day/1 |
| Account | slt-switch2; card 4242 4242 4242 4242 |
| Setting | `proration.switch_fees.upgrade` 0 → **7.50** → 0 |
| Amounts | proration = round(30×dr,2) − round(15×dr,2); fee 7.50; total = both, where dr = max(0, 1 − round((now − `_last_payment_date`)/86400, 2)) |

## Steps
1. `latest-id` → `MP0`; `wp option get arraysubs_settings --format=json --allow-root > .../SLT-SW-08-priors.json`.
2. **Open the bracket:** write the UTC open time to `SLT-SW-08-bracket.txt` and post to the registry that no other SLT switch may run until it closes.
3. Admin → `#/settings/plan-switching` → **Switch Fees (Optional)** → **Upgrade Fee** = `7.50`; leave the other two at 0; Save; screenshot.
4. Verify `wp option get arraysubs_settings --allow-root | grep -o 'switch_fees.\{0,120\}'`.
5. Record `wp post meta list <SUB_ID> --keys=_last_payment_date,_recurring_amount,_next_payment_date --allow-root` to fix `dr`.
6. `cust-SLT-SW-06`: `/my-account/view-subscription/<SUB_ID>/` → **Change Plan** → **SLT Plan Enterprise** → screenshot **Plan Change Summary** (it must show the fee) → **Confirm Plan Change**.
7. Open the `checkout_url`, record `ORDER_C`, screenshot the line items **before** paying, then pay 4242.
8. Re-open the subscription page, re-dump the metas, screenshot the Recurring Amount row.
9. **Restore:** Upgrade Fee back to `0`, Save, re-grep, `diff` against `SLT-SW-08-priors.json`; write the UTC close time to the bracket file.
10. `mailpit-agent list 20`.

## Expected results
1. After step 3 `switch_fees.upgrade` is `7.5`, the other two still `0`, nothing else differs from the priors.
2. `ORDER_C` is manual/pending, `_arraysubs_order_type=plan_switch`, `_arraysubs_switch_type=upgrade`, with **two** fee lines: `Plan Upgrade to SLT Plan Enterprise - Proration` = round(30×dr,2) − round(15×dr,2), and `Plan Upgrade switch fee` = **$7.50**; total = their sum, no tax.
3. If `dr` is 0 the proration line is omitted and the order is the $7.50 fee alone — record which branch ran.
4. `_arraysubs_proration_data` holds `switch_fee: 7.5` and a `net_amount` that already includes it.
5. After payment `_product_id` = Enterprise and `_recurring_amount` = **30.00** — the fee is neither recurring nor re-charged; `_next_payment_date` is unchanged (day/1 → day/1).
6. After step 9 the settings diff against the priors is empty.
7. The D8 renewal (`due + OFFSET`) charges **$30.00** with no fee line — confirmed by the D9 watch, 2026-08-11.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE for the settings change | steps 3, 9 | — | — | `latest-id` must equal `MP0` immediately after step 4 |
| 2 | NONE for the switch | steps 6-7 | — | — | L6: no listener; only the Woo order mail for `ORDER_C` may appear |
| 3 | payment_successful | D8 renewal | slt-switch2 | `Payment received for subscription #<SUB_ID>` | D9 watch |

## Evidence to capture
- `SLT-SW-08-01-fee-setting.png`, `-02-summary-with-fee.png`, `-03-two-fee-lines.png`, `-04-sub-after.png`, `-05-restored.png`; `SLT-SW-08-priors.json`, the restore diff, the bracket file, `ORDER_C`, the `dr` used, Mailpit ids

## Pass criteria
- [ ] Upgrade fee set to 7.50 with no other setting changed
- [ ] A separate `Plan Upgrade switch fee` line of exactly $7.50 on `ORDER_C`
- [ ] Proration line = round(30×dr,2) − round(15×dr,2), or absent when dr = 0
- [ ] After payment: Enterprise, `_recurring_amount=30.00`, fee not recurring, `_next_payment_date` unchanged
- [ ] Fee restored to 0; settings diff against the priors is empty
- [ ] No ArraySubs mail for the setting change or the switch

## Isolation / teardown
- Restores `switch_fees.upgrade` to 0 in step 9; the bracket file records the window in which the fee was live, so any other task's switch inside it can be attributed and re-run.
- Leaves slt-switch2 active on SLT Plan Enterprise at $30.00/day; SLT-SETUP-99 deletes it with the plan-rung cohort.

---

### Verified environment facts (2026-08-01/02 — do not re-derive)

- **Nothing fires at `_next_payment_date`.** Every scheduled leg is shifted by
  `crc32('arraysubs-spread-'.$subscription_id) % 21600` (0-6 h). Charge fires at `due + offset`,
  invoice at `due + offset - 6h`. The stored date never moves. **Assert a window, not a point.**
- Currency `USD`. **Taxes are OFF** (`woocommerce_calc_taxes = no`) — never assert a tax line.
- Orders use **HPOS** (`wp_wc_orders`), not `wp_posts`.
- `woocommerce_enable_guest_checkout = yes`, but ArraySubs force-requires registration for
  **subscription** carts via `woocommerce_checkout_registration_required`
  (`SubscriptionCheckout/Services/Hooks.php:103`, `CheckoutHelpersTrait.php:93-100`).
- WooCommerce **grouped** products have zero handling in either plugin — grouped tasks are
  exploratory: document behaviour, do not assert a spec.
- WP-Cron runs every minute from `/etc/cron.d/mirror-help-arrayhash-wordpress`. Scheduled actions
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence before forcing.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run `wp action-scheduler run` without `--hooks=`; prefer a single action by ID.
- Evidence goes in `qa/subscription-lifecycle-test/evidence/<TASK-KEY>/`.
