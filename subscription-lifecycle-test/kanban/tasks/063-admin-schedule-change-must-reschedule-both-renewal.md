---
id: 63
title: Admin schedule change must reschedule both renewal legs; next-payment-date is API-locked
status: done
priority: critical
created: 2026-08-02T03:43:08.611941568+02:00
updated: 2026-08-06T20:33:41.222855197+02:00
started: 2026-08-06T20:33:41.222854375+02:00
completed: 2026-08-06T20:33:41.222854375+02:00
tags:
    - admin
    - portal
    - day-04
due: "2026-08-06"
estimate: 1h30m
depends_on:
    - 5
    - 10
    - 11
    - 12
class: standard
---

> **SLT-ADM-03** · group `admin` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove what an admin schedule change does to the queue: the next payment date is editable nowhere and both REST paths reject it, while Skip & Pause → Vacation Mode moves `_next_payment_date` and must pull *and* restore **both** legs with the spread offset intact.

## Scope
- Gateway: N/A (gateway-less canvas)
- Checkout: N/A
- Account: admin-created (slt-admincreated)
- Plugins: both

## Preconditions
- SLT-ADM-05's D3 create-and-arm setup leg is complete and its recipe/evidence is published (create lands Pending and schedules nothing; **On Hold→Active** arms it). Its card may still be `in-progress` awaiting SUB-A's separate D4 natural charge gate; this task creates independent `SUB-B` and must not wait for or alter SUB-A.
- Frozen baseline: pause enabled, customers may pause, reason required, max 30 days / 2 pauses.
- **Do not** flip `skip_renewal.enabled` (`false`): Skip Renewal is expectedly unavailable and `cutoff_days=2` blocks a day/1 sub anyway. Record it.
- Act after 12:00 site time.

## Test data
| Item | Value |
|---|---|
| Canvas | **SUB-B**, created here: slt-admincreated + SLT Daily Core, $10.00, Day/1 |
| Session | `--session admin-SLT-ADM-03` |

## Steps
1. Record `SUBCOUNT_BEFORE=<exact current SLT subscription count>` and `M0=$(mailpit-agent latest-id)`. In `admin-SLT-ADM-03`, create **SUB-B** at `#/subscriptions/form` exactly as in SLT-ADM-05 steps 2-4, record/validate its numeric ID, require `SUBCOUNT_AFTER == SUBCOUNT_BEFORE + 1`, and inspect the complete `M0` delta to prove zero creation-attributable mail.
2. Arm it with action-specific mail baselines: set `ACTIVATE_PRE=$(mailpit-agent latest-id)`, change **Pending → Active**, and reconcile the new/admin subscription mail; set `HOLD1_PRE=$(mailpit-agent latest-id)`, change **Active → On Hold**, and wait for `is on hold`; set `REACT1_PRE=$(mailpit-agent latest-id)`, change **On Hold → Active**, and wait for `reactivated`. Record `D` with `wp post meta get "$SUB_B" _next_payment_date --allow-root`, and compute **k** from numeric `$SUB_B` with the README argv-based crc32 command.
3. Capture the **before** queue at `admin.php?page=wc-status&tab=action-scheduler&status=pending&s=$SUB_B` as `SLT-ADM-03-02-queue-before.png`: invoice at `D+k−6h`, charge at `D+k`.
4. Negative A: capture every field on `#/subscriptions/edit/$SUB_B` as `SLT-ADM-03-01-edit-no-date-field.png`; no next-payment-date input may exist.
5. Negative B: record `REST_PRE=$(mailpit-agent latest-id)` and pre-probe date/action IDs, then from the WP root run the exact internal REST probe below. It authenticates as the documented admin solely inside this WP-CLI process, interpolates numeric `$SUB_B` into both routes, sends the same `next_payment_date`, and prints each status/data pair. Re-read the unchanged date/action IDs and require zero task-attributable mail in the bounded `REST_PRE` delta:
   ```bash
   wp eval "
   \$admin = get_user_by('login', 'admin');
   wp_set_current_user((int) \$admin->ID);
   foreach (['/arraysubs/v1/subscriptions/$SUB_B/update', '/arraysubs/v1/subscriptions/$SUB_B/manual/update-dates'] as \$route) {
       \$request = new WP_REST_Request('POST', \$route);
       \$request->set_body_params(['next_payment_date' => '2026-09-01 12:00:00']);
       \$response = rest_do_request(\$request);
       printf('route=%s status=%d%s', \$route, \$response->get_status(), PHP_EOL);
       var_export(\$response->get_data());
       echo PHP_EOL;
   }
   " --allow-root
   ```
