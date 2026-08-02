---
id: 98
title: Pending cancellation with a required reason and declined offers, then customer reactivation
status: todo
priority: high
created: 2026-08-02T03:43:11.218237973+02:00
updated: 2026-08-02T03:43:22.438214187+02:00
tags:
    - plan-switching
    - day-06
    - has-conflicts
due: "2026-08-08"
estimate: 1h 30m
depends_on:
    - 11
    - 12
    - 60
class: standard
---

> **SLT-SW-10** · group `switching` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · shared-global-setting / multi-day deviation vs frozen baseline** — with `SLT-LIFE-03`, `SLT-MYA-01`, `SLT-SW-07`, `SLT-LIFE-02`, `SLT-MYA-03`, `SLT-MYA-04`

- *Problem:* SLT-LIFE-03 flips two global settings out of baseline - skip_renewal.enabled false->true and skip_renewal.cutoff_days 2->0 - and restores them only at its step 7, which happens two days later (after the shifted cycle charges). That is a 2-3 day site-wide deviation in which every customer portal renders a 'Skip Next Renewal' control. Colliding audits: SLT-MYA-01 expected result 5 lists 'Skip Next Renewal' among the five actions an active subscription must expose - which is wrong against the frozen baseline (skip_renewal.enabled=false) and only accidentally right if MYA-01 happens to run inside LIFE-03's bracket. SLT-ADM-03 asserts the opposite ('Skip Renewal is expectedly unavailable'), so the two tasks contradict each other. SLT-SW-07, SLT-SW-10, SLT-LIFE-02, SLT-MYA-03 and SLT-MYA-04 all screenshot the portal Actions card on D5-D7 and would file the Skip control as unexpected UI.
- *Required fix:* Two changes. (1) Correct SLT-MYA-01 expected result 5 to the four baseline actions - Change Plan, Cancel Subscription, Renew Early, Pause Subscription - and add 'Skip Next Renewal MUST be absent (skip_renewal.enabled=false)'; quote the registry WINDOW BASELINE table as C14 requires. (2) Compress LIFE-03's deviation to a single short bracket: settings ON, perform skip / undo / 5-cycle clamp / undo / final 1-cycle skip, settings RESTORED, all inside one <30 min window on D5 with open/close UTC recorded - the pending skip lives in subscription meta (_skip_cycles_remaining, _original_next_payment_date) and completeSkippedCycles() runs off the renewal path, so the setting does not need to stay on for the shifted cycle to complete. Verify that on the day; if completion does prove to require the flag, move LIFE-03 wholesale to D8-D9 where no portal audit runs. Also correct LIFE-03's internal dates: it is a D5 (2026-08-07) task, so D_now = 08-08, skip1 -> 08-09, skip3 -> 08-11, original due 08-08 shows nothing (watch D7 negative) and the shifted $20.00 charge lands 08-09 PM (watch D8) - which also clears 2026-08-10 for SLT-LIFE-01.

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

**`low` · duplicate-coverage** — with `SLT-EML-15`, `SLT-REN-02`, `SLT-ADM-06`, `SLT-EML-03`, `SLT-EML-06`, `SLT-CHK-01`

- *Problem:* Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.
- *Required fix:* Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 keeps only the gating-key and subject-string proof (emails.new_subscription.enabled, admin recipient resolution, the B4 dead-setting verdict) and cites CHK-01 for the checkout. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.

---
## Objective
Exercise cancellation on the real `cancel_immediately=false` setting: required reason, all retention offers declined, an end-of-period **pending cancellation** with its two emails, no renewal invoice in that window, the cancel firing at the exact `_next_payment_date` (not spread), then **reactivation**.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered — **this task creates `slt-cancel`**
- Plugins: free-only

## Preconditions
- SLT-SETUP-02 (`allow_reactivation=true`), SLT-SETUP-03, SLT-PROD-11 done; baseline `cancel_immediately=false`, `require_reason=true`, discount + downgrade offers on.
- Sessions `admin`, `cust-SLT-SW-10`; cart empty first and last. Act on D6 after 12:00 so the cancel lands on D7 in working hours.

## Test data
| Item | Value |
|---|---|
| Product | SLT Plan Basic $5.00 day/1; card 4242 4242 4242 4242 |
| Account | slt-cancel@example.test / `SltQa!2026#Pass` |
| Reason | **Found a better alternative** (`found_alternative`); cancel fires at `_next_payment_date` exactly, no crc32 offset |

