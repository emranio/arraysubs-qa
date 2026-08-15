# Routine Paddle intermediary webhooks are logged as unhandled warnings

- Severity: low
- Status: closed on 2026-08-14
- Date found: 2026-08-11
- Watch day: D09
- Originating task: `SLT-IMP-05` (progress task ID `116`)
- Plan file: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/kanban/tasks/116-end-of-window-log-sweep-failed-action-audit-and.md`

## Task / stage / plan

- QA progress task: `#116` / `SLT-IMP-05`
- Stage: D09 end-of-window raw-log sweep
- Plan path: `qa/subscription-lifecycle-test/kanban/tasks/116-end-of-window-log-sweep-failed-action-audit-and.md`

## Affected records

- Subscription IDs: `12639` (SLT Paddle Daily) and `13344` (SLT Plan Basic)
- Parent/order IDs printed by the processed webhook rows: `12629` and `13343`
- Related completed positive renewal orders on 2026-08-10: `13617` for subscription `12639`, and `13605` for subscription `13344`
- Product IDs: `12112` (SLT Paddle Daily) and `12608` (SLT Plan Basic)
- WordPress user: ID `352`, login `slt-paddle`, email `slt-paddle@example.test`, role `customer`
- Gateway: Paddle sandbox / `arraysubs_paddle`
- Checkout type: Paddle hosted overlay for initial purchase; N/A for the automatic renewal webhook sequences evidenced here
- Non-default settings: none introduced by `SLT-IMP-05`; this task was read-only and the shared D09 test configuration remained unchanged

## Exact route and context

- Webhook endpoint involved: `POST https://mirror-help.arrayhash.com/wp-json/arraysubs/v1/webhooks/arraysubs_paddle`
- Admin log route for observation: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-status&tab=logs`
- Raw source: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/uploads/wc-logs/arraysubs-gateway-2026-08-10-c1b2cbc2cf7fe20955db27c1ab93ed65.log`
- Server-side context: Paddle webhook processing; no customer browser is involved
- Browser/user context for final confirmation: WordPress administrator in isolated session `admin-SLT-IMP-05`; the raw-log slice and read-only relationship queries supplied the exact attribution

## Reproduction

1. On a Paddle-sandbox site, maintain an active SLT subscription using `arraysubs_paddle`, such as subscription `12639` or `13344`.
2. Let Paddle emit its ordinary transaction lifecycle around an automatic payment; do not force a local Action Scheduler drain.
3. After the webhook sequence arrives, open WooCommerce > Status > Logs and select the dated `arraysubs-gateway` source.
4. Read the chronological sequence for that subscription/order.
5. Observe WARNING entries saying `No handler for event` with a blank `normalized:` value for routine intermediary events such as `transaction.created`, `transaction.billed`, `transaction.updated`, and `transaction.paid`.
6. Continue to the mapped `transaction.completed` row and observe that it is processed as `payment_succeeded` for the same resolved subscription/order.

## Expected result

- Routine intermediary Paddle lifecycle events that are intentionally non-actionable are ignored or logged at an informational/debug level.
- WARNING is reserved for malformed, unsupported, or actionable webhook conditions.
- If an event is classified as unhandled, the log should include enough normalization/context to explain that classification without suggesting a failed payment when the sequence is healthy.

## Actual result

- The D0-D9 raw `arraysubs-gateway` files contain 113 WARNING entries, all with `No handler for event: <event> (normalized: )`.
- In the 2026-08-10 file, five such warnings occur in the sequence resolved to SLT subscription `12639` / order `12629`, followed by `transaction.completed -> payment_succeeded`.
- A second five-warning sequence resolves to SLT subscription `13344` / order `13343`, also followed by `transaction.completed -> payment_succeeded`.
- The warning severity therefore presents ordinary, successfully completed Paddle traffic as an operational warning and leaves the normalized event blank.

## Concrete proof

