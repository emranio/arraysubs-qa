---
id: 34
title: Trial started, trial-ending at days_before=3 and trial converted on SLT Trial Four Day
status: todo
priority: high
created: 2026-08-02T03:43:05.865931879+02:00
updated: 2026-08-02T03:43:16.237103565+02:00
tags:
    - email
    - day-02
    - has-conflicts
due: "2026-08-04"
estimate: 1h 15m
depends_on:
    - 38
    - 12
class: standard
---

> **SLT-EML-09** · group `emails` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session/cart collision (persistent cart)** — with `SLT-CHK-01`, `SLT-CHK-14`, `SLT-LIFE-04`, `SLT-CHK-11`, `SLT-CHK-13`, `SLT-MYA-02`

- *Problem:* Audit C09's fix - one named agent-browser session per task - isolates GUEST carts only. WooCommerce persists a logged-in customer's cart to user meta (_woocommerce_persistent_cart_<blog_id>) and restores it into any session that authenticates as that user. Several tasks therefore share a cart despite having distinct session names: on D0 slt-core is used concurrently by SLT-CHK-01 (cust-SLT-CHK-01), SLT-CHK-14 (core-CHK14) and SLT-LIFE-04 (life04); on D2 slt-trial by SLT-CHK-15 (trial-CHK15) and SLT-EML-09 (cust-SLT-EML-09); on D4/D5 slt-core by SLT-CHK-13 (core-CHK13), SLT-CHK-11 (core-CHK11), SLT-MYA-02 and SLT-ADM-02. A leftover subscription line leaking across sessions makes allow_multiple_in_cart=false reject the next add-to-cart for the wrong reason, or - worse - a two-subscription cart reaches checkout and the wrong subscription is created.
- *Required fix:* Add a standing rule to the isolation contract: never run two tasks concurrently under the same slt-* login, and serialise same-account tasks within a day (the calendar's intra-day ordering is binding, not advisory). Every task that logs in must, as its first browser action after login, assert the cart is EMPTY and treat a non-empty cart as a STOP condition with an issue filed - not as something to silently empty. Add a WP-CLI pre-flight to same-account days: `wp user meta get <uid> _woocommerce_persistent_cart_1 --allow-root` must be empty before the task's checkout, and empty again at teardown.

**`medium` · contradictory-expected-result** — with `SLT-CHK-15`, `SLT-EML-01`

- *Problem:* SLT-CHK-15 expected result 7 requires SLT Trial Four Day's subscription to carry `_renewal_reminder_action_id` due 2026-08-05 (trial end 08-08 minus the 3-day lead) and asserts it exists. SLT-EML-09 step 4 asserts the opposite - 'wp action-scheduler list --hooks=arraysubs_send_renewal_reminder ... | grep <S_TR>' must return nothing - and expected result 3 says 'No pending arraysubs_send_renewal_reminder or arraysubs_send_expiring_soon action for S_TR'. Both tasks buy/attach to the same subscription on D2. One of them will file a bug the other declares correct.
- *Required fix:* Separate the action from the mail. Per SLT-REF-05 / EmailManager.php:806 the reminder handler requires post_status exactly `arraysubs-active`, and a trialling subscription is `arraysubs-trial` - so the ACTION may legitimately be scheduled while the MAIL is legitimately never sent. Restate CHK-15 ER7 as 'an arraysubs_send_renewal_reminder action for S_TR exists at trial_end - 3d + k; record whether it does'. Restate EML-09 ER3 as 'no reminder MAIL for S_TR on 2026-08-05; whether the action exists is recorded, not asserted'. Make the D4 watch row carry both as an explicit paired check.

---
## Objective
Take SLT Trial Four Day through its trial and prove which trial emails exist: `trial_started` at checkout, a "trial ends soon" reminder at `days_before=3`, and `trial_converted` at trial end. Per REF-04 B3 / REF-05 §2 the middle cannot fire: `emails.trial_ending.*` has no class or scheduler, and the reminder handler demands status exactly `arraysubs-active` (`EmailManager.php:806`). B6: the `trial_started`/`trial_converted` enable keys are inert.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt-trial`)
- Plugins: both

## Preconditions
- SLT-PROD-03 published **SLT Trial Four Day** (4-day trial; `trials.require_payment_method=true`).
- This task is the **sole purchaser**; if a checkout task already bought it as `slt-trial`, attach to that subscription.
- Buy after 12:00 site on D2 = **2026-08-04**: the trial then ends 2026-08-08 and the 3-day reminder point is 2026-08-05, inside the window. Session `cust-SLT-EML-09`, cart empty first and last.

## Test data
| Item | Value |
|---|---|
| Product | SLT Trial Four Day `slt-trial-four-day`, $12.00/day after 4 trial days |
| Account | slt-trial / slt-trial@example.test |
| Card | `4242 4242 4242 4242` (card still collected) |
| Dates | start 2026-08-04 PM; trial end 2026-08-08 PM; reminder point 2026-08-05 |

## Steps
1. `mailpit-agent latest-id` → `$T1`. Add to cart, `/checkout/`, Stripe, **Place Order**; $0.00 due today.
2. `wait-new "$T1" 120 "free trial for SLT Trial Four Day has started"`; `text latest`. Record `S_TR`.
3. `wp post meta list <S_TR> --keys=_next_payment_date,_trial_end_date,_arraysubs_trial_started_email_sent --allow-root`; status must be `arraysubs-trial`. `list 20`: no `is active` / `New subscription` mail.
4. `wp action-scheduler list --hooks=arraysubs_send_renewal_reminder --status=pending --allow-root | grep <S_TR>`; same for `arraysubs_send_expiring_soon`. Record `$T2 = latest-id`.
5. **Follow-up D3 (2026-08-05 evening):** `list 50` — no `ends soon` message since `$T2`; file `issues/SLT-EML-09-trial-ending-unwired.md` citing B3, `settings-helpers.php:246-251` and `EmailManager.php:806`.
6. Record `$T3 = latest-id` on 2026-08-07 evening. **Follow-up D6 (2026-08-08)** after trial end + spread offset, and again after 08:00 site on 2026-08-09: bulk conversion runs 02:00 UTC daily (`RecurringBilling/Hooks.php:121-126`) and nothing schedules the per-subscription `arraysubs_process_trial_conversion`.
7. Then `wait-new "$T3" 600 "converted to a paid subscription"` and `list 50`; record status, `_next_payment_date`, the $12.00 renewal order and the mail arrival order.

## Expected results
1. Parent order `$0.00`; `S_TR` is `arraysubs-trial`; `_next_payment_date` = 2026-08-08 at the checkout clock time; guard meta set.
2. Exactly one `[mirror-help.arrayhash.com] Your free trial for SLT Trial Four Day has started` to `slt-trial@example.test`; no `new_subscription` at checkout.
3. No pending `arraysubs_send_renewal_reminder` or `arraysubs_send_expiring_soon` action for `S_TR`.
4. 2026-08-05: no trial-ending mail — an expected FAIL against the shipped setting, filed as an issue, not a tester error.
5. 2026-08-08/09: `S_TR` becomes `arraysubs-active` with a $12.00 renewal order and one `Your trial for SLT Trial Four Day has converted to a paid subscription`.
6. `TrialConverter::convertTrialToActive()` activates with no `initial_payment` context (`TrialConverter.php:104-128`), so `new_subscription` + admin copy are also expected at conversion — record whether they arrived; absence means the renewal path activated it under `_arraysubs_renewal_activation_in_progress` (`EmailManager.php:325-327`).

## Emails expected
| # | Email | Trigger | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | trial_started | step 1 | slt-trial | `free trial for SLT Trial Four Day has started` | `wait-new "$T1" 120 "has started"` |
| 2 | NONE EXPECTED — trial ending | 2026-08-05 | — | `ends soon` | absent from `list 50`; file B3 issue |
| 3 | trial_converted | 2026-08-08/09 | slt-trial | `converted to a paid subscription` | `wait-new "$T3" 600 "converted"` |
| 4 | new_subscription + admin (conditional) | conversion | slt-trial / admin | `is active` | `list 50`, record presence |

## Evidence to capture
- `SLT-EML-09-01-zero-due.png`, `-02-trial-started.png`, `-03-no-ending-0805.png`, `-04-converted.png`; `S_TR` id, order ids, Mailpit ids/baselines, steps 3-4 output.

## Pass criteria
- [ ] $0.00 order, `arraysubs-trial`, next payment 2026-08-08, guard meta
- [ ] One trial-started mail, no signup mail at checkout, no reminder/expiring-soon action for a trial
- [ ] Trial-ending absence proven on 2026-08-05 and filed
- [ ] Conversion mail + $12.00 renewal order; verdict recorded on result 6

## Isolation / teardown
- Register `S_TR`, its 2026-08-08 conversion and the daily $12.00 renewals after it; hand to SLT-SETUP-99A.
- Restores: cart emptied, session closed; no setting changed, no Action Scheduler run.

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
