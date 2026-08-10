---
id: 34
title: Trial started, trial-ending at days_before=3 and trial converted on SLT Trial Four Day
status: done
priority: high
created: 2026-08-02T03:43:05.865931879+02:00
updated: 2026-08-05T21:37:49.400939927+02:00
started: 2026-08-05T20:56:04.81311583+02:00
completed: 2026-08-05T20:56:04.81311583+02:00
tags:
    - email
    - day-02
due: "2026-08-04"
estimate: 1h 15m
depends_on:
    - 38
    - 12
    - 10
    - 11
class: standard
---

> **SLT-EML-09** · group `emails` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Observe the exact `SLT Trial Four Day` subscription purchased once by `SLT-CHK-15` and prove which trial emails exist: cite `trial_started` from that checkout, prove no "trial ends soon" mail at `days_before=3`, then capture `trial_converted` at trial end. Per REF-04 B3 / REF-05 §2 the middle cannot fire: `emails.trial_ending.*` has no class or scheduler, and the reminder handler demands status exactly `arraysubs-active` (`EmailManager.php:806`). B6: the `trial_started`/`trial_converted` enable keys are inert.

## Scope
- Gateway: Stripe test
- Checkout: N/A (observation rider; checkout belongs to `SLT-CHK-15`)
- Account: existing (`slt-trial`)
- Plugins: both

## Preconditions
- SLT-PROD-03 published **SLT Trial Four Day** (4-day trial; `trials.require_payment_method=true`).
- `SLT-CHK-15` is the **sole purchaser**, has completed its D2 checkout setup leg, remains `in-progress` for its two dated conversion checks, and has published `S_TR`, its parent order, checkout time, stored Stripe method, action IDs, and trial-started Mailpit ID to the registry. This observation rider deliberately does not depend on task 31 reaching `done`, because the conversion evidence collected here helps close it. Require exactly one `slt-trial` subscription for this product; a second record is contamination and gets a standalone `issues/` file.
- If CHK-15 could not create that sole source subscription, cite its standalone finding, close this rider as `UNVERIFIED (no source S_TR)` through Review to Done, and do not buy a substitute or leave this card blocked.
- The recorded checkout is after 12:00 site on D2 = **2026-08-04**: the trial ends 2026-08-08 and the 3-day reminder point is 2026-08-05, inside the window. This task opens no checkout or customer cart.

## Test data
| Item | Value |
|---|---|
| Product | SLT Trial Four Day `slt-trial-four-day`, $12.00/day after 4 trial days |
| Account | slt-trial / slt-trial@example.test |
| Checkout source | `SLT-CHK-15` classic-checkout evidence; no card entry here |
| Dates | start 2026-08-04 PM; logical trial end 2026-08-08 PM; reminder action gate `trial_end−3d+k`; charge gate `trial_end+k` |
| Session | `mail-SLT-EML-09` (read-only Mailpit evidence) |

## Steps
1. Resolve `S_TR`, parent order, checkout timestamp, offset, stored method, action IDs, and trial-started Mailpit ID from the `SLT-CHK-15` registry hand-off. Assign the numeric subscription ID to shell variable `S_TR`, abort unless `[[ "$S_TR" =~ ^[0-9]+$ ]]`, then re-query owner/product IDs and require exactly one matching subscription. Do not add anything to a cart and do not place an order.
2. `mailpit-agent show <trial-started-id>` and `mailpit-agent text <trial-started-id>`; cite the `SLT-CHK-15` checkout screenshot. Open the exact message in `mail-SLT-EML-09` at the local Mailpit UI and capture `SLT-EML-09-02-trial-started.png`. `wp post meta list "$S_TR" --keys=_next_payment_date,_trial_end_date,_arraysubs_trial_started_email_sent --allow-root`; status must be `arraysubs-trial`. In the checkout-time delta there is no `is active` / `New subscription` mail. Close only `mail-SLT-EML-09` after this D2 read; do not set the reminder-mail baseline a day early.
3. `wp db query "SELECT action_id,hook,status,scheduled_date_gmt,args FROM wp_actionscheduler_actions WHERE hook IN ('arraysubs_send_renewal_reminder','arraysubs_send_expiring_soon') AND JSON_UNQUOTE(JSON_EXTRACT(args,'\$[0]'))='$S_TR' ORDER BY action_id;" --allow-root`; record whether the reminder action exists. If it exists, its timestamp is the exact D3 gate. If it does not, compute the theoretical `trial_end−3d+k` gate and use that exact timestamp for the negative observation. Publish the selected gate and its `−5m` baseline deadline to the registry and D02 watch report; action presence remains observational.
4. **Follow-up at that exact gate (normally D3 but allowed to cross local midnight):** no earlier than five minutes before it, set and publish `T2=$(mailpit-agent latest-id)`. After the gate inspect the complete bounded delta and prove there is no `ends soon` message. Reopen `mail-SLT-EML-09`, show the exact subscription search/delta, capture `SLT-EML-09-03-no-ending-0805.png`, and close the session. Only after the live proof, file `issues/SLT-EML-09-trial-ending-unwired.md` citing B3 and the suite-local reference notes; include this task/plan path, `S_TR`, parent order, user ID/login/role, exact Mailpit UI/test context, reproduction timeline, expected/actual result, action/mail proof, and the two-day-trial counterexample. Do not inspect product source.
5. No earlier than five minutes before the recorded `trial_end+k` charge gate, set and publish `T3=$(mailpit-agent latest-id)`. **Follow-up D6/D7 (2026-08-08/09):** observe that exact charge gate, then—only if the subscription is still `arraysubs-trial`—observe the next recurring `arraysubs_process_trial_conversions` sweep at **02:00 site time**. The singular `arraysubs_process_trial_conversion` has no scheduler or handler (SLT-REF-02).
6. Read the complete Mailpit delta from `$T3`; do not block solely on a `converted to a paid subscription` subject. Record status, `_next_payment_date`, and mail arrival order. Resolve the $12.00 renewal order through the exact HPOS `_subscription_id=$S_TR` relationship and its scheduled-date/cycle metadata, cross-check the reverse subscription/order link, and never select it by recency. Record the exact activation path. Require `trial_converted` only if the bulk `TrialConverter` path performed activation; if the renewal-payment path activated first, require `payment_successful` and record the absence of `trial_converted` as that path's observed contract. Reopen `mail-SLT-EML-09`, capture the exact path-specific Mailpit/search evidence as `SLT-EML-09-04-converted.png`, and close the session.
7. Cite the D5 settled watch evidence for `SLT Free Signup Daily`'s $8.00 paid transition, attach this task's $12.00 transition evidence to `SLT-CHK-15`, self-review both cards, and move `SLT-EML-09` and then `SLT-CHK-15` through Review to Done. Never leave either card in Review; verify `mail-SLT-EML-09` is closed.

