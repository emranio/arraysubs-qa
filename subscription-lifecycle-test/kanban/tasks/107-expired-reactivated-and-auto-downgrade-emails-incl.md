---
id: 107
title: Expired, reactivated and auto-downgrade emails, incl. the expiry-suppression negative
status: todo
priority: high
created: 2026-08-02T03:43:11.922944538+02:00
updated: 2026-08-02T03:43:23.307860197+02:00
tags:
    - email
    - day-08
    - has-conflicts
due: "2026-08-10"
estimate: 1h 30m
depends_on:
    - 54
    - 6
    - 60
class: standard
---

> **SLT-EML-08** · group `emails` · scheduled **D08** (2026-08-10)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · same-subscription collision / duplicate coverage** — with `SLT-SW-02`, `SLT-SW-03`, `SLT-SW-01`, `SLT-PROD-11`

- *Problem:* Both tasks run on D8 and both drive the on_expire auto-downgrade of a slt-switch plan-ladder subscription. SLT-EML-08 step 5 sets _end_date on 'S_PRO - slt-switch's active Pro subscription' and fires arraysubs_expire_subscription to capture the auto_downgrade email and the expired-suppression negative. SLT-SW-02 Leg B does exactly the same on 'S-BASIC (on Pro since SLT-SW-01)'. There are only two slt-switch ladder subscriptions and SLT-SW-03 (d6) already crossgraded the other one (S-PRO) off Pro onto SLT Plan Peer - at which point Pro's _arraysubs_auto_downgrade_product no longer applies to it and EML-08's leg is unrunnable as written. Whichever task expires the remaining Pro subscription first consumes the other's canvas.
- *Required fix:* Single owner: SLT-SW-02 Leg B owns the hand-set _end_date and the expiry of S-BASIC (which SLT-SW-01 left on SLT Plan Pro). SLT-EML-08 becomes observation-only for that leg - it reads the auto_downgrade mail ('has been changed to SLT Plan Basic'), proves the subscription_expired suppression negative (EmailManager.php:317-322) and confirms S-BASIC re-activated on Basic at $5.00 - and runs strictly after SW-02 in the D8 order. Delete EML-08 steps 4-5 (queue screenshot + _end_date write) and replace with 'quote SLT-SW-02's pre-flight queue screenshot and _end_date timestamp'. Update EML-08's Test data to name S-BASIC, not S_PRO.

**`high` · dependency-gap / unowned purchases** — with `SLT-ADM-07`, `SLT-MYA-04`, `SLT-ADM-08`, `SLT-SW-01`, `SLT-SW-03`, `SLT-SW-02`

