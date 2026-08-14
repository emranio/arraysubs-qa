---
id: 126
title: Fix every critical plugin QA issue
status: done
priority: critical
created: 2026-08-12T03:12:27.655369941+02:00
updated: 2026-08-14T04:19:48.823519348+02:00
started: 2026-08-12T03:12:32.554351649+02:00
completed: 2026-08-14T04:19:48.823516644+02:00
tags:
    - plugin
    - bugfix
    - maintenance
class: standard
---

Resolve each critical plugin report in issues/ sequentially with source/data investigation, a dependency-safe implementation plan, live regression testing, and a done- filename only after proof.

[[2026-08-12]] Wed 03:12
ADM-05 resolved and renamed after live regression. ADM-06 verified as a canonical order-item attribution/refund-capacity defect; implementation and native refund regression are in progress.

[[2026-08-12]] Wed 13:21
ADM-06 resolved and renamed after canonical item ownership, exact shared-order attribution, fail-closed refund capacity, replay/idempotency, core-only, Pro, historical Stripe/Paddle, and live HPOS browser verification. Final 18-scenario matrix and targeted scheduler-zero regression passed; browser fixture #15436/order #15439/refund #15442 cleaned with zero residue.

[[2026-08-12]] Wed 13:24
Started CHK-06 investigation. Report/task expect cart composition rejection, but current General Settings UI explicitly documents one-click checkout as clearing existing cart contents and keeping only the clicked item. Treating false-positive classification as an open gate pending code/history, cross-plugin, settings UI, and live-browser verification; no product edit made.

[[2026-08-12]] Wed 13:45
CHK-06 resolved as a false-positive QA oracle after independent code/history audits and live browser controls. One-click subscription mode intentionally replaced A with B and retained current-request quantity; a full-hash-restored default-mode bracket rejected B with the exact composition notice, preserved A, and merged repeated A to quantity 2. Settings hash, persistent cart, Mailpit baseline, and browser-error state were clean; no product code changed.

[[2026-08-12]] Wed 13:48
Started CHK-12. The report's sequential grouped-child replacement may share the documented one-click contract, but disposition is gated on Woo grouped-loop ordering, fixture/current-setting checks, independent audits, and a live subscription_items versus default-mode browser control. No product edit made.

[[2026-08-12]] Wed 14:03
CHK-12 resolved as a false-positive sequential oracle. Woo grouped-child tracing and live browser controls proved configured subscription_items mode intentionally replaced Daily with Fee (4), while a one-field default-mode bracket rejected Fee with the exact notice and preserved Daily (0). Full settings SHA-256 0825104e8b37e8b0ac9524db1b2d5c605808c5e99d9d09714d77ddbb0cac1109, temporary Member Access exclusions, backup option, cart, orders/subscriptions, and Mailpit baseline were restored exactly; no product code changed. The distinct simultaneous stale notice remains in PROD-09.

[[2026-08-12]] Wed 14:43
Resolved critical SLT-CPN-02 on 2026-08-12. Root cause was Coupon Tracking reloading an incomplete classic order during the pre-save-items woocommerce_new_order fallback. Added the authoritative order as a validated fourth internal hook argument, retained legacy/integration fallback, and verified core/Pro listener compatibility. Live before/after: 15576/15577 failed capture; 15588/15589 captured all expected fields and exactly one note; natural renewal invoice 15600 stayed USD 10.00 with zero fees/coupons. Cleanup and full settings hash restore passed. Report renamed done-critical-plugin-SLT-CPN-02-one-time-coupon-capture-missing.md; separate low wording issue logged.

[[2026-08-12]] Wed 15:20
EML-02 completed 2026-08-12: confirmed customer-first Stripe webhook resolution paired orders 13063/13466 with unrelated sub 12655. Fixed Pro normalizer/delegate/context bridge with canonical HPOS order scope, unique-only customer fallback, provider/customer coherence, multi-sub fanout, and mutation-boundary guards. Historical oracles, mismatch/ambiguity/unique probes, live same-customer manual payment (15627 target / 15630 decoy / 15634 order), authentic unlinked order negative (15640), PHP syntax and diff checks all passed. Fixtures/actions removed and report prefixed done-.