6. Set `PAUSE_PRE=$(mailpit-agent latest-id)`. On `#/subscriptions/detail/$SUB_B` open **Skip & Pause → Vacation Mode**, set **Duration (Days)** `2` and **Reason** `SLT-ADM-03 probe`, click **Pause Subscription**, confirm; wait for the on-hold mail after `PAUSE_PRE`.
7. Re-read `_next_payment_date`, `_pause_original_next_payment_date`, `_pause_end_date`, `_pause_count` and status; capture the queue as `SLT-ADM-03-03-queue-paused.png`.
8. Set `RESUME_PRE=$(mailpit-agent latest-id)`, click **Resume Now**, confirm, and wait for the reactivated mail after `RESUME_PRE`. Re-read those metas plus both action-ID metas; capture the queue as `SLT-ADM-03-04-queue-after-resume.png`.
9. Record both restored action IDs/GMT values and publish numeric SUB-B, D, k, both gates, and the invoice `gate−5m` baseline deadline to the registry and D04 report. Close only `admin-SLT-ADM-03` and keep the card `in-progress`; run no Action Scheduler command. No earlier than five minutes before the exact D5 invoice gate, publish `ADM03_RENEW_PRE=$(mailpit-agent latest-id)`. **Follow-up on watch day D6 (2026-08-08):** reopen `admin-SLT-ADM-03`, confirm both natural actions complete, resolve the pending renewal order through exact subscription/scheduled-cycle plus reverse relationship rather than recency, capture it as `SLT-ADM-03-05-renewal-order.png`, and reconcile the complete Mailpit delta after `ADM03_RENEW_PRE`. If any live REST/pause/resume/scheduling assertion fails, create a standalone issue with this task/plan, SUB-B/order/action/user IDs and login/role, exact route/context, reproduction, expected/actual, UI/REST/meta/action/mail proof and SUB-A as counterexample; never create a kanban bug card. Close the session, independently review the D4-D6 evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. The Edit screen exposes Status, Invoice Email and addresses only — **no next-payment-date field** — and both REST calls return HTTP **400** `Manual next payment date changes are no longer supported…` (`SubscriptionController.php:812-818`), leaving the date unchanged.
2. After Pause: status `arraysubs-on-hold`; `_pause_original_next_payment_date` = `D`; `_next_payment_date` = `D + 2 days`; `_pause_end_date` ≈ now + 2 days; `_pause_count` = 1.
3. After Pause the queue holds **zero** invoice and charge rows for SUB-B (`PauseManager.php:202`) and **one** `arraysubs_resume_subscription` row (`arraysubs-status`) at `_pause_end_date` — while paused the shifted date has no legs behind it.
4. After Resume Now the same day (`calculateActualPauseDays()`=0): `_next_payment_date` restored to exactly `D`; pause metas cleared; status `arraysubs-active`; resume row gone.
5. Both legs return unchanged — invoice `D+k−6h`, charge `D+k` (±60 s) as in step 3 — with **new** action ids in both metas.
6. **Watch D6**: both actions **Complete** at `D+k−6h` / `D+k`; a `pending` $10.00 renewal order exists.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 0 | new_subscription + admin_new_subscription | step 2 Pending → Active | customer + admin | `is active` / `New subscription #SUB-B` | reconcile after `ACTIVATE_PRE` |
| 1 | subscription_on_hold | step 2 →On Hold **and** step 6 Pause | slt-admincreated | `is on hold` | exactly once after each of `HOLD1_PRE` and `PAUSE_PRE` |
| 2 | subscription_reactivated | step 2 →Active **and** step 8 Resume | slt-admincreated | `has been reactivated` | exactly once after each of `REACT1_PRE` and `RESUME_PRE` |
| 3 | renewal_invoice | invoice leg, 08-07 | slt-admincreated | `Invoice for subscription #SUB-B` | watch D6 complete delta after `ADM03_RENEW_PRE`; save/show exact matched id |
| 4 | NONE EXPECTED | steps 4-5 | — | — | Complete action-specific delta across step 5; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots `SLT-ADM-03-01-edit-no-date-field.png`, `-02-queue-before.png`, `-03-queue-paused.png`, `-04-queue-after-resume.png`, `-05-renewal-order.png`; SUB-B ID/count delta, D/k/meta reads, both REST outputs, exact action/gate/baseline values, Mailpit IDs, relationship-linked renewal order, session/review proof.

## Pass criteria
- [ ] No next-payment-date field; both REST paths return 400 and change nothing
- [ ] Pause shifts the date by 2 days, removes both legs, queues `arraysubs_resume_subscription`; Resume restores the date exactly and re-queues both legs at `D+k−6h` / `D+k`
- [ ] Watch D6: both legs Complete, `pending` renewal order created, no non-SLT action moved
- [ ] Exact D5 baseline handoff, task sessions closed per phase, and final evidence reviewed to done

## Isolation / teardown
- Hands SUB-B, `D` and k to SLT-ADM-04, which runs the status ladder on D6 **before 12:00**; baseline untouched, `skip_renewal.enabled` left `false`. SUB-B is cancelled by SLT-ADM-04, deleted by SLT-SETUP-99B.


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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence and do not force a natural-watch action.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run a bare or `--hooks=` Action Scheduler drain. Run one known action ID at a time only when the task explicitly authorizes it and after the required queue pre-flight; natural-watch actions are never forced.
- Evidence goes under `/home/server-manager/slt-evidence/` using task-key-prefixed filenames.

[[2026-08-06]] Thu 20:15
Missed-window note: not started before the D4 site-local day rolled to 2026-08-07. Do not treat the authored D6 renewal read as valid until this D4 setup leg is actually executed on a valid day first.

[[2026-08-06]] Thu 20:33
UNVERIFIED closeout on 2026-08-06: the required D4 setup leg was not started before the site-local day rolled into 2026-08-07, so the authored D6 renewal read can no longer validate this exact card without a new, explicitly replanned setup cycle.
