---
id: 47
title: Admin-create a subscription for slt-admincreated and prove it renews unassisted
status: todo
priority: critical
created: 2026-08-02T03:43:06.852737686+02:00
updated: 2026-08-02T03:43:17.418413948+02:00
tags:
    - admin
    - portal
    - day-03
    - has-conflicts
due: "2026-08-05"
estimate: 1h30m
depends_on:
    - 10
    - 11
    - 12
    - 5
class: standard
---

> **SLT-ADM-05** · group `admin` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · shared-global-setting / undeclared exclusive bracket** — with `SLT-EML-12`, `SLT-CHK-09`, `SLT-CPN-04`, `SLT-SYN-14`, `SLT-CHK-05`, `SLT-EML-06`

- *Problem:* SLT-EML-12 (d3) writes the WooCommerce per-email Subject/Heading/Additional content on arraysubs_new_subscription globally, for a bracket it only vaguely bounds ('run after 12:00'). Every new_subscription email site-wide inside that bracket carries the subject 'SLT-EML-12 {customer_first_name} :: sub ...'. Four other D3 tasks place checkouts and gate on the default subject: SLT-CHK-09 ('mailpit-agent wait-new MB09 180 "is active"'), SLT-CPN-04 ('wait-new $M0 120 "is active"', 18:00-19:00), SLT-SYN-14 ('wait-new M0 180', after 12:00), plus SLT-ADM-05's status-change activation on D3. Any of these landing inside EML-12's bracket exits 124 and files a false 'missing email' bug. EML-12's own admin_new_subscription count (expects exactly 3) is also corrupted by any foreign checkout in the bracket.
- *Required fix:* Make EML-12 a declared exclusive bracket, same pattern as SLT-SYN-04's: fixed window 21:00-21:40 site on D3 (2026-08-05), after CPN-04's 18:00-19:00 slot has closed; open/close UTC timestamps written to slt-evidence/SLT-EML-12-bracket.txt and posted to the registry; no other SLT task may place an order, activate a subscription, or run a checkout inside it. Add a pre-flight step: assert no SLT checkout task is in-progress on the board. Apply the identical treatment to SLT-EML-13's admin-email OFF bracket (see separate entry).

**`high` · shared-global-setting / undeclared exclusive bracket** — with `SLT-EML-13`, `SLT-CHK-08`, `SLT-CHK-13`, `SLT-SYN-07`, `SLT-SYN-11`, `SLT-SW-09`

- *Problem:* SLT-EML-13 (d4) disables all four ArraySubs admin emails site-wide for a bracket it bounds only as '08:00-09:00 site, under 20 min'. D4 (2026-08-06) carries the heaviest checkout load of the middle of the window: SLT-CHK-08 places two checkouts, SLT-SYN-11 three, SLT-IMP-03 three, SLT-SW-09 two, plus SLT-CHK-13 and SLT-SYN-07. Every admin_new_subscription for a checkout inside the bracket is silently lost, and those tasks' email tables assert it as present. SLT-ADM-03/ADM-05 also drive status transitions on D4 whose admin notifications would vanish. Conversely, if any of those checkouts drifts into the bracket, EML-13's own 'exactly one message' silence proof is contaminated by their customer mail.
- *Required fix:* Fix the bracket at 08:00-08:20 site on D4 and make it the FIRST thing that happens that day - before any product save, cart, checkout or status change. Add a pre-flight step (already half-present as step 1): screenshot Tools -> Scheduled Actions Pending for the next 2h and abort if any renewal/retry/overdue/cancel action is due, AND assert no SLT checkout task is in-progress on the board. Publish the open/close UTC to the registry. Add 'no checkout before 08:30 site on D4' to the D4 row of the calendar.

**`medium` · action-scheduler policy / broad-fire risk** — with `SLT-LIFE-04`, `SLT-EML-01`, `SLT-EML-10`, `SLT-LIFE-01`, `SLT-SETUP-99`

