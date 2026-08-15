# Stripe and Paddle purchase regression — final report

## Scope and verdict

- Date: 2026-08-15
- Site: `https://mirror-help.arrayhash.com`
- Runtime: ArraySubs `1.8.12`, ArraySubsPro `1.1.3`, WooCommerce `10.9.4`, WooCommerce Stripe `10.8.4`
- Browser: real isolated Chrome sessions driven with `agent-browser`; admin, customer, guest, hosted Paddle overlay, and Stripe 3DS contexts were exercised
- Scope: new subscription checkout, ordinary-product checkout, mixed carts, Stripe SCA, canceled/incomplete checkout, admin/customer records, gateway bindings, webhooks, scheduler rows, and Mailpit
- Excluded by design: the later multi-day renewal regression; this run verified that future automatic-payment actions were created and correctly scoped, but did not advance time to execute those renewals

**Transaction matrix: PASS. Release gate: NOT CLEAN.** Every completed Stripe and Paddle payment produced the correct order and subscription cardinality, and no old-customer billing data was damaged. Three high-priority defects remain open: one longstanding duplicate-note defect, one abandoned-Paddle presentation/state defect, and one initial-SCA email wording/trigger defect. None caused a duplicate charge, failed successful payment, missing active subscription, or extra subscription.

## Purchase matrix

| Gateway / scenario | Browser result | Order | Subscription | Result |
|---|---|---:|---:|---|
| Stripe subscription-only, Visa `4242` | Paid `$19.99`; one Standard Weekly line | `27280` | exactly one active `27296` | PASS |
| Stripe normal-only, Visa `4242` | Paid `$15.00`; one Standard Tee line | `27315` | none | PASS |
| Stripe mixed cart, Visa `4242` | Paid `$34.99`; subscription plus Standard Tee | `27346` | exactly one active `27362`, for the subscription item only | PASS |
| Stripe SCA/3DS, Visa `3184` | One visible challenge completed; same pending records became paid/active | `27387` | exactly one active `27403` | PASS, issue #3 |
| Stripe incomplete/cancel return | Inline validation; cart retained | none | none | PASS |
| Paddle normal-only, sandbox Visa `4242` | Paid `$15.00`; one Standard Tee line | `27275` | none | PASS |
| Paddle subscription-only, sandbox Visa `4242` | Paid `$11.00`; one SLT Paddle Daily line | `27307` | exactly one active `27309` | PASS |
| Paddle mixed cart, sandbox Visa `4242` | Paid `$26.00`; subscription plus Standard Tee | `27339` | exactly one active `27341`, for the subscription item only | PASS |
| Paddle overlay return/cancel | Unpaid pending shell; no completed transaction or remote subscription | pending `27381` | pending `27383` | Payment invariant PASS; presentation/state issue #2 |

Stripe bindings used the expected secret-safe `cus_…`, `pm_…`, and `ch_…` shapes. Paddle bindings used the expected `ctm_…`, `sub_…`, `txn_…`, and `pri_…` shapes. Completed subscriptions showed the correct card summary, completed-payment count, recurring amount, next date, customer portal record, initial order, and future indexed scheduler arguments.

The Stripe SCA path recorded exactly `payment_intent.requires_action` -> `charge.succeeded` -> `payment_intent.succeeded`, with one order and one subscription throughout. The Paddle return path created no remote subscription, billing customer binding, completed transaction, renewal action, or payment/subscription email.

## Cross-system verification

- WooCommerce admin and customer order pages showed the expected line items, totals, statuses, and related-subscription cardinality.
- ArraySubs admin and customer portals agreed with WP-CLI/database state for all successful and canceled scenarios.
- Gateway Health kept Stripe and Paddle connected in test mode. Stripe's official WooCommerce and ArraySubs webhook endpoints remained configured.
- Completed Stripe fixtures had one reminder, invoice, and renewal-payment action. Completed Paddle fixtures had one invoice and renewal-payment action. No fixture had duplicate pending renewal legs.
- Purchase webhooks increased Stripe from `257` to `266` rows and Paddle from `106` to `142`; remote Paddle cleanup added exactly four expected cancellation events, ending at `146`. Final combined webhook count is `412`.
- Mailpit contained the expected ordinary-order and subscription/admin messages. The incomplete Stripe path and abandoned Paddle path emitted no success mail.
- Browser page-error buffers were empty for the clean direct-customer sessions. Network requests needed for checkout, 3DS, Paddle overlay, admin cancellation, and final pages returned successful responses.
- The only checkout console warning was WooCommerce's existing `wcBlocksData` dependency warning. A transient Chrome View-Transition `AbortError` was isolated to temporary Login-as-Customer cookies and disappeared when those helper cookies were removed; it was not a gateway or ArraySubs runtime failure.
- Debug-log entries after the preflight offset were all traced to QA WP-CLI probes: an undefined test variable, corrected schema probes, one parse error, internal-meta diagnostic notices, and a post-delete lookup of an intentionally deleted Action Scheduler ID. No browser-request ArraySubs/ArraySubsPro fatal, warning, uncaught exception, or database error was found.
- A fresh post-teardown admin session loaded the restored Subscriptions screen with exact counts `403 / 19 / 14 / 355 / 15`, then loaded Gateway Logs with the final Stripe SCA and Paddle cancellation chains visible. Its page-error buffer was empty and its cleared console contained only the normal JQMIGRATE informational line.

## Open findings

