---
id: 83
title: 'Webhook replay idempotency: one Stripe and one Paddle renewal event, no duplicates'
status: done
priority: high
created: 2026-08-02T03:43:10.132785191+02:00
updated: 2026-08-07T04:58:43.268972228+02:00
started: 2026-08-07T04:58:43.268971507+02:00
completed: 2026-08-07T04:58:43.268971507+02:00
tags:
    - edge-cases
    - day-05
due: "2026-08-07"
estimate: 3h
depends_on:
    - 5
    - 23
    - 26
    - 9
    - 42
class: standard
---

> **SLT-IMP-02** · group `implied` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Replay one Stripe and one Paddle renewal webhook for SLT subscriptions that already renewed, and prove the second delivery creates no second order, charge, email or date advance. The contract is the UNIQUE `(gateway_slug, event_id)` key on `wp_arraysubs_webhook_events`: Paddle claims atomically before dispatch (`WebhookRouter::process()`, `claimEvent()` INSERT IGNORE); Stripe dedupes in `StripeDelegate::handleOfficialStripeWebhook()`.

## Scope
- Gateway: both (Stripe test, Paddle sandbox)
- Checkout: N/A
- Account: existing (slt-core, slt-paddle)
- Plugins: pro-required

## Preconditions
- `SLT Daily Core` (slt-core) has >=1 paid renewal order and `SLT Paddle Daily` (slt-paddle) >=1 Paddle-confirmed renewal; both hold by D5 via SLT-PROD-01/16.
- Run NO `wp action-scheduler run` here (C07). D8 = 2026-08-10 is only the authorized date-meta time-travel day; hook/group drains remain forbidden there too, and its tasks run only exact verified action IDs from the UI.
- Read secrets from WP root into non-exported shell variables without printing them: `STRIPE_WEBHOOK_SECRET="$(wp option get woocommerce_stripe_settings --format=json --allow-root | jq -r .test_webhook_secret)"` and `PADDLE_WEBHOOK_SECRET="$(wp option get woocommerce_arraysubs_paddle_settings --format=json --allow-root | jq -r .webhook_secret)"`. Require both variables non-empty, never echo them, and unset them after the signed requests; no secret enters evidence.

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
1. Resolve exact numeric `STRIPE_SUB`/`PADDLE_SUB` and their already-paid renewal `STRIPE_ORDER`/`PADDLE_ORDER` by bidirectional subscription/cycle relationship from the registry. Resolve each exact provider event ID and its byte-identical raw JSON body from the provider/audit record tied to that order; never select the newest event or fabricate/rebuild a payload. If one gateway lacks an exact paid renewal plus raw payload, mark only that half `UNVERIFIED`, execute the other half, and still close the card through review.
2. In `admin-SLT-IMP-02`, capture Gateway Logs before state as `SLT-IMP-02-01-logs-before.png`; record an exact log-row ID/time cursor and the before ID set/count for `wp_arraysubs_webhook_events`. For each numeric sub save status plus `_next_payment_date,_last_payment_date,_completed_payments`, every relationship-owned order ID/status/total/transaction ID, and exact note-ID set.
3. Set `M0=$(mailpit-agent latest-id)` immediately before the first available replay. Load secrets into non-exported variables without output, install an EXIT trap that unsets them, and require non-empty values; no command tracing and no secret may enter evidence.
4. PADDLE, only with its exact raw body: query the exact `(gateway_slug,event_id)` row first, sign those unchanged bytes with a fresh timestamp, POST once, and write only the redacted response/body/status to `SLT-IMP-02-02-paddle-replay.txt`.
5. Assert HTTP **200** and body `{"received":true,"duplicate":true}`.
6. STRIPE: prefer the exact order-owned event's Dashboard **Resend**. If unreachable, re-POST its byte-identical raw JSON with the same `evt_` ID and a fresh valid local signature. Record the path and redacted response in `SLT-IMP-02-03-stripe-path.txt`; if exact payload provenance is unavailable, do not send an approximation and mark only this half `UNVERIFIED`.
7. Re-query exact gateway/event-ID rows and the full before ID set/count, write `SLT-IMP-02-05-events-table.txt`, and require no inserted ID or changed `processed_at`; never use a broad `LIMIT 10` as the assertion.
8. Inspect the complete Mailpit delta after `M0` (paginate the localhost API if needed); require zero message attributable to either replay and classify independently scheduled/background mail by its actual owner. Re-read the step-2 metas and re-list orders for both subs.
9. Re-open Gateway Logs and capture only rows after the exact cursor as `SLT-IMP-02-04-logs-after.png`. Search only runtime log files and byte/time window owned by the two POSTs with `rg` (not grep), redact secrets/signatures, and record both event IDs/slugs.
10. Open `#/audits/renewal-failures`; capture `SLT-IMP-02-06-failures.png` and require no relationship-owned row. Reconcile exact pre/post subscription/order/note/meta sets and the complete `M0` delta, then unset both secrets and close only `admin-SLT-IMP-02`.
11. If a replay is accepted or any live assertion fails, create a standalone `issues/SLT-IMP-02-<concise-slug>.md` (never a kanban bug card) containing task/stage/plan, gateway/event/subscription/order/note IDs, affected user IDs/logins/emails/roles, exact endpoint/admin/session context, reproduction, expected/actual, sanitized response/DB/meta/log/Mailpit/screenshot proof, and the other gateway as counterexample where applicable. Never include payload secrets. Independently review all available/UNVERIFIED evidence, move the card through `review` to `done`, and ensure Review returns to zero.

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
- [ ] the Stripe replay path is recorded (else `UNVERIFIED`)
- [ ] No fabricated payload used; exact session closed, findings only in standalone issue files, and evidence reviewed to done