- *Problem:* No task in the index issues a bare `wp action-scheduler run --hooks=<hook> --force`, so the largest hazard the audit named is currently absent - but the 'D8 is the only authorized Action Scheduler day' rule is broken by tasks that legitimately need to run one action: SLT-LIFE-04 step 9 hand-schedules HOOK_SEND_EXPIRING_SOON and runs it by id on D3 (2026-08-05) - which is also SLT-SYN-04's exclusive bracket day; SLT-EML-01 step 8 queues a duplicate reminder action on D3 and lets wp-cron claim it; SLT-ADM-05/ADM-03 depend on cron claiming their legs on D3/D4. Residual broad-fire risks that DO exist: (a) SLT-LIFE-01 back-dates S5's legs and relies on the per-minute runner, whose batch will claim any other action already due in that same tick; (b) SLT-EML-10 schedules HOOK_SEND_EXPIRING_SOON at time()-60; (c) SLT-SETUP-99's step 7 cancels pending actions found by searching the Scheduled Actions screen, which can match non-SLT rows; (d) SLT-ADM-01's bulk 'Delete Permanently' path issues DELETE wp/v2/arraysubs_data/<id>?force=true per selected id with no onDeleteCheck guard - one accidental confirm force-deletes irrecoverably.
- *Required fix:* Refine the rule into three tiers and publish it in the README isolation contract. (1) BANNED on every day, no exceptions: any `wp action-scheduler run` without a specific action id, and any `--hooks=` drain. (2) PERMITTED on any day: running ONE action by id from Tools -> Scheduled Actions, and queueing a single-subscription action and letting the per-minute cron claim it - provided the task first screenshots the Pending queue for the next 60 minutes and aborts if any non-SLT action is due. (3) D8 ONLY: editing _next_payment_date / _end_date / _renewal_scheduled_date to move an event in time, always paired with the 13 non-SLT _next_payment_date before/after proof. Under this rule LIFE-04 step 9, EML-01 step 8, EML-10 and ADM-05/03 are legal where they are; LIFE-01 and SETUP-99 stay on D8/D10 with the pre-flight. For SETUP-99, replace 'search and cancel' with 'cancel by action id, taken from the per-subscription action-id metas recorded in the registry'. For SLT-ADM-01, keep the bulk dialog cancelled and file the missing-guard finding as a bug, as authored.

---
## Objective
Create a subscription from scratch in wp-admin — no checkout, no gateway — for `slt-admincreated`, prove what the create path omits, arm it so Action Scheduler owns it, and prove it renews unattended.

## Scope
- Gateway: N/A (no gateway — manual payment path)
- Checkout: N/A
- Account: admin-created
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03 and SLT-PROD-01 done; `slt-admincreated` has a billing address and never checks out.
- Act **after 12:00 site time**, clear of the D3 09:00–11:00 SLT-SYN-04 bracket.
- Code facts: the create form has **no Status field**, POSTs `wp/v2/arraysubs_data` as `arraysubs-pending` with no dates (`SubscriptionForm.jsx:449-481`), and `scheduleSubscriptionRenewal()` bails on an empty date (`OrderIntegration.php:1732-1740`).

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core, day/1, $10.00 |
| Account | slt-admincreated / slt-admincreated@example.test |
| Session | `--session admin-SLT-ADM-05`, no card/coupon |