1. **Stripe webhook notes are duplicated.** Paid Stripe subscriptions `27296`, `27362`, and `27403` each received two identical private payment-confirmation notes. Historical records reproduce this before the gateway migration, so this is longstanding and did not affect charging, activation, bindings, or scheduling. Formal issue: `qa/issues/kanban/tasks/001-stage-5-stripe-webhooks-add-duplicate-payment.md`.
2. **An abandoned Paddle checkout shows a phantom connected authorization.** Pending subscription `27383` had no remote customer, payment method, remote Paddle subscription, completed transaction, card details, or renewal actions, but admin/customer UI rendered it as connected and as `Paddle (Paddle)`. Formal issue: `qa/issues/kanban/tasks/002-stage-6-abandoned-paddle-checkout-shows-phantom.md`.
3. **Initial Stripe SCA sends a renewal-verification email.** While the first checkout for subscription `27403` awaited its browser challenge, Mailpit and subscription notes described it as a renewal requiring verification. The payment completed correctly, but the trigger/wording is wrong for an initial checkout. Formal issue: `qa/issues/kanban/tasks/003-stage-5-initial-stripe-sca-checkout-sends-renewal.md`.

## Restoration and fixture teardown

The temporary browser-only QA window was restored exactly:

- Member Access exclusions returned from `[200,197,12112,447]` to `[]`.
- Renewal synchronization returned from disabled to enabled; first-charge mode remained `full`.
- Final `arraysubs_settings` hash exactly matches the baseline: `ef5e20f24ae03fcab4967dbe713bb7c1fb2fb5667a3d01600e4c38ccf166b3ae`.
- Final Member Access rule hash exactly matches the baseline: `48a238abb67869d4308fc3726dc5cd27237e1622b9ed6f9862057e0fac4526ac`.
- Stripe settings, Stripe extras, and Paddle settings hashes are unchanged from baseline.

Remote cleanup was completed before local deletion:

- Paddle subscriptions `27309` and `27341` were canceled through the real admin UI. Both exact sandbox subscriptions are remotely `canceled`, have no next billing date, and produced the expected two updated plus two canceled webhooks.
- Stripe subscriptions `27296`, `27362`, and `27403` were canceled locally and had all future payment actions canceled. ArraySubs owns those future charges; no provider-side recurring schedule remained.
- The abandoned Paddle fixture had no remote subscription to cancel.

Local teardown removed only the disposable scope: nine users (`459`–`467`), eight orders, six subscriptions, their 56 subscription notes, 29 exact WooCommerce order notes, and 27 exact Action Scheduler rows/logs. Explicit post-checks are zero for every targeted user, order, subscription/note post, action, action log, WooCommerce order table, order item, comment, and customer lookup record. Audit webhook rows and Mailpit messages were intentionally retained as evidence.

The two isolated comparison databases restored from the pre-migration SQL backup for the Stripe and Paddle fingerprints were dropped after use. Neither was the live WordPress database, and final schema checks confirmed both no longer exist.

Final live counts match the preflight population:

- WooCommerce `shop_order` rows: `692` with the exact baseline status distribution. An additional `36` `shop_order_refund` rows explain the generic `728` order-object count and are unrelated to this QA.
- ArraySubs subscriptions: `403` total — active `19`, canceled `355`, expired `15`, pending `14`.
- Active gateway distribution: Stripe `13`, Paddle `1`, BACS `4`, no stored gateway `1`.
- Action Scheduler pending count: `335`, exactly the baseline; due ArraySubs actions: `0`; ArraySubs failures in the last 24 hours: `0`.
- The 407 non-target subscription status records retained their exact before/after hash during provider cleanup. A separate backup comparison of the 13 preexisting active Stripe subscriptions found no changes to statuses, schedules/dates, payment counters, gateway customer/payment-method IDs, transaction IDs, card metadata, or token-like fields. During the migration window, scheduler pointer IDs were regenerated one-for-one for the 11 scheduled records. All 31 replacements are pending and match the old rows exactly on hook, Action Scheduler group, full indexed arguments, and scheduled UTC datetime; there are zero missing, overdue, non-pending, or duplicate replacements. Their semantic hashes match exactly at `2c7fa2171548464fbd01c87f12c13c70544eac05f6d15cd416c67ed538ba24b1`.
- The sole preexisting active Paddle subscription, `7809`, also matches its pre-migration backup: every `wp_posts` field is identical and 45 of 47 meta keys are byte-identical, including all remote customer/payment-method/subscription/price/session/transaction bindings, status, payment count, gateway/card descriptors, and billing dates/timestamps. Only its two scheduler pointer IDs changed; both resolve to one-for-one pending invoice/renewal actions with identical hook, group, arguments, priority, schedule payload, and UTC time. The old/current semantic action hashes are both `a4c9721986f2da25b48551d5212c32af65745737b7087db0d678a8e7c5a2234c`, with no missing, due, or pending duplicate action. Four old canceled duplicate-history rows were cleaned and were never actionable.
- Latest Mailpit message stayed at the final expected Stripe cleanup cancellation message after database teardown, proving teardown emitted no extra customer mail.

No ArraySubs or ArraySubsPro source file was changed during this QA run.

## Evidence index

- Preflight and exact settings restoration: `../preflight/report.md`
- Stripe browser/payment report: `../stripe/report.md`
- Paddle browser/payment and remote-cleanup report: `../paddle/report.md`
- Independent admin/customer audit screenshots: `../audit/`
- Post-teardown live admin smoke: `post-teardown-gateway-logs.png`
- Formal issue board: `../../../issues/kanban/tasks/`
