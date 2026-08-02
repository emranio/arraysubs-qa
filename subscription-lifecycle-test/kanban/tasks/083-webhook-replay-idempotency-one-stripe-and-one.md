---
id: 83
title: 'Webhook replay idempotency: one Stripe and one Paddle renewal event, no duplicates'
status: todo
priority: high
created: 2026-08-02T03:43:10.132785191+02:00
updated: 2026-08-02T03:43:21.036500115+02:00
tags:
    - edge-cases
    - day-05
    - has-conflicts
due: "2026-08-07"
estimate: 3h
depends_on:
    - 5
    - 23
    - 26
class: standard
---

> **SLT-IMP-02** · group `implied` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

---
## Objective
Replay one Stripe and one Paddle renewal webhook for SLT subscriptions that already renewed, and prove the second delivery creates no second order, charge, email or date advance. The contract is the UNIQUE `(gateway_slug, event_id)` key on `wp_arraysubs_webhook_events`: Paddle claims atomically before dispatch (`WebhookRouter::process()`, `claimEvent()` INSERT IGNORE); Stripe dedupes in `StripeDelegate::handleOfficialStripeWebhook()`.

## Scope
- Gateway: both (Stripe test, Paddle sandbox)
- Checkout: N/A
- Account: existing (slt-core, slt-paddle)
- Plugins: pro-required

## Preconditions
- `SLT Daily Core` (slt-core) has >=1 paid renewal order and `SLT Paddle Daily` (slt-paddle) >=1 Paddle-confirmed renewal; both hold by D5 via SLT-PROD-01/16.
- Run NO `wp action-scheduler run` here (C07); D8 = 2026-08-10 is the only drain day.
- Read secrets from WP root, never into evidence: `wp option get woocommerce_stripe_settings --format=json --allow-root | jq -r .test_webhook_secret`; same for `woocommerce_arraysubs_paddle_settings | jq -r .webhook_secret`.

## Test data
| Item | Value |
|---|---|
| Subjects | SLT Daily Core/slt-core, SLT Paddle Daily/slt-paddle |
| Paddle endpoint | `POST /wp-json/arraysubs/v1/webhooks/arraysubs_paddle` |
| Paddle header | `Paddle-Signature: ts=<now>;h1=hmac_sha256("<ts>:<body>",secret)`; rejected if ts >300 s old |
| Stripe endpoint | `POST /?wc-api=wc_stripe` |
| Stripe header | `Stripe-Signature: t=<now>,v1=hmac_sha256("<t>.<body>",secret)` |
| Dedupe | `wp_arraysubs_webhook_events`, slugs `arraysubs_stripe`/`arraysubs_paddle` |

## Steps
1. `agent-browser --session admin open ".../admin.php?page=arraysubs#/audits/gateway-logs"` -> `snapshot -i`. Screenshot the tail; record the newest row time `G0`.
2. Pre-state for BOTH subs: `wp post meta list <SUB_ID> --keys=_next_payment_date,_last_payment_date,_completed_payments --allow-root`, plus every order id linked by `_subscription_id`.
3. `mailpit-agent latest-id` -> `M0`.
4. PADDLE: `wp db query "SELECT event_id,event_type FROM wp_arraysubs_webhook_events WHERE gateway_slug='arraysubs_paddle' ORDER BY id DESC LIMIT 5" --allow-root`. Rebuild a `transaction.completed` body with that exact `event_id`, sign with a fresh `ts`, POST with `curl -s -w '\n%{http_code}\n'`.
5. Assert HTTP **200** and body `{"received":true,"duplicate":true}`.
6. STRIPE: prefer Stripe test Dashboard -> Developers -> Events -> the renewal's `payment_intent.succeeded` -> **Resend**. If unreachable, re-POST the same event JSON (same `evt_` id), locally signed, to the wc-api endpoint. Record the path used; if neither works the Stripe half is `UNVERIFIED`, never PASS.
7. Re-run the step-4 query with no WHERE clause, `LIMIT 10`.
8. `mailpit-agent list 50`; compare to `M0`. Re-read the step-2 metas and re-list orders for both subs.
9. Re-open Gateway Logs, screenshot new rows, grep `wp-content/uploads/wc-logs/*2026-08-07*` for both slugs.
10. Open `#/audits/renewal-failures`; confirm the replay created no row.

## Expected results
1. `wp_arraysubs_webhook_events` gains **zero** rows; each replayed `event_id` keeps its `processed_at`.
2. Paddle returns 200 `duplicate: true`; Gateway Logs shows `[Gateway: arraysubs_paddle] [Webhook] Duplicate webhook event skipped: <event_id>`, level `info`.
3. The Stripe replay produces no `arraysubs_gateway_payment_succeeded` effect: no order note, no second `payment_complete()`, no new transaction id.
4. Order count per subscription unchanged; no order changes status or total.
5. `_completed_payments`, `_last_payment_date`, `_next_payment_date` byte-identical to step 2.
6. Mailpit `latest-id` still `M0`.
7. No new Renewal Failures row; no PHP notice in `wp-content/debug.log` in the replay window.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| — | **NONE EXPECTED** | both replays | — | — | `mailpit-agent latest-id` after step 8 == `M0` |

Negative check: exactly ONE `Payment received for subscription #<SUB_ID>` per cycle, not two.

## Evidence to capture
- `SLT-IMP-02-01-logs-before.png`, `-02-paddle-replay.txt`, `-03-stripe-path.txt`, `-04-logs-after.png`, `-05-events-table.txt`, `-06-failures.png`.
- Both subscription ids, all order ids before/after, both `event_id`s, `M0`, curl status lines, console errors.

## Pass criteria
- [ ] no new `wp_arraysubs_webhook_events` row for either replay
- [ ] Paddle returns 200 `duplicate:true` and logs the skip
- [ ] no new order, no status/total change on either subscription
- [ ] the three schedule metas unchanged on both
- [ ] Mailpit latest-id unchanged
- [ ] no new Renewal Failures row, no PHP notice in the window
- [ ] the Stripe replay path is recorded (else `UNVERIFIED`)

## Isolation / teardown
- Read-only apart from the two POSTs. If a replay IS accepted, file the issue; do NOT delete the row.
- Never delete from `wp_arraysubs_webhook_events`; its cleanup job prunes at 30 days.

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