## Expected results
1. The sole parent order cited from `SLT-CHK-15` is `$0.00`; `S_TR` is `arraysubs-trial`; `_next_payment_date` = 2026-08-08 at the checkout clock time; guard meta set.
2. Exactly one `[mirror-help.arrayhash.com] Your free trial for SLT Trial Four Day has started` to `slt-trial@example.test`; no `new_subscription` at checkout.
3. Record whether `arraysubs_send_renewal_reminder` was scheduled for `S_TR`; its presence is valid for the trial boundary. No `arraysubs_send_expiring_soon` action is expected. The binding assertion is that the trial-status guard prevents a reminder mail on 2026-08-05.
4. At the exact reminder action gate: no trial-ending mail — an expected FAIL against the shipped setting, written as a standalone markdown file under `issues/`, not a tester error or lifecycle-board card.
5. At `trial_end+k` or the next 02:00 site-time bulk sweep: `S_TR` becomes `arraysubs-active` with a $12.00 paid order. The exact site date and activating path are evidence, not assumptions.
6. If the bulk converter activates it, require `trial_converted` and record whether the signup pair also arrived. If the renewal-payment path activates it first, require `payment_successful`; `trial_converted` and the signup pair are then observational rather than mandatory.

## Emails expected
| # | Email | Trigger | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | trial_started | `SLT-CHK-15` checkout | slt-trial | `free trial for SLT Trial Four Day has started` | show/text the handed-off Mailpit ID |
| 2 | NONE EXPECTED — trial ending | exact `trial_end−3d+k` action gate | — | `ends soon` | absent from the Mailpit delta; write the B3 standalone file under `issues/` |
| 3 | trial_converted (conditional on bulk activation) | next 02:00 site-time bulk sweep if still trial | slt-trial | `converted to a paid subscription` | Mailpit delta from `$T3` |
| 4 | payment_successful (conditional on renewal-path activation) | exact `trial_end+k` charge gate | slt-trial | `Payment received for subscription #<S_TR>` | Mailpit delta from `$T3` |
| 5 | new_subscription + admin (observational) | activation | slt-trial / admin | `is active` | complete delta after T3; record presence by exact S_TR / `To:` |

## Evidence to capture
- Cite `SLT-CHK-15`'s zero-due/classic-checkout screenshot and capture `SLT-EML-09-02-trial-started.png`, `-03-no-ending-0805.png`, `-04-converted.png`; `S_TR` id, order ids, Mailpit ids/baselines, steps 2-3 output.

## Pass criteria
- [ ] Exactly one inherited $0.00 order/subscription; this task placed no order; `arraysubs-trial`, next payment 2026-08-08, guard meta
- [ ] One trial-started mail and no signup mail at checkout; reminder-action presence recorded separately from the no-mail assertion
- [ ] Trial-ending absence proven at the exact reminder action gate and filed
- [ ] $12.00 paid transition observed; activation path and its path-specific mail contract recorded

## Isolation / teardown
- Append the conversion and renewal IDs to the existing `S_TR` registry row and hand it to SLT-SETUP-99A.
- Observation-only: no cart, setting change, or Action Scheduler run. Reopen the sole read-only browser session `mail-SLT-EML-09` only for each named capture and close it immediately after every dated leg.

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

[[2026-08-05]] Wed 20:56
UNVERIFIED (no source S_TR) on 2026-08-05.

Live verification: wp user list confirms slt-trial exists as user ID 348, but the arraysubscription query for that owner returned no rows, so the sole CHK-15 source subscription was never created. Mailpit search on 2026-08-05 found no trial-started, trial-ending, or trial-converted mail for slt-trial@example.test. automation/logs/D03-2026-08-05-afternoon-codex.log also records the authored fixture-absent outcome: required trial checkout cards were not executed; no trial subscription or trial mail exists. Closing this rider per the task precondition instead of leaving it blocked.
