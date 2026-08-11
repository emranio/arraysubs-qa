# Routine Paddle intermediary webhooks are logged as unhandled warnings

- Severity: low
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
