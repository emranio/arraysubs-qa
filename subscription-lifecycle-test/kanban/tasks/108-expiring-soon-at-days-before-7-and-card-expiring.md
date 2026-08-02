---
id: 108
title: 'Expiring-soon at days_before=7 and card-expiring: two emails nothing can trigger'
status: todo
priority: medium
created: 2026-08-02T03:43:12.000273197+02:00
updated: 2026-08-02T03:43:23.390013085+02:00
tags:
    - email
    - day-08
    - has-conflicts
due: "2026-08-10"
estimate: 1h
depends_on:
    - 107
class: standard
---

> **SLT-EML-10** · group `emails` · scheduled **D08** (2026-08-10)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-12`, `SLT-EML-13`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

**`medium` · action-scheduler policy / broad-fire risk** — with `SLT-LIFE-04`, `SLT-EML-01`, `SLT-LIFE-01`, `SLT-ADM-05`, `SLT-SETUP-99`

- *Problem:* No task in the index issues a bare `wp action-scheduler run --hooks=<hook> --force`, so the largest hazard the audit named is currently absent - but the 'D8 is the only authorized Action Scheduler day' rule is broken by tasks that legitimately need to run one action: SLT-LIFE-04 step 9 hand-schedules HOOK_SEND_EXPIRING_SOON and runs it by id on D3 (2026-08-05) - which is also SLT-SYN-04's exclusive bracket day; SLT-EML-01 step 8 queues a duplicate reminder action on D3 and lets wp-cron claim it; SLT-ADM-05/ADM-03 depend on cron claiming their legs on D3/D4. Residual broad-fire risks that DO exist: (a) SLT-LIFE-01 back-dates S5's legs and relies on the per-minute runner, whose batch will claim any other action already due in that same tick; (b) SLT-EML-10 schedules HOOK_SEND_EXPIRING_SOON at time()-60; (c) SLT-SETUP-99's step 7 cancels pending actions found by searching the Scheduled Actions screen, which can match non-SLT rows; (d) SLT-ADM-01's bulk 'Delete Permanently' path issues DELETE wp/v2/arraysubs_data/<id>?force=true per selected id with no onDeleteCheck guard - one accidental confirm force-deletes irrecoverably.
- *Required fix:* Refine the rule into three tiers and publish it in the README isolation contract. (1) BANNED on every day, no exceptions: any `wp action-scheduler run` without a specific action id, and any `--hooks=` drain. (2) PERMITTED on any day: running ONE action by id from Tools -> Scheduled Actions, and queueing a single-subscription action and letting the per-minute cron claim it - provided the task first screenshots the Pending queue for the next 60 minutes and aborts if any non-SLT action is due. (3) D8 ONLY: editing _next_payment_date / _end_date / _renewal_scheduled_date to move an event in time, always paired with the 13 non-SLT _next_payment_date before/after proof. Under this rule LIFE-04 step 9, EML-01 step 8, EML-10 and ADM-05/03 are legal where they are; LIFE-01 and SETUP-99 stay on D8/D10 with the pre-flight. For SETUP-99, replace 'search and cancel' with 'cancel by action id, taken from the per-subscription action-id metas recorded in the registry'. For SLT-ADM-01, keep the bulk dialog cancelled and file the missing-guard finding as a bug, as authored.

**`medium` · impossible-timing / single-day contention** — with `SLT-LIFE-01`, `SLT-SW-02`, `SLT-SYN-10`, `SLT-EML-08`, `SLT-EML-14`, `SLT-DUN-05`

- *Problem:* D8 (2026-08-10) is the single authorized time-travel day and six tasks are stacked on it, each of which demands exclusive control of the pending Action Scheduler queue: SLT-SYN-10 (runs one month-renewal action by id and must prove no non-SLT date moved), SLT-SW-02 Leg B (hand-set _end_date + expire), SLT-EML-08 (expects an empty pending queue for its own _end_date write), SLT-EML-10 (queues an expiring-soon action in the past and runs it), SLT-LIFE-01 (back-dates S5's legs twice and leaves the queue empty for up to 3h waiting for the recovery sweep), SLT-EML-14 (read-only sweep whose whole value is that nothing moved). Each takes its own 'abort if a non-SLT action is due within 24h' pre-flight, and each would abort on the others' queued work. Run in any order but the right one, they invalidate each other's proofs.
- *Required fix:* Fix a strict D8 running order in the calendar and make it a precondition line in each body: (0) SLT-TT-00 pre-flight - one shared pending-queue screenshot plus the 13 non-SLT _next_payment_date snapshot, published to the registry and quoted by every other D8 task instead of re-taken; (1) SLT-TT-00 executes the month seg1/seg2 + week seg3 + flex-variable-tail renewals; (2) SLT-SYN-10 (month overflow, one action by id); (3) SLT-SW-02 (Leg A downgrade, then Leg B expiry auto-downgrade); (4) SLT-EML-08 (observes SW-02 Leg B; reactivates S_EML); (5) SLT-EML-10 (expiring-soon + card-expiring probes; cancels S_EML at teardown); (6) SLT-LIFE-01 (late-renewal phases A and B on S5 - last, because Phase B deliberately leaves S5 with zero legs and a past date for up to 3h); (7) SLT-EML-14 (read-only negative sweep, after everything). Close the day with the shared post-drain non-SLT diff.

---
## Objective
Exercise the two emails no ArraySubs code path reaches on its own: `expiring_soon` at `days_before=7` — class, template, handler and settings exist, yet **nothing schedules `arraysubs_send_expiring_soon`** (B1, REF-05 §3) — and `card_expiring`, which fires only from a Stripe `customer.source.expiring` webhook (`StripeDelegate.php:106,1370-1389`). Both are driven by hand, the gap filed.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt-eml`)
- Plugins: both

