---
id: 108
title: 'Expiring-soon at days_before=7 and card-expiring: two emails nothing can trigger'
status: done
priority: medium
created: 2026-08-02T03:43:12.000273197+02:00
updated: 2026-08-10T02:51:37.059391566+02:00
started: 2026-08-10T02:51:36.949367039+02:00
completed: 2026-08-10T02:51:36.949367039+02:00
tags:
    - email
    - day-08
due: "2026-08-10"
estimate: 1h
depends_on:
    - 107
class: standard
---

> **SLT-EML-10** · group `emails` · scheduled **D08** (2026-08-10)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Exercise two otherwise impractical-in-window email paths: `expiring_soon` at `days_before=7` — class, template, handler and settings exist, yet the suite-local reference says **nothing schedules `arraysubs_send_expiring_soon`** (B1, REF-05 §3) — and `card_expiring`, which is legitimately webhook-driven and cannot be awaited naturally in this 12-day test. Drive both targeted handlers, file only the confirmed expiring-soon scheduler gap, and treat the working card-expiring webhook contract as a pass unless live evidence exposes a separate defect.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt-eml`)
- Plugins: both

## Preconditions
- SLT-EML-08 ran earlier today, leaving `S_EML` **active** with a valid `_next_payment_date`.
- The strict D8 order has reached this task after `SLT-TT-00` → `SLT-SYN-10` → `SLT-SW-02` → `SLT-EML-08`. Quote the shared non-SLT schedule baseline and the latest empty diff before scheduling anything.
- **D8 = 2026-08-10, the only authorized date-meta time-travel day** (C07/C17). Run this task's actions **by exact id**; hook/group drains remain forbidden. Verify `arraysubs_send_expiring_soon` has no other actions site-wide and re-snapshot immediately before each Run click.
- `emails.expiring_soon.enabled=true`, `days_before=7`; `emails.card_expiring.*` is absent from defaults so it resolves enabled (`email-helpers.php:50-80`).

## Test data
| Item | Value |
|---|---|
| Subscription | `S_EML` (SLT Daily Core, `slt-eml`) |
| Target | `_end_date` if valid, else `_next_payment_date` (`EmailManager.php:925-936`) |

## Steps
1. Resolve numeric `S_EML`. Query every `arraysubs_send_expiring_soon` row with status, args, group and logs. Classify the registry-declared historical SLT-LIFE-04 targeted probe; require **zero pending rows and zero rows with natural scheduler provenance**, not an impossible empty all-status table. In `admin-SLT-EML-10`, screenshot that filtered queue/history as the B1 evidence.
2. Set `E1=$(mailpit-agent latest-id)`. Schedule exactly one target action **12 hours in the future** with the shared ActionScheduler constants, so per-minute cron cannot race the browser; never schedule it in the past. Record the numeric action ID returned/resolved by exact hook/group/args.
3. Query exact pending hook/group/args and require exactly that one action. In `admin-SLT-EML-10`, re-snapshot and run only that recorded ID from Tools → Scheduled Actions.
4. Poll immutable E1 in repeated calls no longer than 60 seconds through the two-minute cutoff for `is ending soon`; save the exact matched id, inspect the complete delta, and save its text. Unrelated shared-site mail is classified.
5. `wp post meta list "$S_EML" --keys=_arraysubs_expiring_soon_sent_for,_arraysubs_expiring_soon_sent_at --allow-root`.
6. Dedupe probe: `E2=$(mailpit-agent latest-id)`; repeat the safe future scheduling/exact-ID UI run unchanged; one 60-second `wait-new` must time out (exit 124), then classify the complete bounded delta.
7. Set `E3=$(mailpit-agent latest-id)`. Fire the gateway action directly for only the resolved target: `wp eval "do_action('arraysubs_card_expiring', (int) $S_EML, ['object'=>'card','last4'=>'4242','exp_month'=>8,'exp_year'=>2026], 'stripe');" --allow-root`.
8. One 60-second wait on immutable E3 must return the exact card-update message; save text and full delta. In `customer-SLT-EML-10`, follow its payment-method link as `slt-eml` and require it resolves to numeric S_EML without exposing card data.
9. If steps 1–6 prove no natural-scheduler provenance while the targeted handler works, file only `issues/SLT-EML-10-expiring-soon-unreachable.md`; a working webhook-driven card handler is not an issue. Every issue file must include task/stage/plan path; subscription/action/message IDs; user ID/login/email/role; exact routes/sessions/manual-action timestamps; reproduction; expected/actual; and queue/log/meta/UI/Mailpit proof. Do not inspect product source.
10. Teardown: set E4 immediately before the admin status mutation; in `admin-SLT-EML-10` cancel numeric S_EML, then poll immutable E4 in ≤60-second calls through the two-minute cutoff and require the exact customer/admin cancellation pair by subject/To.
11. Re-run the shared non-SLT schedule diff and require it empty, close both exact sessions, independently review the two targeted handlers/dedupe/cancellation evidence, move through `review` to `done` with Review empty, then hand off to SLT-LIFE-01.

## Expected results
1. Before step 2, zero pending or naturally scheduled `arraysubs_send_expiring_soon` actions exist; the prior registry-declared targeted probe is classified separately — B1 confirmed without a false all-history assertion.
2. One `[mirror-help.arrayhash.com] Your subscription #<S_EML> is ending soon` to `slt-eml@example.test`, from the renewal-reminder template, no tax line.
3. Dedupe meta `_arraysubs_expiring_soon_sent_for` = `<_next_payment_date>|7`, `_sent_at` set.
4. The second run sends nothing (dedupe on the unchanged key) — `mailpit-agent wait-new` exits 124.
5. One `[mirror-help.arrayhash.com] Update the card for subscription #<S_EML>` to `slt-eml@example.test` with a working payment-method link; no order, no status change.
6. The missing natural `expiring_soon` scheduler is filed as one standalone issue. The webhook-driven `card_expiring` result is recorded separately and produces an issue only if its live handler/link contract fails. Teardown sends the cancelled pair and touches nothing else.

