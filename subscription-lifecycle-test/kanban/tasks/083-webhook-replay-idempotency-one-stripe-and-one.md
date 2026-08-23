---
id: 83
title: 'Webhook replay idempotency: one Stripe and one Paddle renewal event, no duplicates'
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - edge-cases
    - day-05
due: "2026-08-28"
estimate: 3h
depends_on:
    - 5
    - 23
    - 26
    - 9
    - 42
class: standard
---

> **SLT-IMP-02** · group `implied` · scheduled **D05** (2026-08-28)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Replay one Stripe and one Paddle renewal webhook for SLT2 subscriptions that already renewed, and prove the second delivery creates no second order, charge, email or date advance. The contract is the UNIQUE `(gateway_slug, event_id)` key on `wp_arraysubs_webhook_events`: Paddle claims atomically before dispatch (`WebhookRouter::process()`, `claimEvent()` INSERT IGNORE); Stripe dedupes in `StripeDelegate::handleOfficialStripeWebhook()`.

## Scope
- Gateway: both (Stripe test, Paddle sandbox)
- Checkout: N/A
- Account: existing (slt2-core, slt2-paddle)
- Plugins: core-owned Stripe/Paddle webhook path

## Preconditions
- `SLT2 Daily Core` (slt2-core) has >=1 paid renewal order and `SLT2 Paddle Daily` (slt2-paddle) >=1 Paddle-confirmed renewal; both hold by D5 via SLT-PROD-01/16.
- Run NO `wp action-scheduler run` here (C07). D8 = 2026-08-31 is only the authorized date-meta time-travel day; hook/group drains remain forbidden there too, and its tasks run only exact verified action IDs from the UI.
- Read secrets from WP root into non-exported shell variables without printing them: `STRIPE_WEBHOOK_SECRET="$(wp option get woocommerce_stripe_settings --format=json --allow-root | jq -r .test_webhook_secret)"` and `PADDLE_WEBHOOK_SECRET="$(wp option get woocommerce_arraysubs_paddle_settings --format=json --allow-root | jq -r .webhook_secret)"`. Require both variables non-empty, never echo them, and unset them after the signed requests; no secret enters evidence.

## Test data
| Item | Value |
|---|---|
| Subjects | SLT2 Daily Core/slt2-core, SLT2 Paddle Daily/slt2-paddle |
| Paddle endpoint | `POST /wp-json/arraysubs/v1/webhooks/arraysubs_paddle` |
| Paddle header | `Paddle-Signature: ts=<now>;h1=hmac_sha256("<ts>:<body>",secret)`; rejected if ts >300 s old |
| Stripe endpoint | `POST /?wc-api=wc_stripe` |
| Stripe header | `Stripe-Signature: t=<now>,v1=hmac_sha256("<t>.<body>",secret)` |
| Dedupe | `wp_arraysubs_webhook_events`, slugs `arraysubs_stripe`/`arraysubs_paddle` |

