---
id: 131
title: Fix all Paddle-prefixed lifecycle QA issues
status: done
priority: high
created: 2026-08-14T22:57:27.77371259+02:00
updated: 2026-08-15T01:04:46.130978614+02:00
started: 2026-08-15T01:04:46.130977783+02:00
completed: 2026-08-15T01:04:46.130977783+02:00
tags:
    - paddle
    - plugin
    - bugfix
class: standard
---

Scope: investigate, plan, security-review, implement, and live browser-retest all eight unresolved paddle-* reports under subscription-lifecycle-test/issues, one by one.

Inventory: SLT-ADM-01 gateway filter; SLT-IMP-05 webhook warnings; SLT-MYA-03 payment-method surface; SLT-MYA-03 renewal order metadata/line item; SLT-OBS-01 parse error; SLT-SW-05 duplicate parent linkage; SLT-SW-05 stale last transaction; SLT-SW-05 preview/order-pay total mismatch.

Protocol: verify each report against its originating task, QA plan, live data, and core/pro code; plan before editing; preserve dependent behavior and security boundaries; test live with agent-browser plus exact WP-CLI/Mailpit/scheduler evidence; rename with done- only after resolution; skip PHPCS/lint per workspace issue-fix workflow.

[[2026-08-14]] Fri 23:00
Plan — Paddle SLT-OBS-01 parse error: treat the historical E_PARSE as genuine but potentially already remediated. Correlate recovery mail/debug evidence and deployment history; require the current committed Paddle class and registered gateway to load through WordPress; exercise an authenticated admin page plus its real admin-ajax heartbeat, capture UI/network/error evidence, and verify no new debug-log or technical-issue mail delta. Do not patch speculative syntax, suppress fatal reporting, or reintroduce the invalid historical file. If all current paths are clean, close as a recovered transient deployment defect and rename done- with the regression proof.


Result 1/8 — Paddle SLT-OBS-01: confirmed genuine historical incomplete-file deployment, already remediated. Current Paddle class/gateway bootstrap, authenticated admin UI, and admin-ajax POST pass; debug parse-error count stayed 5 and Mailpit emitted no technical-issue mail. No speculative code patch. Report closed and renamed done-paddle-SLT-OBS-01-paddle-parse-error.md.


Plan — Paddle SLT-ADM-01 gateway filter: current browser and DB reproduce the issue. Canonical gateway identity is the exact sanitized WooCommerce gateway ID stored in _payment_gateway. Change core admin filter values for Paddle/PayPal to arraysubs_paddle/arraysubs_paypal, stop stripping arraysubs_ in both the collection and status-count queries, and keep Stripe as stripe. This preserves exact-match behavior, avoids cross-gateway leakage, requires no pro dependency in core, and keeps both endpoints aligned. Test with direct authenticated REST counts/IDs, then live browser Paddle list/counts plus exact affected ID and Stripe control; inspect network, console, errors, and screenshot. Build only the existing admin asset pipeline; no lint/PHPCS.


Result 2/8 — Paddle SLT-ADM-01: fixed exact gateway identity across UI, REST collection, and status counts. Live Paddle result is 11 with reported IDs 12639/13344; Stripe control remains 44; unauthenticated count request remains 401. Built existing assets, browser/network/error and Mailpit checks passed. Report closed and renamed done-paddle-SLT-ADM-01-paddle-gateway-filter-returns-zero.md.


Plan — Paddle SLT-IMP-05 webhook warnings: confirmed current, with 80 unhandled warnings on 2026-08-14 and HTTP-500 retry semantics behind the warning. Add an explicit normalized ignored-event contract value; map only Paddle transaction lifecycle intermediaries (created, ready, billed, updated, paid) to it; make the router acknowledge them as successful INFO without entity resolution while preserving idempotent claims. Keep unknown events as warning + retryable failure, but label normalized state as none rather than blank. Test normalization, a real signed REST delivery, duplicate idempotency, invalid-signature 401, unknown-event 500/released claim, current WC log severity, and admin browser log UI. No provider transaction or subscription will be mutated.