## Steps
1. `mailpit-agent latest-id` → `M0`. Open `.../admin.php?page=arraysubs-mainadmin#/subscriptions/form` → `snapshot -i`.
2. **Customer**: type `slt-admincreated` and select the match.
3. Set **Subscription Product** `SLT Daily Core`, **Quantity** 1, **Recurring Amount** `10.00`, **Billing Interval** `1`, **Billing Period** `Day(s)`, **Subscription Length**/**Signup Fee**/**Trial Length** `0`, **Invoice Email** `slt-admincreated@example.test`, billing address per SLT-SETUP-03.
4. **Create Subscription**; screenshot the `Subscription created successfully!` toast. Record the id as **SUB-A**.
5. Probe (repeat at steps 7 and 8): `wp post get SUB-A --field=post_status`, `wp post meta list SUB-A --keys=_next_payment_date,_start_date,_renewal_action_id,_renewal_invoice_action_id` (`--allow-root`); screenshot `admin.php?page=wc-status&tab=action-scheduler&status=pending&s=SUB-A`.
6. `#/subscriptions/edit/SUB-A` → `Change to...` = **Active** → **Change Status** → confirm.
7. `mailpit-agent wait-new M0 120 "is active"`, then re-run the probe — the load-bearing observation.
8. Arming recipe: change status to **On Hold**, then back to **Active**; re-run the probe.
9. Compute **k** = `crc32('arraysubs-spread-'.SUB-A) % 21600` (SLT-REF-01 §0). Run no `wp action-scheduler` command; close the session.
10. **Follow-up, watch day D3 (2026-08-05):** confirm the overnight renewal (result 5).

## Expected results
1. SUB-A: `post_status = arraysubs-pending`, title `Subscription #<epoch-ms>`, `_customer_id` = slt-admincreated, `_recurring_amount=10`, `_billing_period=day`.
2. At creation: `_next_payment_date`/`_start_date` empty, no action-id metas, zero pending actions.
3. After pending→Active: status `arraysubs-active`, mail sent, **but the date is still empty and zero actions pending**. File an issue — admin-created subscription set Active never renews (`OrderIntegration.php:626-638`).
4. After Active→On Hold→Active: `_next_payment_date` = that moment + 1 day = **D**; two pending rows — `arraysubs_generate_renewal_invoice` (`arraysubs-billing`) at `D+k−6h`, `arraysubs_process_renewal` (`arraysubs-renewals`) at `D+k`, ±60 s; action-id metas set.
5. **Watch D3**: both actions **Complete** inside `[D+k−6h, +5m]` / `[D+k, +5m]`; a renewal order exists with `_is_renewal_order=yes`, `_renewal_cycle_number=1`, `_renewal_scheduled_date=D`, total `$10.00`, status **`pending`** (manual fallback).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription + admin_new_subscription | step 6 | customer + admin | `is active` / `New subscription #SUB-A` | `wait-new M0 120 "active"` |
| 2 | subscription_on_hold | step 8 →On Hold | slt-admincreated | `is on hold` | `wait-new <prev> 120 "on hold"` |
| 3 | subscription_reactivated | step 8 →Active | slt-admincreated | `has been reactivated` | `wait-new <prev> 120 "reactivated"` |
| 4 | renewal_invoice | invoice leg | slt-admincreated | `Invoice for subscription #SUB-A` | watch D3 `list 50`; must send |
| 5 | NONE EXPECTED at creation | step 4 | — | — | `latest-id` = `M0` before step 6 |

## Evidence to capture
- Screenshots `SLT-ADM-05-01-form.png`, `-02-toast.png`, `-03/04-queue-empty.png`, `-05-queue-two-legs.png`; SUB-A id, the probe outputs, k, Mailpit ids, renewal order id.

## Pass criteria
- [ ] SUB-A created `arraysubs-pending` with no dates/actions; pending→Active mails but schedules nothing (issue filed)
- [ ] On Hold→Active sets the date to now+1 day, legs at `D+k−6h` / `D+k`
- [ ] Watch D3: both actions Complete, `pending` $10.00 renewal order, renewal_invoice received, no unexpected mail

## Isolation / teardown
- Hands SUB-A and the arming recipe (pending→Active inert; On Hold→Active arms) to SLT-ADM-02/03.
- Expected tail, not a bug: the unpaid renewal order drives SUB-A to on-hold ~1 day later and cancelled ~3 days after. Record it; do not intervene.
- No setting changed; SUB-A deleted by SLT-SETUP-99B.


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