## Emails expected
| # | Email | Trigger | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | expiring_soon | step 3 | slt-eml | `#<S_EML> is ending soon` | immutable-baseline polls ≤60 seconds through the two-minute cutoff |
| 2 | NONE EXPECTED — 2nd expiring_soon | step 6 | — | `is ending soon` | `mailpit-agent wait-new "$E2" 60` exits 124 |
| 3 | card_expiring | step 7 | slt-eml | `Update the card for subscription #<S_EML>` | `mailpit-agent wait-new "$E3" 60 "Update the card"` |
| 4 | subscription_cancelled + admin copy | step 10 | slt-eml + admin | `has been cancelled` / `cancelled by` | immutable-baseline polls ≤60 seconds through the two-minute cutoff |

## Evidence to capture
- `SLT-EML-10-01-no-hook.png`, `-02-expiring-soon.png`, `-03-card-expiring.png`, `-04-cancelled.png`; AS ids, Mailpit ids, steps 1/5 output, issue path.

## Pass criteria
- [ ] Zero pending/naturally scheduled expiring-soon actions; known targeted history classified as B1 evidence
- [ ] Expiring-soon mail, exact subject, `<target>|7` dedupe meta; second run sends nothing
- [ ] Card-expiring mail, exact subject, working update link
- [ ] One issue filed for the confirmed expiring-soon scheduler gap; card-expiring issue only on an actual live defect; cancellation pair on teardown; shared non-SLT diff empty
- [ ] Safe future exact-ID actions, exact sessions, and independent review close with Review empty

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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence and do not force a natural-watch action.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run a bare or `--hooks=` Action Scheduler drain. Run one known action ID at a time only when the task explicitly authorizes it and after the required queue pre-flight; natural-watch actions are never forced.
- Evidence goes under `/home/server-manager/slt-evidence/` using task-key-prefixed filenames.

[[2026-08-06]] Thu 22:03
Source-block note on 2026-08-06: this D8 email task depends on card 107 / SLT-EML-08 having already reactivated `S_EML` after card 111 / SLT-SW-02 executed the targeted downgrade/expiry branch. Card 111 is currently source-blocked because card 72 / SLT-SW-00 never created the ladder-switch fixtures, so this card cannot start until a later valid execution recreates that chain and card 107 completes first.

[[2026-08-10]] Mon 06:51
D08 execution closes `UNVERIFIED` at the authored active-source guard. Upstream card 107 completed its execution record without reactivating `S_EML` because the card-111 downgrade/expiry chain has no numeric source. Fresh state proves `S_EML=12263` remains `arraysubs-cancelled` with no valid next payment, so this task did not create E1-E4 baselines, schedule or run any probe action, invoke the direct card-expiring hook, write dedupe meta, test a payment-method link, or emit cancellation mail. Read-only all-status history contains exactly one `arraysubs_send_expiring_soon` row: targeted SLT-LIFE-04 probe 14773, args `[12017]`, complete via Admin List Table; pending count is zero and no natural-provenance row is present. Because the required targeted handler/dedupe proof was not lawfully reachable, the natural scheduler gap is not newly confirmed and the mandatory issue condition is not satisfied; no issue was filed. Full closeout: `/home/server-manager/slt-evidence/SLT-EML-10-D08-source-block.txt`. No site/browser mutation occurred.