Result 3/8 — Paddle SLT-IMP-05: fixed explicit no-op webhook classification. Five signed intermediary events now return 200 and INFO with normalized=ignored; duplicate claim is idempotent; unsupported control remains 500/WARNING with released claim; invalid signature remains 401 with no claim. Browser WC-log UI, console/errors, and Mailpit checks passed. Report closed and renamed done-paddle-SLT-IMP-05-paddle-routine-webhooks-logged-as-unhandled-warnings.md.


Plan — Paddle SLT-MYA-03 stale payment method: confirmed exact remote transaction/subscription/customer binding and live customer display 4242 versus Paddle 5556. Keep the existing hosted update surface and normalized transaction.completed -> payment_method_updated route. Replace its no-op with a mutation-lock-protected card-display sync that requires completed PM-change origin, exact Paddle transaction/subscription/customer identity, valid card schema, and a monotonic provider event timestamp. Persist only safe display fields plus source/time through GatewayMetaStore, clear retry state, log a private safe note, and emit the existing payment-method-updated hook. Add the Paddle watermark to core managed/detach protection. Test the real formatter/UI before and after a correctly signed replay built from the immutable affected Paddle transaction; verify duplicate idempotency, stale-event non-rewind, mismatched identity rejection, no orders/schedule/mail changes, and browser/console/network state.

[[2026-08-14]] Fri 23:36
Implementation checkpoint — payment-method sync patch adds exact subscription/customer/transaction binding, card schema validation, mutation locking, monotonic Paddle event ordering, detach protection, safe canonical metadata, retry reset, private note, and existing update hook. Beginning live signed-event regression with lifecycle state snapshots.

[[2026-08-14]] Fri 23:44
Result 4/8 — Paddle SLT-MYA-03 stale card display: fixed signed payment-method-change reconciliation with exact subscription/customer binding, validated card display fields, mutation locking, nanosecond stale-event protection, canonical detach-safe persistence, retry reset, private note, and the existing update hook. Real affected transaction replay, duplicate, stale, mismatch, malformed-card, lifecycle/order/scheduler/Mailpit, and authenticated browser checks passed; UI now shows Visa ending in 5556. The single probe-created note was removed before the final replay. Report closed and renamed done-paddle-SLT-MYA-03-missing-local-paddle-payment-method-update-surface.md.

[[2026-08-14]] Fri 23:47
Plan — Paddle SLT-MYA-03 renewal order auditability: confirmed historical broken controls and traced the two symptoms to the old manual retroactive wc_create_order path plus omitted authored transaction-meta writes. Current committed lifecycle hardening already reuses core OrderCreation for retroactive renewals and persists both Paddle transaction keys for pre-created and retroactive orders. Do not add duplicate code. Verify old controls, current post-revision paid order 15775, and an isolated non-provider-bound fixture built through the exact private retroactive constructor; require one correctly priced/linked item, renewal cycle/date/reverse metadata, exact transaction fields, browser UI, no mail, and exact fixture teardown with zero residue.

[[2026-08-14]] Fri 23:50
Result 5/8 — Paddle SLT-MYA-03 renewal order auditability: historical controls remained true positives, but current lifecycle hardening already fixes them by sharing core OrderCreation and persisting both Paddle transaction metas before provider completion. Paid post-revision order 15775 and a fresh isolated retroactive fixture passed structured plus authenticated admin UI checks. Fixture order 26832/subscription 26829 were identity-checked and removed with zero item/action residue and no mail. No redundant code change; report closed and renamed done-paddle-SLT-MYA-03-paddle-renewal-order-omits-transaction-meta-and-line-item.md.

[[2026-08-14]] Fri 23:54
Plan — Paddle SLT-SW-05 duplicate parent relationship: current two-stale-order-object probe reproduced two identical HPOS rows, so the report remains a true positive despite the new checkout mutex. Harden the shared core parent-link helper, not Paddle: acquire the centralized checkout-creation lock reentrantly, reload an authoritative order after the lock, merge only parent/customer-validated unique IDs, treat one physical meta row as the canonical success condition, and use WC_Order::update_meta_data/save_meta_data so HPOS plus compatibility sync collapse duplicates. Verify the live affected order and the reproduced fixture are repaired to one row, repeat stale-object/concurrent-style calls remain one row, forged/cross-customer IDs fail, composite arrays stay intact, browser UI remains one relationship, no mail/actions, and exact fixture teardown.

