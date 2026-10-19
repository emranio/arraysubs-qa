# Phase 1 handoff (restart r2, completed 2026-10-19 ~07:35 UTC)

Configuration A: core `4791832` + Pro `edaa401` active (clean trees, unchanged during phase 1). Stripe official gateway 10.9.0, test mode. Site timezone UTC+6 (UI times are site-local; all times below are UTC).

## Results

| Case | Config | Result | References |
|---|---|---|---|
| P1-CAP | - | DONE | `wp-content/mu-plugins/sqa-lifecycle-capture.php` identical to `scripts/mu-plugin-sqa-lifecycle-capture.php`; capturing from FPM and WP-CLI |
| P1-FIX | - | DONE | customers 576/577/578 (created by r1 at 2026-10-18 13:45Z, reused; passwords reset, stored outside the QA folder), product 68271, classic checkout page 68273, protected post 68274 |
| S01 Classic | A | PASS | customer 576; order 68280 (completed, 7.31 USD); subscription 68281 active; pi_3USAniJG5OzSNVs21Se63lGZ (succeeded, setup_future_usage=off_session), ch_3USAniJG5OzSNVs213QMkLe5, pm_1USAndJG5OzSNVs2XlkIpgyU attached to cus_VT71tAVKNoT5Om, WC token 61 default; actions 100773/100774 (invoice/process) aligned; entitlement allowed (developer check + browser) |
| S01 Block | A | PASS (with issue #55 observation) | customer 577; order 68302 / subscription 68304 active; pi_3USAsqJG5OzSNVs2027MoJGU, ch_3USAsqJG5OzSNVs20A3MiN3V, pm_1USAscJG5OzSNVs2LsLR34GP on cus_VT76vZzzT3jCHy, token 62; actions 100788/100789; entitlement allowed |
| S01 initial failure | A | PASS | customer 578; card 4000 0000 0000 0002 → "The card was declined."; order 68340 failed; subscription 68341 on-hold, `has_view_entitlement=false`, completed_payments 0, no granted/activated events, no token, no renewal actions; pi_3USAz5JG5OzSNVs21g1iBtSB requires_payment_method/generic_decline; developer access check denied |
| S02 | A | PASS | 68281: invoice action 100812 → renewal order 68355; process action 100813 → pi_3USB27JG5OzSNVs20UOFEyBI succeeded, ch_3USB27JG5OzSNVs20THHKrv3; 68304: 100814 → 68358; 100815 → pi_3USB2EJG5OzSNVs20n24T6M2. Each: one PI per renewal (Stripe PI list per customer), completed_payments 1→2, next_payment 2026-10-19 07:30:49 → 2026-10-20 07:30:49, next-cycle jobs 100817/100818 and 100819/100820 (invoice = process − 6 h), renewal emails in Mailpit, customer + admin views correct |

## Lifecycle capture vs developer snapshots

- Checkout: `status_changed`(pending) → `granted` → `status_changed`(active) → `activated` (order_id 68280/68302). `granted` and `status_changed` carry `order_id: 0` with `parent_order_id` set; snapshots agree on initial_order_id.
- Renewal: `renewal_invoice_created` (queue runner) → `renewal_payment_attempted` → `renewed` (effect grant) → `renewal_payment_result` (result paid). All payload values scalar; `event_id` distinct per event; `next_payment_date`, `order_id`, `status` match `snapshots/sub-*-s02-after-payment.json`.

## Observations

- **Issue #55** (qa/issues): the first Block checkout created two concurrent orders/subscriptions (68292/68293 and 68302/68304) and charged pm_1USAsc… twice. Not reproduced in two follow-up attempts (68314/68315 saved card, 68329/68330 new card, HAR recorded). Origin unproven.
- **Cloudflare edge cache** serves the cached guest copy of pages to logged-in members (see plan.md environment note). Use cache-busted URLs for entitlement checks.
- Admin subscription detail shows renewal notes in both "Payment Timeline" and "Subscription Notes" (stored once each) — expected, not a defect.

## State for phase 2

- Settings unchanged in phase 1 (no diff needed yet).
- Extra active fixture subscriptions for customer 577: 68293, 68315, 68330 (daily renewals pending; leave to phase 7 cleanup or reuse). Their scheduled actions will become due ~2026-10-20; do not let them run unattended if that would confuse later phases — they are fixtures, so running/cancelling them is allowed.
- Snapshot tool: `scripts/dev-snapshot.php <sub> <label>` (writes `snapshots/sub-<id>-<label>.json`, true UTC dates, includes renewal orders and scheduled actions).
- Browser sessions: sqa-admin, sqa-classic, sqa-block, sqa-initfail, sqa-guest. In zsh, wrap agent-browser in a function (`B(){ agent-browser --session X "$@"; }`). Stripe Payment Element is a cross-origin iframe: click the card field by coordinates (`mouse move/down/up`) and `keyboard type`.