- `/home/server-manager/slt-evidence/SLT-IMP-05-wc-logs/00-summary.txt` records the complete source/date inventory, counts, event buckets, entity relationships, and attribution decision.
- `/home/server-manager/slt-evidence/SLT-IMP-05-wc-gateway-warning.png` shows the same dated source in WooCommerce > Status > Logs in the required admin browser session.
- `/home/server-manager/slt-evidence/SLT-IMP-05-wc-logs/arraysubs-gateway-2026-08-10-c1b2cbc2cf7fe20955db27c1ab93ed65.log.txt` contains every WARNING from that file and the selected chronological processed rows.
- Raw line range `12-27` contains the five-warning sequence and successful mapped completion for subscription `12639` / order `12629`.
- Raw line range `67-83` contains the five-warning sequence and successful mapped completion for subscription `13344` / order `13343`.
- Read-only database relationships confirm both subscriptions belong to user `352`, products `12112` / `12608`, gateways `arraysubs_paddle`, and parent orders `12629` / `13343`; both parent orders are `wc-completed`.
- Subscription `12639` has six completed payments and subscription `13344` has three at the capture cutoff.
- No credentials, card data, keyed customer URL, or provider dashboard/request diagnostics are included in this issue or its evidence.

## Scope notes and counterexamples

- Only ten warnings are claimed as directly SLT-attributable from the two cited raw-log sequences; the remaining aggregate warnings are not all assumed to belong to SLT.
- The same five-warning pattern also occurs at raw lines `88-104` for non-SLT subscription `7809` / order `7808`, followed by successful `transaction.completed`. This confirms a gateway-wide logging behavior rather than an SLT-fixture artifact; that non-SLT record was observed only and was not modified.
- The mapped `transaction.completed` handler succeeds in both SLT sequences, so this finding does not claim a lost charge, failed renewal, or state divergence. Severity is low because the impact is misleading/noisy diagnostics.
- `wc_logger` had zero WARNING/ERROR/CRITICAL entries across its extant D0-D9 files. The unrelated WooCommerce Stripe error for order `12131` is already tracked in `issues/critical-plugin-SLT-REN-03-subscription-checkout-offers-incompatible-alipay.md`.
- Suite reference `reference/SLT-REF-09-paddle-vs-stripe-renewal-mechanics-creation-webhooks-no-ops-.md` documents `transaction.completed` as the mapped payment-success event; this issue relies on that suite-local reference and live raw/runtime evidence only.
- No file under `arraysubs/` or `arraysubspro/` was opened, searched, or changed.

## D09 evening recurrence — 2026-08-11

The next natural Basic renewal reproduced the same healthy five-warning sequence in
`wp-content/uploads/wc-logs/arraysubs-gateway-2026-08-11-b1ddad3805dbe0ba0c9ea757962c9a57.log`.
At `14:31:03–14:31:07Z`, `transaction.billed`, `transaction.created`, two
`transaction.updated` deliveries, and `transaction.paid` were each logged as `WARNING`
with `normalized:` blank for subscription `13344` / parent order `13343`. The mapped
`transaction.completed` event then processed successfully at `14:31:11Z`; webhook-ledger
rows `682` / `688` record the associated `subscription.updated` /
`transaction.completed` events, and exact transaction `txn_01kzrkqs84sb3xcx73mzq8b4gc`
completed order `13758` for `$5.00`. This is misleading log severity only: the payment,
order, schedule, and exact two-message mail delta all succeeded. Sanitized proof:
`/home/server-manager/slt-evidence/SLT-SW-05-D09-natural-basic-renewal.txt`.

## Current reproduction and root cause

The report was not a false positive and remained active on 2026-08-14. The current dated
`arraysubs-gateway` log contained `80` additional `No handler for event` warnings. The observed
event buckets included the same `transaction.created`, `transaction.billed`,
`transaction.updated`, and `transaction.paid` sequence, plus `transaction.ready` from checkout
preparation.

The root cause was shared dispatch behavior, not Paddle signature or entity resolution:

1. Paddle's event map normalized only the final actionable transaction events
   (`transaction.completed` and `transaction.payment_failed`).