[[2026-08-15]] Sat 00:01
Result 6/8 — Paddle SLT-SW-05 duplicate parent linkage: reproduced the current same-request stale-WC_Order duplication, fixed shared core relationship persistence with the centralized reentrant checkout lock plus authoritative post-lock HPOS read and exact one-row verification, and repaired affected order 20113 from two rows to one  row. Stale-object repeats, composite IDs, stale cleanup, cross-customer rejection, UI, Mailpit, and exact fixture/action cleanup passed. Report closed and renamed done-paddle-SLT-SW-05-duplicate-parent-subscription-ids-meta.md.

[[2026-08-15]] Sat 00:01
Correction to Result 6/8: affected order 20113 was repaired from two physical HPOS relationship rows to one row containing sole subscription ID 20114.


Plan — Paddle SLT-SW-05 stale last transaction: confirmed the gateway-specific omission across two reported Paddle subscriptions and Stripe/Mollie/PayPal controls. Persist the newest successful Paddle renewal transaction only after exact paid-order/provider/subscription/customer/site/amount/currency binding, inside the existing subscription mutation boundary. Pair it with a nanosecond event watermark so out-of-order delivery cannot rewind the audit link; keep exact replay idempotent and fail closed on equal-time transaction conflicts. Test canonical-row repair, malformed/conflicting/stale inputs, a real signed current captured transaction through the public REST route, duplicate and out-of-order delivery, renewal accounting/schedule/mail boundaries, affected-record reconciliation, and live admin plus owner views.


Result 7/8 — Paddle SLT-SW-05 stale last transaction: fixed the missing subscription audit update with exact provider-bound finalization plus monotonic nanosecond event ordering in canonical GatewayMetaStore persistence. Fixture tests passed current/replay/stale/conflict/malformed and duplicate-row cases. A signed real transaction completed order 20500 once, advanced subscription 7809 payments 39→40, set the same transaction on order/subscription, preserved the provider date, and produced only the expected first-delivery mails; exact duplicate, same-time, and older replays added no accounting, note, or mail. Exact remote/local reconciliation repaired reported subscriptions 13344 and 12639 to their newest completed transactions. Admin, order, owner portal, browser error, and screenshot checks passed. Report closed and renamed done-paddle-SLT-SW-05-paddle-renewal-leaves-subscription-last-transaction-stale.md.


Plan — Paddle SLT-SW-05 preview/order-pay mismatch: reproduce the current three-calculation race at a controlled daily-cycle cent boundary, then make the preview a signed customer-consent contract without trusting client prices. Require a short-lived user/target/terms-bound HMAC quote at execute, return a fresh 409 quote before mutations when it differs, and repeat the comparison under the canonical refund/subscription lock immediately before order creation. Keep Paddle provider preparation and the signed order contract downstream. Test HMAC/user/target tampering, all proration modes, stale and fresh totals, locked races, duplicate submissions, Pro-active and core-only operation, a real customer browser refresh with visible toast/loading behavior, and exact zero-residue teardown.


Result 8/8 — Paddle SLT-SW-05 preview/order-pay mismatch: reproduced USD 0.02 preview → USD 0.01 order on the pre-fix current runtime, then added a 15-minute opaque user-bound HMAC quote, exact execute-time validation, and a second inside-lock term guard. Stale/tampered/cross-user/cross-target quotes now create no order and return a fresh HTTP 409 confirmation; valid fresh orders exactly equal the confirmed quote. Pro-active 40-assertion and core-only 35-assertion matrices passed. In the live Paddle-context portal, USD 0.08 refreshed visibly to USD 0.07 with the translated info toast after an exact 409, while product/status/order/reservation/provider/mail state stayed unchanged. Build, browser network/console/error, screenshots, and exact fixture/note/action/script/session teardown passed. Report closed and renamed done-paddle-SLT-SW-05-preview-total-disagrees-with-order-pay.md. All eight Paddle-prefixed reports are resolved.