- *Problem:* Five purchases that multiple tasks treat as preconditions are owned by no task key in the index - they existed only as free-text 'purchases owned by other groups' rows in the superseded calendar. (a) S_FEE: slt-core's SLT Signup Fee Daily subscription, required by SLT-ADM-07 ('bought D3 by slt-core'), SLT-MYA-04 and SLT-ADM-08 (which refunds and cancels it). (b) S-BASIC and S-PRO: slt-switch's SLT Plan Basic and SLT Plan Pro subscriptions 'bought D4', required by SLT-SW-01, SW-03, SW-02 and SLT-EML-08. (c) SLT Flex Month Segments segment-3 by slt-flex3 on 2026-08-08, required by SLT-SYN-10 (SUB_S3, _next_payment_date 2026-09-30 18:00:00). (d) The D8 time-travel renewals for month segment-1/segment-2, week segment-3 (SLT-SYN-07's tail, due 2026-08-15) and the flex-variable tail - audit C17 mandates one dedicated D8 owner and none exists. (e) SLT-SYN-10 also references SUB_S2 which SLT-SYN-06 does buy, so only seg-3 is missing.
- *Required fix:* Assign explicit owners. Add step 0 to SLT-ADM-07: 'slt-core buys SLT Signup Fee Daily on D3 after 12:00 (order + subscription ids to the registry)'. Create SLT-SW-00 on D4: 'slt-switch buys SLT Plan Basic and SLT Plan Pro on Stripe after 12:00' as the ladder canvas for SW-01/02/03 and EML-08. Add step 0 to SLT-SYN-10: 'slt-flex3 buys SLT Flex Month Segments on 2026-08-08 (D6) after 12:00 - day-in-cycle 8, past both boundaries, resolves to segment 3, next payment 2026-10-01 00:00 site = 2026-09-30 18:00 UTC'. Create SLT-TT-00 on D8 as the single time-travel owner: pre-flight pending-queue screenshot + the 13 non-SLT _next_payment_date snapshot, then the month seg1/seg2 and week seg3 renewals and the flex-variable tail, single-action-by-id only, then the post-drain non-SLT diff proof - and have SYN-10, SW-02, EML-08, EML-10 and LIFE-01 quote its snapshot instead of each taking their own.

**`medium` · contradictory-expected-result** — with `SLT-LIFE-04`, `SLT-EML-14`, `SLT-PROD-06`

- *Problem:* SLT-LIFE-04 derives from code (OrderIntegration.php:1489-1502) that SLT Fixed Three Cycles stamps _end_date at the moment of the FINAL renewal charge and flips to arraysubs-expired inside that payment - so with a D0 (2026-08-02) purchase the expiry is 2026-08-06, not the catalog's 'expires 6 days after checkout' (which LIFE-04 itself proves is unbacked - arraysubs_calculate_end_date_from_length() has zero callers). SLT-EML-08 states 'S_FIX expired 2026-08-08' and hunts for the 'has expired' message dated 08-08; SLT-EML-14 states 'Fixed Three Cycles renews 08-04 and 08-06 and expires 08-08 (_end_date)'; SLT-PROD-06's title still says 'expires on 2026-08-07' (the pre-shift anchor). Three different dates for one event; EML-08 and EML-14 will both report a missing email.
- *Required fix:* Adopt LIFE-04's code-derived model as authoritative and restate the dates everywhere for D0 = 2026-08-02: renewal #1 2026-08-04, renewal #2 2026-08-06, _end_date = the 08-06 charge moment, status arraysubs-expired 08-06, subscription_expired mail 08-06 PM (readable on watch D5, 2026-08-07). Update SLT-EML-08 step 1 to search 08-06, SLT-EML-14's dated contract, SLT-PROD-06's title and objective, and the watch schedule. LIFE-04's 'file an issue if _end_date is not the final charge moment' stays as the open question.

**`medium` · impossible-timing / single-day contention** — with `SLT-LIFE-01`, `SLT-SW-02`, `SLT-SYN-10`, `SLT-EML-10`, `SLT-EML-14`, `SLT-DUN-05`

- *Problem:* D8 (2026-08-10) is the single authorized time-travel day and six tasks are stacked on it, each of which demands exclusive control of the pending Action Scheduler queue: SLT-SYN-10 (runs one month-renewal action by id and must prove no non-SLT date moved), SLT-SW-02 Leg B (hand-set _end_date + expire), SLT-EML-08 (expects an empty pending queue for its own _end_date write), SLT-EML-10 (queues an expiring-soon action in the past and runs it), SLT-LIFE-01 (back-dates S5's legs twice and leaves the queue empty for up to 3h waiting for the recovery sweep), SLT-EML-14 (read-only sweep whose whole value is that nothing moved). Each takes its own 'abort if a non-SLT action is due within 24h' pre-flight, and each would abort on the others' queued work. Run in any order but the right one, they invalidate each other's proofs.
- *Required fix:* Fix a strict D8 running order in the calendar and make it a precondition line in each body: (0) SLT-TT-00 pre-flight - one shared pending-queue screenshot plus the 13 non-SLT _next_payment_date snapshot, published to the registry and quoted by every other D8 task instead of re-taken; (1) SLT-TT-00 executes the month seg1/seg2 + week seg3 + flex-variable-tail renewals; (2) SLT-SYN-10 (month overflow, one action by id); (3) SLT-SW-02 (Leg A downgrade, then Leg B expiry auto-downgrade); (4) SLT-EML-08 (observes SW-02 Leg B; reactivates S_EML); (5) SLT-EML-10 (expiring-soon + card-expiring probes; cancels S_EML at teardown); (6) SLT-LIFE-01 (late-renewal phases A and B on S5 - last, because Phase B deliberately leaves S5 with zero legs and a past date for up to 3h); (7) SLT-EML-14 (read-only negative sweep, after everything). Close the day with the shared post-drain non-SLT diff.

---
## Objective
Prove three end-of-life emails on real transitions: `subscription_expired` from the natural 2026-08-08 expiry of SLT Fixed Three Cycles, `subscription_reactivated` from a customer reactivation of `S_EML`, and `auto_downgrade` from the `on_expire` downgrade of slt-switch's SLT Plan Pro — plus the verified negative that the expired mail is **suppressed** when a downgrade target exists (`EmailManager.php:317-322`).

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt-eml`, `slt-switch`)
- Plugins: free-only

## Preconditions
- SLT-EML-07 left `S_EML` cancelled; `S_FIX` (SLT Fixed Three Cycles, slt-core) expired 2026-08-08; SLT-PROD-11 set `_arraysubs_auto_downgrade_product` = Basic on SLT Plan Pro; slt-switch holds an active Pro subscription `S_PRO`.
- Frozen baseline: `allow_reactivation=true`; `plan_switching.auto_downgrade_timing=on_expire`.
- **D8 = 2026-08-10 is the only authorized Action Scheduler day** (C07/C17): single targeted actions only, never a bare hook drain. The D8 time-travel task owns the hand-set `_end_date` here; if it already ran, observe instead.

## Test data
| Item | Value |
|---|---|
| Subscriptions | `S_FIX` expired, `S_EML` cancelled, `S_PRO` (Pro $15.00 → Basic $5.00) |
| Gates | `emails.subscription_expired.enabled`, `emails.auto_downgrade.enabled`; `subscription_reactivated` has **no** ArraySubs key — only the WooCommerce → Emails → `[ArraySubs] Subscription Reactivated` checkbox (record it) |

## Steps
1. `mailpit-agent list 200` — find the `has expired` message from 2026-08-08 and `show` it. Confirm recipient, `S_FIX` status `arraysubs-expired`, `_end_date` = 2026-08-08, no admin variant.
2. `latest-id` → `$R1`. Customer session `cust-SLT-EML-08` → `/my-account/subscriptions/` → `S_EML` → **Reactivate**; `wait-new "$R1" 90 "has been reactivated"`; `text latest`.
3. Confirm `S_EML` is `arraysubs-active` with a recomputed `_next_payment_date`; `list 20` shows no `is active` / `New subscription` mail.
4. `wp post meta get <Pro product ID> _arraysubs_auto_downgrade_product --allow-root` = Basic ID. Screenshot Tools → Scheduled Actions **Pending**; abort if a non-SLT action is due within 24 h. Snapshot the 13 pre-existing subscriptions' `_next_payment_date`.
5. `latest-id` → `$R2`. Set `_end_date` on `S_PRO` to now + 2 min (UTC) so `arraysubs_expire_subscription` is queued for `S_PRO` alone, then run **that action by id** from Tools → Scheduled Actions.
6. `wait-new "$R2" 300 "has been changed to"`; `show`; then `list 30`: no `has expired` message for `S_PRO`.
7. Verify `S_PRO` now bills SLT Plan Basic $5.00 (product meta, next payment, note) and re-read the step 4 snapshot — no non-SLT `_next_payment_date` moved.

## Expected results
1. One `[mirror-help.arrayhash.com] Your subscription #<S_FIX> has expired` to `slt-core@example.test` at the 2026-08-08 expiry; no admin copy exists.
2. `[mirror-help.arrayhash.com] Your subscription for SLT Daily Core has been reactivated` to `slt-eml@example.test` once; `S_EML` is `arraysubs-active`; no `new_subscription` (`EmailManager.php:345-349`).
3. `[mirror-help.arrayhash.com] Your subscription #<S_PRO> has been changed to SLT Plan Basic` arrives once, gated by `emails.auto_downgrade.enabled=true`.
4. **No** `subscription_expired` for `S_PRO` — suppression is by design; if one arrives, file a bug citing `EmailManager.php:317-322`.
5. `S_PRO` renews at $5.00 on SLT Plan Basic; no non-SLT schedule moved and no non-SLT action ran.

## Emails expected
| # | Email | Trigger | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | subscription_expired | 2026-08-08 expiry | slt-core | `#<S_FIX> has expired` | `mailpit-agent list 200`, `show` |
| 2 | subscription_reactivated | step 2 | slt-eml | `has been reactivated` | `wait-new "$R1" 90 "reactivated"` |
| 3 | auto_downgrade | step 5 | slt-switch | `has been changed to SLT Plan Basic` | `wait-new "$R2" 300 "changed to"` |
| 4 | NONE EXPECTED — expired mail for `S_PRO`; `new_subscription` on reactivation | steps 2, 5 | — | `has expired` / `is active` | absent from `list 30` |

## Evidence to capture
- `SLT-EML-08-01-expired.png`, `-02-reactivate.png`, `-03-pending-queue.png`, `-04-downgrade.png`; `S_FIX`/`S_EML`/`S_PRO` ids, Mailpit ids, AS action id, both schedule snapshots.

## Pass criteria
- [ ] Expired mail once for `S_FIX`, exact subject, no admin copy
- [ ] Reactivated mail once, `S_EML` active, no `new_subscription`; ungated status noted
- [ ] Auto-downgrade mail once, exact subject, Basic named
- [ ] No expired mail for `S_PRO` (suppression proven)
- [ ] Only the targeted action ran; no non-SLT schedule moved

## Isolation / teardown
- Leaves `S_EML` **active** on purpose — SLT-EML-10 uses it the same day and cancels it. `S_PRO` stays on SLT Plan Basic; record the switch in the registry and hand it to SLT-SETUP-99A.
- Restores: no global setting written; the `_end_date` change on `S_PRO` is intentional and logged; sessions closed.

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