## Steps
1. Resolve exact numeric `STRIPE_SUB`/`PADDLE_SUB` and their already-paid renewal `STRIPE_ORDER`/`PADDLE_ORDER` by bidirectional subscription/cycle relationship from the registry. Resolve each exact provider event ID and its byte-identical raw JSON body from the provider/audit record tied to that order; never select the newest event or fabricate/rebuild a payload. If either source is missing, execute and preserve the available half, create/update the upstream issue, and leave this card blocked until both halves pass.
2. In `admin-SLT-IMP-02`, capture Gateway Logs before state as `SLT-IMP-02-01-logs-before.png`; record an exact log-row ID/time cursor and the before ID set/count for `wp_arraysubs_webhook_events`. For each numeric sub save status plus `_next_payment_date,_last_payment_date,_completed_payments`, every relationship-owned order ID/status/total/transaction ID, and exact note-ID set.
3. Set `M0=$(mailpit-agent latest-id)` immediately before the first available replay. Load secrets into non-exported variables without output, install an EXIT trap that unsets them, and require non-empty values; no command tracing and no secret may enter evidence.
4. PADDLE, only with its exact raw body: query the exact `(gateway_slug,event_id)` row first, sign those unchanged bytes with a fresh timestamp, POST once, and write only the redacted response/body/status to `SLT-IMP-02-02-paddle-replay.txt`.
5. Assert HTTP **200** and body `{"received":true,"duplicate":true}`.
6. STRIPE: prefer the exact order-owned event's Dashboard **Resend**. If unreachable, re-POST its byte-identical raw JSON with the same `evt_` ID and a fresh valid local signature. Record the path and redacted response in `SLT-IMP-02-03-stripe-path.txt`; if exact payload provenance is unavailable, do not send an approximation and mark only this half `BLOCKED`.
7. Re-query exact gateway/event-ID rows and the full before ID set/count, write `SLT-IMP-02-05-events-table.txt`, and require no inserted ID or changed `processed_at`; never use a broad `LIMIT 10` as the assertion.
8. Inspect the complete Mailpit delta after `M0` (paginate the localhost API if needed); require zero message attributable to either replay and classify independently scheduled/background mail by its actual owner. Re-read the step-2 metas and re-list orders for both subs.
9. Re-open Gateway Logs and capture only rows after the exact cursor as `SLT-IMP-02-04-logs-after.png`. Search only runtime log files and byte/time window owned by the two POSTs with `rg` (not grep), redact secrets/signatures, and record both event IDs/slugs.
10. Open `#/audits/renewal-failures`; capture `SLT-IMP-02-06-failures.png` and require no relationship-owned row. Reconcile exact pre/post subscription/order/note/meta sets and the complete `M0` delta, then unset both secrets and close only `admin-SLT-IMP-02`.
11. If a replay is accepted, a source is missing or any assertion fails, create/update the mandatory `qa/issues/` kanban card containing task/stage/plan, gateway/event/subscription/order/note IDs, affected users, exact endpoint/session context, reproduction, expected/actual and sanitized proof. Never include payload secrets. Mark done only after both gateway replays pass.

## Expected results
1. `wp_arraysubs_webhook_events` gains **zero** rows; each replayed `event_id` keeps its `processed_at`.
2. Paddle returns 200 `duplicate: true`; Gateway Logs shows `[Gateway: arraysubs_paddle] [Webhook] Duplicate webhook event skipped: <event_id>`, level `info`.
3. The Stripe replay produces no `arraysubs_gateway_payment_succeeded` effect: no order note, no second `payment_complete()`, no new transaction id.
4. Order count per subscription unchanged; no order changes status or total.
5. `_completed_payments`, `_last_payment_date`, `_next_payment_date` byte-identical to step 2.
6. The complete M0 delta contains zero replay-attributable mail.
7. No new Renewal Failures row; no PHP notice in `wp-content/debug.log` in the replay window.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| — | **NONE EXPECTED** | both replays | — | — | complete delta after `M0`; zero replay-attributable message, classify unrelated mail |

Negative check: exactly one `Payment received for subscription #$STRIPE_SUB` and one for `#$PADDLE_SUB` in each exact source cycle, never a replay-created duplicate.

## Evidence to capture
- `SLT-IMP-02-01-logs-before.png`, `-02-paddle-replay.txt`, `-03-stripe-path.txt`, `-04-logs-after.png`, `-05-events-table.txt`, `-06-failures.png`.
- Exact gateway/event/subscription/order/note/user IDs and pre/post sets/hashes, payload provenance without secrets, `M0`, redacted response/status lines, log cursors, console errors, session/review proof.

## Pass criteria
- [ ] no new `wp_arraysubs_webhook_events` row for either replay
- [ ] Paddle returns 200 `duplicate:true` and logs the skip
- [ ] no new order, no status/total change on either subscription
- [ ] the three schedule metas unchanged on both
- [ ] Complete M0 delta contains no replay-attributable mail
- [ ] no new Renewal Failures row, no PHP notice in the window
- [ ] the Stripe replay path is recorded (else `BLOCKED`)
- [ ] No fabricated payload used; exact session closed, findings only in QA issue cards, and evidence reviewed to done

## Isolation / teardown
- Read-only apart from exact-payload replays. Do not delete audit rows. A missing source blocks the composite card until the prerequisite is rerun.
- Never delete from `wp_arraysubs_webhook_events`; its cleanup job prunes at 30 days.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