2. The routine intermediary states normalized to an empty string and had no derived handler.
3. `WebhookRouter::dispatch()` logged that condition as WARNING and returned `success=false`.
4. The outer router consequently released the idempotency claim and returned HTTP `500`.

The impact therefore exceeded misleading severity: Paddle treated every intentionally
non-actionable delivery as failed and could retry it. Paddle documents `transaction.paid` as the
captured-but-not-fully-processed state before `transaction.completed`, and its delivery guidance
requires HTTP `200` for successfully received notifications; non-200 responses are retried.
Unknown or malformed events must still retain failure visibility, so a blanket success response
would not be safe.

Provider references:

- `https://developer.paddle.com/webhooks/transactions/transaction-paid`
- `https://developer.paddle.com/webhooks/about/respond-to-webhooks`

## Fix

- Added the normalized gateway contract value `ignored` for explicitly classified no-op events.
- Mapped only Paddle's routine transaction intermediaries—`transaction.created`,
  `transaction.ready`, `transaction.billed`, `transaction.updated`, and `transaction.paid`—to
  `ignored`. Final success and failure events retain their existing actionable mappings.
- The router acknowledges an authenticated and claimed `ignored` event without resolving or
  mutating any subscription, order, customer, or gateway metadata. It retains the idempotency row,
  logs a contextual INFO entry with raw type, normalized value, and event ID, and returns HTTP
  `200`.
- Truly unsupported events still log WARNING, return HTTP `500`, and release their claim for
  retry. Their diagnostic now says `normalized: none` rather than printing a misleading blank.
- Invalid signatures still return HTTP `401` before any event claim or dispatch.

This is an explicit allowlist rather than a raw-prefix or “accept every Paddle event” rule. A new
provider event cannot silently bypass review, and no signed payload is trusted to mutate local
entities merely because it belongs to a routine transaction namespace.

## Regression verification

Verification completed on live staging on 2026-08-14:

1. Runtime normalization returned `ignored` for all five intermediary types,
   `payment_succeeded` for `transaction.completed`, `payment_failed` for
   `transaction.payment_failed`, and an empty/unsupported value for `qa.unknown`.
2. Five uniquely identified, correctly signed webhook requests were delivered through the real
   `POST /wp-json/arraysubs/v1/webhooks/arraysubs_paddle` route. Created, ready, billed, updated,
   and paid each returned HTTP `200` with `received=true` and `processed=true`.
3. The repeated `transaction.updated` event returned HTTP `200` with `duplicate=true`; exactly one
   ledger row existed for that event, proving claim-first idempotency still works.
4. A correctly signed unsupported `qa.unknown` control returned HTTP `500` and left zero ledger
   rows, proving retry semantics and warning visibility remain intact.
5. A mapped event with an invalid signature returned HTTP `401` and left zero ledger rows, proving
   the no-op path cannot bypass signature authentication.
6. The current WooCommerce log records all five routine deliveries at INFO as
   `Webhook acknowledged without action ... (normalized: ignored, event: ...)`. The explicit
   unsupported control is the only new WARNING, and the invalid-signature control is the only new
   ERROR.
7. An administrator opened the exact WooCommerce log in isolated browser session
   `admin-paddle-imp05`; the severity badges and full contextual messages rendered correctly.
   Browser errors were empty and console output contained only JQMIGRATE.
8. Mailpit's latest ID remained `1zPxE6FmuLNdLZQPE1aist`; webhook diagnostics emitted no email.

Evidence screenshot:
`/home/server-manager/slt-evidence/FIX-PADDLE-SLT-IMP-05-info-log.png`.

Core/Pro and security review: the change stays entirely in ArraySubsPro's automatic-payment
contract, Paddle map, and shared Pro webhook router. ArraySubs core is unchanged. Signature
verification, exact gateway resolution, atomic event claims, failure claim release, and final
payment handlers remain in their original order; the no-op branch executes only after successful
authentication and classification and deliberately performs no entity lookup or write.