## Isolation / teardown
- Read-only apart from the available exact-payload POSTs. If a replay is accepted, write only the standalone issue file; do NOT delete the row. A missing exact source closes that half `UNVERIFIED`, not the whole card in progress.
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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence and do not force a natural-watch action.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run a bare or `--hooks=` Action Scheduler drain. Run one known action ID at a time only when the task explicitly authorizes it and after the required queue pre-flight; natural-watch actions are never forced.
- Evidence goes under `/home/server-manager/slt-evidence/` using task-key-prefixed filenames.

[[2026-08-06]] Thu 21:15
Preflight only on Thursday, August 6, 2026. This card remains scheduled for D05 and was not executed early. Live read-only verification resolved the current exact dedupe rows and owner-side identifiers:
- Paddle row `353` / `evt_01kzb9eq7ns3swtn84m9yg73k4` / `transaction.completed` / processed `2026-08-06 10:21:25`
- Stripe rows `381` / `evt_3U1TMwJG5OzSNVs20eEtPhCA` / `charge.succeeded` and `382` / `evt_3U1TMwJG5OzSNVs203L0uTfi` / `payment_intent.succeeded`, both processed `2026-08-06 15:38:09`
- `slt-core` Stripe renewal order `12915` stores `_stripe_intent_id=pi_3U1TMwJG5OzSNVs2043jxaUx` and `_stripe_charge_id=ch_3U1TMwJG5OzSNVs2038AdnWY`
Current local provenance gap: direct searches across `wp_postmeta`, `wp_options`, `wp_comments`, and local WC log files found the event ids and duplicate-skip log lines but did not surface a byte-identical raw JSON body for either replay candidate. Future D05 execution must fetch exact payload bytes from the provider or an audit store before replaying; do not reconstruct or approximate the JSON body.

[[2026-08-06]] Thu 21:46
Local provenance re-check on Thursday, August 6, 2026: the dedupe table wp_arraysubs_webhook_events stores only id/gateway_slug/event_id/event_type/processed_at, so it cannot be the exact-byte payload source for replay. The live arraysubs-gateway wc-log for 2026-08-06 proves the candidate event ids and duplicate-skip behavior, but still does not retain a byte-identical raw JSON body for the Paddle event evt_01kzb9eq7ns3swtn84m9yg73k4 or the slt-core Stripe pair around renewal order 12915. Future execution still needs exact raw bytes from the provider or another authoritative audit store; do not reconstruct the payload.