## Preconditions
- SLT-EML-08 ran earlier today, leaving `S_EML` **active** with a valid `_next_payment_date`.
- **D8 = 2026-08-10, the only authorized Action Scheduler day** (C07/C17). Run actions **by id**; `arraysubs_send_expiring_soon` has no other actions site-wide — verify first.
- `emails.expiring_soon.enabled=true`, `days_before=7`; `emails.card_expiring.*` is absent from defaults so it resolves enabled (`email-helpers.php:50-80`).

## Test data
| Item | Value |
|---|---|
| Subscription | `S_EML` (SLT Daily Core, `slt-eml`) |
| Target | `_end_date` if valid, else `_next_payment_date` (`EmailManager.php:925-936`) |

## Steps
1. `wp action-scheduler list --hooks=arraysubs_send_expiring_soon --allow-root` (all statuses) — the empty result is the B1 evidence; screenshot the same search in Tools → Scheduled Actions.
2. `mailpit-agent latest-id` → `$E1`. WP root: `wp eval '\ArraySubs\Supports\ActionScheduler::scheduleSingle(\ArraySubs\Supports\ActionScheduler::HOOK_SEND_EXPIRING_SOON, time()-60, [<S_EML>], \ArraySubs\Supports\ActionScheduler::GROUP_EMAILS);' --allow-root`
3. Re-list the hook: exactly one pending action, args `[<S_EML>]`. Run **that action by id** from Tools → Scheduled Actions.
4. `wait-new "$E1" 120 "is ending soon"`; `text latest` — it renders the shared `customer-renewal-reminder.php` body.
5. `wp post meta list <S_EML> --keys=_arraysubs_expiring_soon_sent_for,_arraysubs_expiring_soon_sent_at --allow-root`.
6. Dedupe probe: `latest-id` → `$E2`; repeat steps 2-3 unchanged; `wait-new "$E2" 60 "is ending soon"` must time out (exit 124).
7. `latest-id` → `$E3`. Fire the gateway action directly: `wp eval "do_action('arraysubs_card_expiring', <S_EML>, ['object'=>'card','last4'=>'4242','exp_month'=>8,'exp_year'=>2026], 'stripe');" --allow-root`.
8. `wait-new "$E3" 60 "Update the card for subscription"`; `text latest`; follow the payment-method link as `slt-eml` and confirm it resolves to `S_EML`.
9. File `issues/SLT-EML-10-unreachable-emails.md`: (a) nothing schedules the hook, so `emails.expiring_soon.*` is dead config and `days_before` only feeds the dedupe key (`EmailManager.php:900`); (b) `card_expiring` needs a live webhook and has no settings toggle.
10. Teardown: `latest-id` → `$E4`; admin → `S_EML` → **Status = Cancelled** → Update; `wait-new "$E4" 90 "been cancelled"`.

## Expected results
1. Before step 2, zero `arraysubs_send_expiring_soon` actions exist in any status — B1 confirmed here.
2. One `[mirror-help.arrayhash.com] Your subscription #<S_EML> is ending soon` to `slt-eml@example.test`, from the renewal-reminder template, no tax line.
3. Dedupe meta `_arraysubs_expiring_soon_sent_for` = `<_next_payment_date>|7`, `_sent_at` set.
4. The second run sends nothing (dedupe on the unchanged key) — `wait-new` exits 124.
5. One `[mirror-help.arrayhash.com] Update the card for subscription #<S_EML>` to `slt-eml@example.test` with a working payment-method link; no order, no status change.
6. Neither email has a reachable ArraySubs toggle → issue filed; teardown sends the cancelled pair and touches nothing else.

## Emails expected
| # | Email | Trigger | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | expiring_soon | step 3 | slt-eml | `#<S_EML> is ending soon` | `wait-new "$E1" 120 "is ending soon"` |
| 2 | NONE EXPECTED — 2nd expiring_soon | step 6 | — | `is ending soon` | `wait-new "$E2" 60` exits 124 |
| 3 | card_expiring | step 7 | slt-eml | `Update the card for subscription #<S_EML>` | `wait-new "$E3" 60 "Update the card"` |
| 4 | subscription_cancelled + admin copy | step 10 | slt-eml + admin | `has been cancelled` / `cancelled by` | `wait-new "$E4" 90 "cancelled"` |

## Evidence to capture
- `SLT-EML-10-01-no-hook.png`, `-02-expiring-soon.png`, `-03-card-expiring.png`, `-04-cancelled.png`; AS ids, Mailpit ids, steps 1/5 output, issue path.

## Pass criteria
- [ ] Zero pre-existing `arraysubs_send_expiring_soon` actions, recorded as B1 evidence
- [ ] Expiring-soon mail, exact subject, `<target>|7` dedupe meta; second run sends nothing
- [ ] Card-expiring mail, exact subject, working update link
- [ ] Issue filed for both unreachable paths; cancellation pair on teardown

## Isolation / teardown
- Only `S_EML` is touched; the `_arraysubs_expiring_soon_*` metas stay as evidence. Ends with `S_EML` cancelled, so nothing from the emails group renews into D9-D12; note it in the registry.
- Restores: no global setting written, no bare hook drain, sessions closed.

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