[[2026-08-12]] Wed 15:36
2026-08-12 EML-02 final dependency audit complete: Stripe SetupIntent now uses the payload payment-method ID and resolves Woo Stripe's authoritative setup-intent order before existing-card owners; exact customer+payment-method account events fan out safely; ambiguous payment success/failure emits no subscription hook with ID 0. Disposable precedence fixture order 15661 linked only to target 15662 even though decoy 15665 already owned the selected PM; target alone updated/hooked, then all fixtures/notes were deleted. Historical probe metadata was restored from captured baselines (12003/12017/12234 keys absent; 11959 and 12655 exact timestamps restored). Two independent reviews report PASS; report remains done-prefixed.

[[2026-08-12]] Wed 15:40
EML-02 restoration addendum: MySQL row-binlog audit found the handler probe had also changed subscription 12234 `_gateway_status` from its pre-probe `inactive` value to `active`. Restored the exact `inactive` value and recorded it in the done report; requested a full binlog audit of every probe-mutated key.

[[2026-08-12]] Wed 19:21
MYA-03 resolved 2026-08-12 after true-positive diagnosis and dependency/security review. Paddle payment-method-change transactions now bypass renewal accounting; checkout/renewal binding, durable payment/finalization, lock ordering, provider-schema schedule alignment, all three action legs, nanosecond out-of-order protection, overflow rejection, and remote snapshot ownership are fail-closed. Full live signed-webhook, concurrency, retry, Mailpit, and post-frozen-source browser matrix passed three independent audits. Disposable subscription 15669 and its exact fixture orders, notes, actions, claims, locks, helpers, and scripts were removed with zero residue; historical order 12629, product 12112, and user 352 remain. Report renamed done-critical-plugin-SLT-MYA-03-payment-method-change-treated-as-zero-dollar-renewal.md. Separate light card display and missing line item reports remain open.

[[2026-08-12]] Wed 19:45
OBS-01 plan (2026-08-12): confirmed concurrent first-write TOCTOU via historical Stripe checkout and official webhook binlog threads. Implement one core-owned allowlisted per-subscription gateway-meta store with an atomic set/delete batch, database-scoped advisory lock, reentrant request depth, strict arraysubs_data/scalar validation, oldest-row preservation, verified writes, and surfaced WP_Error failures. Route canonical writers in both plugins through it, then test two-process contention, timeout/retry, duplicate healing, live Stripe block checkout/webhook capture, gateway UI state changes, and cleanup before closing the report.

[[2026-08-12]] Wed 21:33
OBS-01 dependency/security gate expanded after review of every migrated writer. `detached` must be an irreversible tombstone set only in the same gateway-meta lock as the complete 26-key context deletion, after verified remote cancellation where the provider owns a recurring schedule. Ordinary webhooks, retries, sync, reconciliation, renewal payment, and status handlers must neither race through nor resurrect it. PayPal/Paddle remote cancellation failures require one bounded, idempotent, exact-binding retry service shared by admin detach, automatic downgrade, normal cancellation, and overdue cancellation. Provider events and any `payment_complete()` boundary require unambiguous canonical subscription/order/customer/gateway/amount/currency/transaction binding plus the order-payment lock. Final proof is blocked until these conditions pass deterministic negative/race tests and the live Stripe fixture completes retry, resync, detach, stale-capture rejection, and exact cleanup.

[[2026-08-13]] Thu 04:24
OBS-01 resolved and renamed after correcting the original WordPress metadata assumption, proving the concurrent absent-key race from checkout/webhook binlog threads, and centralizing 28 managed keys in the core atomic store. The 32/32 ultimate frozen-source matrix, independent high/medium security review, live Stripe checkout/webhook/resync/metadata-retry/detach/stale-write browser flow, one-time 29-row historical dedupe audit, site-wide zero-duplicate check, and exact fixture/control cleanup all passed. The migration auto-ran before the planned checkpoint; its one request and exact 29 identical newer-row deletions were reconstructed from the binary log and documented in the done report.