[[2026-08-06]] Thu 21:47
Local provenance sweep on Thursday, August 6, 2026: beyond wp_arraysubs_webhook_events and the arraysubs-gateway wc-log, a broader filesystem scan plus an information_schema scan for text/json columns named like payload/body/event/request/response/log found no obvious local webhook archive. The only extra text-like schema hit was unrelated wp_ihc_user_logs.log_content. This keeps the exact raw webhook body blocker unchanged: the D5 replay run still needs the byte-identical payload from the provider or another authoritative audit source.

[[2026-08-06]] Thu 21:49
Exhaustive local DB provenance sweep on Thursday, August 6, 2026: across every wp_% char/varchar/text/json column, the three candidate event ids were found only in wp_arraysubs_webhook_events.event_id (3 hits total) and nowhere else. Combined with the earlier schema/log checks, this makes the local blocker precise: current database storage does not preserve a replayable raw webhook body for the candidate Paddle or Stripe events.

[[2026-08-06]] Thu 22:06
Preflight only on Thursday, August 6, 2026. Did not execute the D05 replay early because D05 is Friday, August 7, 2026 and therefore still future-dated from the current run.
- Stripe official resend path is available in principle from the provider side: the local Stripe account exposes webhook endpoint `we_1TYtUnJG5OzSNVs2eeeoioka` for `https://mirror-help.arrayhash.com/?wc-api=wc_stripe`, and the order-owned event mapping is exact: `evt_3U1TMwJG5OzSNVs20eEtPhCA` -> `charge.succeeded` -> charge `ch_3U1TMwJG5OzSNVs2038AdnWY` on renewal order `12915` / subscription `11959`; sibling `payment_intent.succeeded` event `evt_3U1TMwJG5OzSNVs203L0uTfi` maps to intent `pi_3U1TMwJG5OzSNVs2043jxaUx`.
- Paddle provider-side event retrieval remains blocked from this environment: the configured API key exists, but `GET /events?event_type=transaction.completed&per_page=200` returned `forbidden`, so current environment access still cannot supply a provider-side exact-event payload for `evt_01kzb9eq7ns3swtn84m9yg73k4`.
- This keeps the safe D05 rule unchanged: do not fabricate or rebuild either gateway payload. Execute the Stripe half only via a provider-side resend path if the CLI or dashboard is available on Friday, August 7, 2026; otherwise mark that half `UNVERIFIED` too. Paddle currently remains source-blocked unless exact bytes become available from the provider or another authoritative audit store.

[[2026-08-07]] Fri 04:58

[[2026-08-07]] Fri 08:55 UTC+6 — D05 execution closeout
- Stripe half: UNVERIFIED. Exact order-owned event evt_3U1TMwJG5OzSNVs20eEtPhCA resolved to subscription 11959 / renewal order 12915, but the official Dashboard URL rendered only the Stripe sign-in form, Stripe CLI is absent, and no byte-identical local raw body exists. No resend/POST was attempted and no secret was loaded.
- Paddle half: UNVERIFIED. Exact event evt_01kzb9eq7ns3swtn84m9yg73k4 resolved to subscription 12639 / renewal order 12891, but the byte-identical body is not stored locally and the provider event API was already proven forbidden. No approximation, POST, or secret load occurred.
- Read-only stability checks: event table remained exactly 182 rows with identical full ID set; rows 353/381/382 and processed_at values unchanged; both subscriptions, all relationship orders, schedule metas, transaction IDs, and exact note-ID sets remained identical; Mailpit cursor 1fEVaKxuFnCCLwCxNzeZ7G did not move; Renewal Failures contains no task-owned row; browser errors empty.
- Evidence: /home/server-manager/slt-evidence/SLT-IMP-02-01-logs-before.png, -02-paddle-replay.txt, -03-stripe-path.txt, -03-stripe-dashboard-unavailable.png, -04-logs-after.png, -05-events-table.txt, -06-failures.png, and SLT-IMP-02-state.txt.
- Browser session admin-SLT-IMP-02 closed. No issue filed: missing exact provider payload/auth is a test-source limitation, and no product request was sent. Per authored step 11 and isolation contract, both unavailable halves close UNVERIFIED through review rather than remaining open.