## Steps
1. `latest-id` → `MP0`; create `slt-cancel` as in SLT-SW-06 step 2.
2. `cust-SLT-SW-10`: log in, cart empty, `/checkout/?add-to-cart=<Plan Basic ID>`, pay 4242. Record `SUB_ID`, `_next_payment_date` (`CANCEL_AT`), crc32 `OFFSET`.
3. `/my-account/view-subscription/<SUB_ID>/` → **Cancel Subscription**; snapshot the modal (**Continue** disabled until a reason is picked), pick **Found a better alternative** → **Continue**.
4. On **Before You Go...** screenshot each offer card, click **No thanks, continue to cancel**, confirm.
5. Screenshot banner + Status row; `wp post meta list <SUB_ID> --allow-root`, recording `_waiting_cancellation`, `_cancellation_*`, `_cancelled_by`, `_retention_offer_shown`.
6. **Tools → Scheduled Actions**, search `<SUB_ID>`: screenshot the `arraysubs_cancel_subscription` and renewal rows. At `CANCEL_AT − 30 min` (past the 6h lead) confirm no renewal order exists.
7. **Follow-up D7 (2026-08-09) at `CANCEL_AT + 10 min`:** snapshot `latest-id`, reload, screenshot the status, `list 20`.
8. Click **Reactivate Subscription**, confirm, screenshot; re-dump metas and Scheduled Actions. The D8 watch reports if it renewed.

## Expected results
1. **Continue** stays disabled until a reason is picked; only **Stay and Save!** appears (Basic has no downgrade target), yet `_retention_offer_shown` is set.
2. After step 4: still **arraysubs-active**, badge "Pending cancellation", `_waiting_cancellation=1`, `_cancellation_scheduled_date` = `_next_payment_date` exactly, `_cancellation_type=end_of_period`, `_cancelled_by=customer`, reason as chosen.
3. A pending `arraysubs_cancel_subscription` sits at `CANCEL_AT` **with no spread offset** — contrast the renewal legs at `CANCEL_AT ± OFFSET`.
4. No renewal order is created in the window (`blockRenewalDuringPendingCancellation`); no charge is taken.
5. At `CANCEL_AT` the status becomes **arraysubs-cancelled** with `_cancelled_date` set; reactivation returns it to **active**, deleting `_waiting_cancellation`, every `_cancellation_*`, `_cancelled_by`, `_end_date`.
6. **Bug candidate:** reactivation never calls `RenewalScheduler::schedule()` (unlike cancellation-undo), so `_next_payment_date` stays past. Record it and the queue; if no future legs exist, file an issue — the D8 watch shows whether the hourly sweep recovers it.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription, admin copy, Woo mail | step 2 | customer, admin | `is active` | `wait-new MP0` |
| 2 | pending_cancellation + admin copy | step 4 | customer, admin | `scheduled to cancel on` | `wait-new <pre-4>` |
| 3 | subscription_cancelled + admin copy | `CANCEL_AT` | customer, admin | `has been cancelled` | `wait-new <pre-7> 900` |
| 4 | subscription_reactivated | step 8 | slt-cancel | `has been reactivated` | `wait-new <pre-8>` |
| 5 | NONE for declining offers; NONE renewal/invoice mail | step 4, window | — | — | `list 20` — nothing between rows 2 and 3 |

## Evidence to capture
- `SLT-SW-10-01-reason.png`, `-02-offers.png`, `-03-pending.png`, `-04-action.png`, `-05-cancelled.png`, `-06-reactive.png`; `SUB_ID`, `CANCEL_AT`, `OFFSET`, metas, Mailpit ids

## Pass criteria
- [ ] Reason enforced; only the discount offer shown, then declined
- [ ] Pending state exactly as expected result 2
- [ ] Cancel queued at `CANCEL_AT`, no spread offset; no renewal order or charge in the window
- [ ] Cancelled at `CANCEL_AT`; reactivation returns it to active and clears the metas
- [ ] Post-reactivation scheduling recorded (issue if no future legs); emails 1-4 in order, row 5 absent

## Isolation / teardown
- Leaves `slt-cancel` with one reactivated subscription; do not repair its schedule by hand — the unrepaired state is the evidence. SLT-SETUP-99 deletes it.
- No global setting changed; the Reactivate button exists only via the SLT-SETUP-02 baseline, never toggled here.

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