[[2026-08-13]] Thu 08:45
PROD-08 resolved and renamed as a false-positive save-path attribution. Historical audit rows and binlog thread 118459 prove the variable product, four variations, settings, and stock saved successfully before a distinct canonical WordPress/WooCommerce trash cascade 48 seconds later. Fresh live editor control 18907 with variations 18910/18912/18914/18916 passed attribute save, generation, variation AJAX save, second draft save, and direct edit reload with invariant statuses and no trash ledger, errors, settings drift, or mail. Three independent code/security reviews found no plugin trash caller and rejected broad status guards as deletion/data-integrity regressions. No production source changed; exact marked fixture, 16 notes, and 21 actions were cleaned to zero residue.

[[2026-08-13]] Thu 05:07
Started REN-03. Historical provider log/order/mail evidence confirms Stripe rejected Alipay only because ArraySubsPro added future-use capture; the same order succeeded with Card. Fresh block-checkout runtime proof shows the root mismatch: Woo Stripe reports `cartContainsSubscription=false` while ArraySubsPro reports `forceSavePaymentMethod=true`. Planned scope is the existing Pro Stripe compatibility adapter, provider-declared reusable-method filtering on block/classic/order-pay surfaces, and pre-payment tamper validation, followed by a reversible live Alipay bracket and Card control.

[[2026-08-13]] Thu 10:09
REN-03 resolved 2026-08-13 and report prefixed done-. Confirmed Woo Stripe did not recognize ArraySubs recurrence while Pro forced future-use capture. Added provider-registry reusable-method policy, exact classic/Store API/order-pay validation, provider-resolved ownership/type checks, and request-local vaulting for saved_cards=no. Frozen policy/security suite 70/70, REST isolation 6/6, PHP syntax/diff checks, three independent critical/high reviews, ordinary-product Alipay control, subscription filtering, tamper HTTP 400/no lifecycle, saved-card checkout 18950/18951, and saved_cards=no new-card checkout 18961/18962 all passed. Provider refunds completed; exact fixtures/actions/notes removed; Stripe and ArraySubs settings hashes restored; browser session closed.

[[2026-08-13]] Thu 10:14
Started SETUP-05 on 2026-08-13. Historical report, originating task #26, suite/stage instructions, code/history, current product/catalog metadata, logs, and redacted gateway state are under investigation before any source or data change. Report currently appears to target a missing Paddle product-save lifecycle hook; disposition remains open pending remote/current-data and dependency-safe idempotency analysis.

[[2026-08-13]] Thu 12:28
SETUP-05 resolved 2026-08-13. Added the missing authenticated/capability-bound Pro save lifecycle and hardened Paddle catalogue creation with environment-scoped exact bindings, durable reservations, bounded reconciliation, immutable checkout prices, safe archive ownership, and sanitized logs. Deterministic suites passed 72+14+12+29+29 assertions plus the composite matrix and independent critical/high review. Two live admin saves created exactly one sandbox product/price then reused both; the controlled renewal-sync-off checkout bracket visibly offered Paddle and restored the complete settings hash. Final provider counts 4/4, users/subscriptions/orders 368/402/711, cart/fixtures/processes clean. Report prefixed done-.

[[2026-08-14]] Fri 04:18
SW-05 resolved and renamed after confirming fee-only plan-switch orders were omitted from Paddle checkout item construction. Implemented a signed version-4 switch contract, exact proration/credit accounting, provider-scoped one-time price translation, lock/refund/settlement/recovery fences, and remote recurring-price alignment across core and Pro. The full deterministic matrices, independent reviews, real Paddle block checkout -> portal upgrade -> order-pay settlement, exact remote/local alignment, cancellation, settings restoration, and fixture teardown all passed.

[[2026-08-14]] Fri 04:18
SYN-02 resolved after reproducing a clean variable parent's hidden unindexed simple controls rematerializing flex metadata on no-op Update. Pro now removes/disables those six names for non-simple forms, normalizes exactly the six parent keys on authenticated variable saves, rejects incomplete/colliding payloads and invalid intervals, preserves variation/custom-product ownership, and runtime-rejects any stale variable plan. Deterministic regression passed 28/28; the production build, final parent Update, dynamic variable/simple transition, variation AJAX save, exact data/settings/Mailpit checks, and three independent reviews passed. All critical issue reports are now done-prefixed.
